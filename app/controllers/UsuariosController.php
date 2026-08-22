<?php

require_once __DIR__ . '/../core/Controller.php';

// Controlador del módulo Usuarios — acceso exclusivo al rol Administrador (requireRole() en cada
// acción, GET y POST incluidos; el menú del dashboard solo oculta el enlace, nunca sustituye esta
// validación). Ver auditoría M11-A para el detalle de las decisiones funcionales.
class UsuariosController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        $usuarioModel = $this->model('Usuario');
        $rolModel = $this->model('Rol');

        $q = trim($_GET['q'] ?? '');
        $idRol = (int) ($_GET['id_rol'] ?? 0);
        $estado = in_array($_GET['estado'] ?? '', ['activo', 'inactivo'], true) ? $_GET['estado'] : '';

        $usuarios = $usuarioModel->getAll([
            'q' => $q,
            'id_rol' => $idRol,
            'estado' => $estado,
        ]);

        $this->view('usuarios/index', [
            'usuarios' => $usuarios,
            'roles' => $rolModel->getActivos(),
            'q' => $q,
            'idRol' => $idRol,
            'estado' => $estado,
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        $idUsuario = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            echo 'Usuario no válido.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $usuario = $usuarioModel->findByIdCompleto($idUsuario);

        if ($usuario === false) {
            echo 'Usuario no encontrado.';
            return;
        }

        $this->view('usuarios/ver', [
            'usuario' => $usuario,
            'esUsuarioActual' => $idUsuario === (int) $_SESSION['id_usuario'],
        ]);
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        $rolModel = $this->model('Rol');

        $this->view('usuarios/crear', [
            'roles' => $rolModel->getActivos(),
            'error' => null,
            'datosFormulario' => [],
        ]);
    }

    public function guardar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $usuarioModel = $this->model('Usuario');
        $rolModel = $this->model('Rol');
        $bitacoraModel = $this->model('Bitacora');

        $roles = $rolModel->getActivos();

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $usuarioInput = trim($_POST['usuario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $idRol = (int) ($_POST['id_rol'] ?? 0);
        $estadoInicial = in_array($_POST['estado_usuario'] ?? 'activo', ['activo', 'inactivo'], true)
            ? ($_POST['estado_usuario'] ?? 'activo')
            : 'activo';
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmacion = (string) ($_POST['password_confirmacion'] ?? '');

        $datosFormulario = [
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuarioInput,
            'correo' => $correo,
            'telefono' => $telefono,
            'id_rol' => $idRol,
            'estado_usuario' => $estadoInicial,
        ];

        $error = $this->validarDatosGenerales($usuarioModel, $roles, $nombreCompleto, $usuarioInput, $correo, $telefono, $idRol, $estadoInicial, null);

        if ($error === null) {
            if ($password === '') {
                $error = 'La contraseña es obligatoria.';
            } elseif (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== $passwordConfirmacion) {
                $error = 'Las contraseñas no coinciden.';
            }
        }

        if ($error !== null) {
            $this->view('usuarios/crear', [
                'roles' => $roles,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $datos = [
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuarioInput,
            'correo' => $correo !== '' ? $correo : null,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id_rol' => $idRol,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'estado_usuario' => $estadoInicial,
        ];

        try {
            $usuarioModel->beginTransaction();

            $idUsuario = $usuarioModel->crear($datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_USUARIO',
                modulo: 'Usuarios',
                resultado: 'exitoso',
                descripcion: 'Se registró el usuario "' . $usuarioInput . '" (' . $nombreCompleto . ') con rol ' . $this->nombreRolPorId($idRol, $roles) . '.',
                tablaAfectada: 'usuarios',
                idRegistroAfectado: $idUsuario,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $usuarioModel->commit();

            $_SESSION['mensaje_exito'] = 'El usuario fue registrado correctamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        } catch (Throwable $e) {
            if ($usuarioModel->inTransaction()) {
                $usuarioModel->rollBack();
            }

            error_log('Error al registrar el usuario: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible registrar el usuario. Verifique los datos e intente nuevamente.';

            $this->view('usuarios/crear', [
                'roles' => $roles,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        $idUsuario = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            echo 'Usuario no válido.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $rolModel = $this->model('Rol');

        $usuario = $usuarioModel->findByIdCompleto($idUsuario);

        if ($usuario === false) {
            echo 'Usuario no encontrado.';
            return;
        }

        $this->view('usuarios/editar', [
            'usuario' => $usuario,
            'roles' => $this->rolesParaFormulario($rolModel, (int) $usuario['id_rol'], $usuario['nombre_rol']),
            'error' => null,
            'datosFormulario' => $usuario,
            'esUsuarioActual' => $idUsuario === (int) $_SESSION['id_usuario'],
        ]);
    }

    public function actualizar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idUsuario = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            echo 'Usuario no válido.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $rolModel = $this->model('Rol');
        $bitacoraModel = $this->model('Bitacora');

        $usuarioExistente = $usuarioModel->findByIdCompleto($idUsuario);

        if ($usuarioExistente === false) {
            echo 'Usuario no encontrado.';
            return;
        }

        $idRolActual = (int) $usuarioExistente['id_rol'];
        $rolesFormulario = $this->rolesParaFormulario($rolModel, $idRolActual, $usuarioExistente['nombre_rol']);

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $usuarioInput = trim($_POST['usuario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $idRol = (int) ($_POST['id_rol'] ?? 0);
        $estadoNuevo = in_array($_POST['estado_usuario'] ?? '', ['activo', 'inactivo'], true) ? $_POST['estado_usuario'] : '';

        $datosFormulario = [
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuarioInput,
            'correo' => $correo,
            'telefono' => $telefono,
            'id_rol' => $idRol,
            'estado_usuario' => $estadoNuevo,
        ];

        $error = $this->validarDatosGenerales($usuarioModel, $rolesFormulario, $nombreCompleto, $usuarioInput, $correo, $telefono, $idRol, $estadoNuevo, $idUsuario);

        $esUsuarioActual = $idUsuario === (int) $_SESSION['id_usuario'];
        $idRolAdministrador = $rolModel->obtenerIdPorNombre('Administrador');
        $eraAdministrador = $idRolAdministrador !== null && $idRolActual === $idRolAdministrador;

        if ($error === null && $esUsuarioActual && $estadoNuevo === 'inactivo') {
            $error = 'No puede inactivar su propia cuenta.';
        }

        if ($error === null && $esUsuarioActual && $eraAdministrador && $idRolAdministrador !== null && $idRol !== $idRolAdministrador) {
            $error = 'No puede cambiar su propio rol de Administrador.';
        }

        // Primera pasada de la regla de último Administrador, con los datos leídos al inicio de la
        // petición — evita abrir una transacción cuando ya hay un error de validación más simple. La
        // comprobación que realmente decide se repite DENTRO de la transacción (más abajo) usando el
        // registro recién bloqueado con FOR UPDATE, para cerrar la ventana de concurrencia.
        $dejariaDeSerAdminFuncional = $idRolAdministrador !== null
            && $eraAdministrador
            && ($estadoNuevo !== 'activo' || $idRol !== $idRolAdministrador);

        if ($error === null && $dejariaDeSerAdminFuncional) {
            $error = $this->validarUltimoAdministrador($usuarioModel, $idUsuario);
        }

        if ($error !== null) {
            $this->view('usuarios/editar', [
                'usuario' => $usuarioExistente,
                'roles' => $rolesFormulario,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'esUsuarioActual' => $esUsuarioActual,
            ]);
            return;
        }

        $datos = [
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuarioInput,
            'correo' => $correo !== '' ? $correo : null,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id_rol' => $idRol,
            'estado_usuario' => $estadoNuevo,
        ];

        try {
            $usuarioModel->beginTransaction();

            $usuarioBloqueado = $usuarioModel->findByIdForUpdate($idUsuario);

            if ($usuarioBloqueado === false) {
                $usuarioModel->rollBack();
                echo 'Usuario no encontrado.';
                return;
            }

            // Recalculado con el registro ya bloqueado (no con $idRolActual leído antes de la
            // transacción): si otra petición concurrente cambió el rol/estado de este mismo usuario
            // entre la primera pasada y este punto, aquí se evalúa el estado real y actual.
            $eraAdministradorBloqueado = $idRolAdministrador !== null && (int) $usuarioBloqueado['id_rol'] === $idRolAdministrador;
            $dejariaDeSerAdminFuncionalBloqueado = $eraAdministradorBloqueado
                && ($estadoNuevo !== 'activo' || $idRol !== $idRolAdministrador);

            if ($dejariaDeSerAdminFuncionalBloqueado) {
                $mensajeUltimoAdmin = $this->validarUltimoAdministrador($usuarioModel, $idUsuario);

                if ($mensajeUltimoAdmin !== null) {
                    $usuarioModel->rollBack();

                    $this->view('usuarios/editar', [
                        'usuario' => $usuarioExistente,
                        'roles' => $rolesFormulario,
                        'error' => $mensajeUltimoAdmin,
                        'datosFormulario' => $datosFormulario,
                        'esUsuarioActual' => $esUsuarioActual,
                    ]);
                    return;
                }
            }

            $usuarioModel->actualizar($idUsuario, $datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_USUARIO',
                modulo: 'Usuarios',
                resultado: 'exitoso',
                descripcion: 'Se modificaron los datos del usuario "' . $usuarioInput . '".',
                tablaAfectada: 'usuarios',
                idRegistroAfectado: $idUsuario,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $usuarioModel->commit();

            $_SESSION['mensaje_exito'] = 'Los datos del usuario fueron actualizados correctamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        } catch (Throwable $e) {
            if ($usuarioModel->inTransaction()) {
                $usuarioModel->rollBack();
            }

            error_log('Error al actualizar el usuario: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible actualizar el usuario. Verifique los datos e intente nuevamente.';

            $this->view('usuarios/editar', [
                'usuario' => $usuarioExistente,
                'roles' => $rolesFormulario,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'esUsuarioActual' => $esUsuarioActual,
            ]);
            return;
        }
    }

    public function cambiarEstado(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idUsuario = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            echo 'Usuario no válido.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $rolModel = $this->model('Rol');
        $bitacoraModel = $this->model('Bitacora');

        $usuario = $usuarioModel->findByIdCompleto($idUsuario);

        if ($usuario === false) {
            echo 'Usuario no encontrado.';
            return;
        }

        $estadoAnterior = $usuario['estado_usuario'];

        if ($estadoAnterior === 'activo') {
            $estadoNuevo = 'inactivo';
        } elseif ($estadoAnterior === 'inactivo') {
            $estadoNuevo = 'activo';
        } else {
            echo 'Estado del usuario no válido.';
            return;
        }

        $esUsuarioActual = $idUsuario === (int) $_SESSION['id_usuario'];

        if ($esUsuarioActual && $estadoNuevo === 'inactivo') {
            $_SESSION['mensaje_error'] = 'No puede inactivar su propia cuenta.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        }

        $idRolAdministrador = $rolModel->obtenerIdPorNombre('Administrador');

        try {
            $usuarioModel->beginTransaction();

            $usuarioBloqueado = $usuarioModel->findByIdForUpdate($idUsuario);

            if ($usuarioBloqueado === false) {
                $usuarioModel->rollBack();
                echo 'Usuario no encontrado.';
                return;
            }

            // Recalculado con el registro ya bloqueado, no con $usuario leído antes de la transacción.
            $esAdministradorBloqueado = $idRolAdministrador !== null && (int) $usuarioBloqueado['id_rol'] === $idRolAdministrador;
            $dejariaDeSerAdminFuncionalBloqueado = $estadoNuevo === 'inactivo' && $esAdministradorBloqueado;

            if ($dejariaDeSerAdminFuncionalBloqueado) {
                $mensaje = $this->validarUltimoAdministrador($usuarioModel, $idUsuario);

                if ($mensaje !== null) {
                    $usuarioModel->rollBack();

                    $_SESSION['mensaje_error'] = $mensaje;

                    header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
                    exit;
                }
            }

            $usuarioModel->cambiarEstado($idUsuario, $estadoNuevo);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CAMBIAR_ESTADO_USUARIO',
                modulo: 'Usuarios',
                resultado: 'exitoso',
                descripcion: 'Cambio de estado: ' . $estadoAnterior . ' → ' . $estadoNuevo . ' (usuario "' . $usuario['usuario'] . '").',
                tablaAfectada: 'usuarios',
                idRegistroAfectado: $idUsuario,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $usuarioModel->commit();

            $_SESSION['mensaje_exito'] = 'El estado del usuario fue actualizado correctamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        } catch (Throwable $e) {
            if ($usuarioModel->inTransaction()) {
                $usuarioModel->rollBack();
            }

            error_log('Error al cambiar el estado del usuario: ' . $e->getMessage());

            $_SESSION['mensaje_error'] = 'No fue posible cambiar el estado del usuario. Intente nuevamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        }
    }

    public function cambiarPassword(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idUsuario = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            echo 'Usuario no válido.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $bitacoraModel = $this->model('Bitacora');

        $usuario = $usuarioModel->findByIdCompleto($idUsuario);

        if ($usuario === false) {
            echo 'Usuario no encontrado.';
            return;
        }

        $esUsuarioActual = $idUsuario === (int) $_SESSION['id_usuario'];

        $passwordNueva = (string) ($_POST['password_nueva'] ?? '');
        $passwordConfirmacion = (string) ($_POST['password_confirmacion'] ?? '');

        $error = null;

        if ($esUsuarioActual) {
            $passwordActual = (string) ($_POST['password_actual'] ?? '');

            if ($passwordActual === '') {
                $error = 'Debe ingresar su contraseña actual.';
            } else {
                $usuarioConHash = $usuarioModel->findActivoConPasswordById($idUsuario);

                if ($usuarioConHash === false || !password_verify($passwordActual, $usuarioConHash['password_hash'])) {
                    $error = 'La contraseña actual no es correcta.';
                }
            }
        }

        if ($error === null && $passwordNueva === '') {
            $error = 'La nueva contraseña es obligatoria.';
        } elseif ($error === null && strlen($passwordNueva) < 8) {
            $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($error === null && $passwordNueva !== $passwordConfirmacion) {
            $error = 'Las contraseñas no coinciden.';
        }

        if ($error !== null) {
            $_SESSION['mensaje_error'] = $error;

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        }

        try {
            $usuarioModel->beginTransaction();

            $usuarioModel->cambiarPassword($idUsuario, password_hash($passwordNueva, PASSWORD_DEFAULT));

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CAMBIAR_PASSWORD_USUARIO',
                modulo: 'Usuarios',
                resultado: 'exitoso',
                descripcion: 'Se actualizó la contraseña del usuario "' . $usuario['usuario'] . '"' . ($esUsuarioActual ? ' (contraseña propia).' : '.'),
                tablaAfectada: 'usuarios',
                idRegistroAfectado: $idUsuario,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $usuarioModel->commit();

            $_SESSION['mensaje_exito'] = 'La contraseña fue actualizada correctamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        } catch (Throwable $e) {
            if ($usuarioModel->inTransaction()) {
                $usuarioModel->rollBack();
            }

            error_log('Error al cambiar la contraseña del usuario: ' . $e->getMessage());

            $_SESSION['mensaje_error'] = 'No fue posible actualizar la contraseña. Intente nuevamente.';

            header('Location: index.php?modulo=usuarios&accion=ver&id=' . $idUsuario);
            exit;
        }
    }

    // =========================================================================================
    // Helpers privados
    // =========================================================================================

    // Validaciones compartidas por guardar()/actualizar(). $idUsuarioActual es null al crear (no hay
    // nada que excluir en las verificaciones de duplicado); al editar es el id del propio registro.
    private function validarDatosGenerales(
        Usuario $usuarioModel,
        array $rolesValidos,
        string $nombreCompleto,
        string $usuarioInput,
        string $correo,
        string $telefono,
        int $idRol,
        string $estado,
        ?int $idUsuarioActual
    ): ?string {
        if ($nombreCompleto === '') {
            return 'El nombre completo es obligatorio.';
        }

        if (strlen($nombreCompleto) > 150) {
            return 'El nombre completo no puede superar los 150 caracteres.';
        }

        if ($usuarioInput === '') {
            return 'El usuario es obligatorio.';
        }

        if (strlen($usuarioInput) < 3 || strlen($usuarioInput) > 50) {
            return 'El usuario debe tener entre 3 y 50 caracteres.';
        }

        if (str_contains($usuarioInput, ' ')) {
            return 'El usuario no puede contener espacios.';
        }

        if ($usuarioModel->existeUsuario($usuarioInput, $idUsuarioActual)) {
            return 'El nombre de usuario ya está registrado.';
        }

        if ($correo !== '') {
            if (strlen($correo) > 100) {
                return 'El correo no puede superar los 100 caracteres.';
            }

            if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
                return 'El correo ingresado no es válido.';
            }

            if ($usuarioModel->existeCorreo($correo, $idUsuarioActual)) {
                return 'El correo ya está registrado.';
            }
        }

        if ($telefono !== '' && strlen($telefono) > 20) {
            return 'El teléfono no puede superar los 20 caracteres.';
        }

        if ($idRol <= 0) {
            return 'Debe seleccionar un rol válido.';
        }

        $rolValido = false;
        foreach ($rolesValidos as $rol) {
            if ((int) $rol['id_rol'] === $idRol) {
                $rolValido = true;
                break;
            }
        }

        if (!$rolValido) {
            return 'Debe seleccionar un rol válido.';
        }

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            return 'Estado no válido.';
        }

        return null;
    }

    // Devuelve la lista de roles activos para el <select>, agregando al final el rol actualmente
    // asignado si estuviera inactivo (mismo patrón que ResponsablesController con ubicaciones) — se
    // muestra claramente pero no se puede volver a elegir si ya se cambió a otro.
    private function rolesParaFormulario(Rol $rolModel, int $idRolActual, string $nombreRolActual): array
    {
        $roles = $rolModel->getActivos();

        foreach ($roles as $rol) {
            if ((int) $rol['id_rol'] === $idRolActual) {
                return $roles;
            }
        }

        $roles[] = [
            'id_rol' => $idRolActual,
            'nombre_rol' => $nombreRolActual,
            'es_inactivo' => true,
        ];

        return $roles;
    }

    // Regla de último Administrador (auditoría M11-A, punto 19). Los dos llamadores solo invocan este
    // método cuando ya determinaron que el usuario objetivo DEJARÍA de contar como Administrador
    // activo funcional tras el cambio (ver "dejariaDeSerAdminFuncional" en actualizar()/
    // cambiarEstado()) — por construcción, el propio objetivo nunca debe sumarse de vuelta al conteo.
    // Nunca hardcodea "sistema": contarAdministradoresActivosFuncionales() excluye por hash no
    // funcional, sea cual sea el usuario.
    private function validarUltimoAdministrador(Usuario $usuarioModel, int $idUsuarioObjetivo): ?string
    {
        if ($usuarioModel->contarAdministradoresActivosFuncionales($idUsuarioObjetivo) > 0) {
            return null;
        }

        return 'Debe permanecer al menos un Administrador activo con acceso al sistema.';
    }

    private function nombreRolPorId(int $idRol, array $roles): string
    {
        foreach ($roles as $rol) {
            if ((int) $rol['id_rol'] === $idRol) {
                return (string) $rol['nombre_rol'];
            }
        }

        return '—';
    }

    private function mensajeErrorDuplicado(Throwable $e): ?string
    {
        if (!($e instanceof PDOException)) {
            return null;
        }

        if (($e->errorInfo[0] ?? null) !== '23000') {
            return null;
        }

        $detalleError = $e->errorInfo[2] ?? '';

        if (str_contains($detalleError, 'uq_usuarios_usuario')) {
            return 'El nombre de usuario ya está registrado.';
        }

        if (str_contains($detalleError, 'uq_usuarios_correo')) {
            return 'El correo ya está registrado.';
        }

        return null;
    }
}
