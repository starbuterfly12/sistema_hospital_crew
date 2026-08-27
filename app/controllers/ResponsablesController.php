<?php

require_once __DIR__ . '/../core/Controller.php';

class ResponsablesController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $responsableModel = $this->model('Responsable');

        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $responsables = $responsableModel->buscar($q);
        } else {
            $responsables = $responsableModel->getAll();
        }

        $this->view('responsables/index', [
            'responsables' => $responsables,
            'q' => $q,
            'tituloPagina' => 'Responsables',
        ], 'main');
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idResponsable = (int) ($_GET['id'] ?? 0);

        if ($idResponsable <= 0) {
            echo 'Responsable no válido.';
            return;
        }

        $responsableModel = $this->model('Responsable');
        $responsable = $responsableModel->findById($idResponsable);

        if ($responsable === false) {
            echo 'Responsable no encontrado.';
            return;
        }

        $this->view('responsables/ver', [
            'responsable' => $responsable,
            'tituloPagina' => 'Detalle del responsable',
        ], 'main');
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        $ubicaciones = $ubicacionModel->getActivas();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('responsables/crear', [
                'ubicaciones' => $ubicaciones,
                'error' => null,
                'datosFormulario' => [],
                'tituloPagina' => 'Registrar responsable',
            ], 'main');
            return;
        }

        verifyCsrf();

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $profesion = trim($_POST['profesion'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $idUbicacion = (int) ($_POST['id_ubicacion'] ?? 0);

        $datosFormulario = [
            'nombre_completo' => $nombreCompleto,
            'cargo' => $cargo,
            'profesion' => $profesion,
            'nit' => $nit,
            'telefono' => $telefono,
            'id_ubicacion' => $idUbicacion,
        ];

        $error = null;

        if ($nombreCompleto === '') {
            $error = 'El nombre completo es obligatorio.';
        } elseif ($cargo === '') {
            $error = 'El cargo es obligatorio.';
        } elseif ($nit === '') {
            $error = 'El NIT es obligatorio.';
        } elseif (strlen($nit) > 20) {
            $error = 'El NIT no puede superar los 20 caracteres.';
        } elseif ($responsableModel->existeNit($nit)) {
            $error = 'El NIT ingresado ya está registrado.';
        } elseif ($telefono !== '' && strlen($telefono) > 20) {
            $error = 'El teléfono no puede superar los 20 caracteres.';
        } elseif ($idUbicacion <= 0) {
            $error = 'Debe seleccionar una ubicación.';
        } else {
            $ubicacionValida = false;
            foreach ($ubicaciones as $ubicacion) {
                if ((int) $ubicacion['id_ubicacion'] === $idUbicacion) {
                    $ubicacionValida = true;
                    break;
                }
            }

            if (!$ubicacionValida) {
                $error = 'La ubicación seleccionada no es válida.';
            }
        }

        if ($error !== null) {
            $this->view('responsables/crear', [
                'ubicaciones' => $ubicaciones,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar responsable',
            ], 'main');
            return;
        }

        $datos = [
            'nombre_completo' => $nombreCompleto,
            'cargo' => $cargo,
            'profesion' => $profesion !== '' ? $profesion : null,
            'nit' => $nit,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id_ubicacion' => $idUbicacion,
            'estado_responsable' => 'activo',
        ];

        try {
            $responsableModel->beginTransaction();

            $idResponsable = $responsableModel->crear($datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_RESPONSABLE',
                modulo: 'Responsables',
                resultado: 'exitoso',
                descripcion: 'Se registró el responsable con NIT ' . $nit . ' (' . $nombreCompleto . ').',
                tablaAfectada: 'responsables',
                idRegistroAfectado: $idResponsable,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $responsableModel->commit();

            setFlash('success', 'Responsable registrado correctamente', 'La información del responsable fue guardada en el sistema.');

            header('Location: index.php?modulo=responsables&accion=ver&id=' . $idResponsable);
            exit;
        } catch (Throwable $e) {
            if ($responsableModel->inTransaction()) {
                $responsableModel->rollBack();
            }

            error_log('Error al registrar el responsable: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible registrar el responsable. Verifique los datos e intente nuevamente.';

            $this->view('responsables/crear', [
                'ubicaciones' => $ubicaciones,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar responsable',
            ], 'main');
            return;
        }
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $idResponsable = (int) ($_GET['id'] ?? 0);

        if ($idResponsable <= 0) {
            echo 'Responsable no válido.';
            return;
        }

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        $responsable = $responsableModel->findById($idResponsable);

        if ($responsable === false) {
            echo 'Responsable no encontrado.';
            return;
        }

        $ubicaciones = $ubicacionModel->getActivas();

        $idUbicacionOriginal = (int) $responsable['id_ubicacion'];

        $ubicacionesFormulario = $ubicaciones;

        $ubicacionOriginalPresente = false;
        foreach ($ubicaciones as $ubicacion) {
            if ((int) $ubicacion['id_ubicacion'] === $idUbicacionOriginal) {
                $ubicacionOriginalPresente = true;
                break;
            }
        }

        if (!$ubicacionOriginalPresente) {
            $ubicacionesFormulario[] = [
                'id_ubicacion' => $idUbicacionOriginal,
                'nombre_ubicacion' => $responsable['nombre_ubicacion'],
                'tipo_ubicacion' => $responsable['tipo_ubicacion'],
                'es_inactiva' => true,
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('responsables/editar', [
                'responsable' => $responsable,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => null,
                'datosFormulario' => $responsable,
                'tituloPagina' => 'Modificar responsable',
            ], 'main');
            return;
        }

        verifyCsrf();

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $profesion = trim($_POST['profesion'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $idUbicacion = (int) ($_POST['id_ubicacion'] ?? 0);

        $datosFormulario = [
            'nombre_completo' => $nombreCompleto,
            'cargo' => $cargo,
            'profesion' => $profesion,
            'nit' => $nit,
            'telefono' => $telefono,
            'id_ubicacion' => $idUbicacion,
        ];

        $error = null;

        if ($nombreCompleto === '') {
            $error = 'El nombre completo es obligatorio.';
        } elseif ($cargo === '') {
            $error = 'El cargo es obligatorio.';
        } elseif ($nit === '') {
            $error = 'El NIT es obligatorio.';
        } elseif (strlen($nit) > 20) {
            $error = 'El NIT no puede superar los 20 caracteres.';
        } elseif ($responsableModel->existeNit($nit, $idResponsable)) {
            $error = 'El NIT ingresado ya está registrado.';
        } elseif ($telefono !== '' && strlen($telefono) > 20) {
            $error = 'El teléfono no puede superar los 20 caracteres.';
        } elseif ($idUbicacion <= 0) {
            $error = 'Debe seleccionar una ubicación.';
        } else {
            $ubicacionValida = false;
            foreach ($ubicaciones as $ubicacion) {
                if ((int) $ubicacion['id_ubicacion'] === $idUbicacion) {
                    $ubicacionValida = true;
                    break;
                }
            }

            if (!$ubicacionValida && $idUbicacion === $idUbicacionOriginal) {
                $ubicacionValida = true;
            }

            if (!$ubicacionValida) {
                $error = 'La ubicación seleccionada no es válida.';
            }
        }

        if ($error !== null) {
            $this->view('responsables/editar', [
                'responsable' => $responsable,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Modificar responsable',
            ], 'main');
            return;
        }

        $datos = [
            'nombre_completo' => $nombreCompleto,
            'cargo' => $cargo,
            'profesion' => $profesion !== '' ? $profesion : null,
            'nit' => $nit,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id_ubicacion' => $idUbicacion,
            'estado_responsable' => $responsable['estado_responsable'],
        ];

        try {
            $responsableModel->beginTransaction();

            $responsableModel->actualizar($idResponsable, $datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_RESPONSABLE',
                modulo: 'Responsables',
                resultado: 'exitoso',
                descripcion: 'Se modificó la información del responsable con NIT ' . $nit . '.',
                tablaAfectada: 'responsables',
                idRegistroAfectado: $idResponsable,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $responsableModel->commit();

            setFlash('success', 'Cambios guardados correctamente', 'La información del responsable fue actualizada.');

            header('Location: index.php?modulo=responsables&accion=ver&id=' . $idResponsable);
            exit;
        } catch (Throwable $e) {
            if ($responsableModel->inTransaction()) {
                $responsableModel->rollBack();
            }

            error_log('Error al actualizar el responsable: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible actualizar el responsable. Verifique los datos e intente nuevamente.';

            $this->view('responsables/editar', [
                'responsable' => $responsable,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Modificar responsable',
            ], 'main');
            return;
        }
    }

    public function cambiarEstado(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idResponsable = (int) ($_GET['id'] ?? 0);

        if ($idResponsable <= 0) {
            echo 'Responsable no válido.';
            return;
        }

        $responsableModel = $this->model('Responsable');
        $bitacoraModel = $this->model('Bitacora');

        $responsable = $responsableModel->findById($idResponsable);

        if ($responsable === false) {
            echo 'Responsable no encontrado.';
            return;
        }

        $estadoAnterior = $responsable['estado_responsable'];

        if ($estadoAnterior === 'activo') {
            $estadoNuevo = 'inactivo';
        } elseif ($estadoAnterior === 'inactivo') {
            $estadoNuevo = 'activo';
        } else {
            echo 'Estado del responsable no válido.';
            return;
        }

        try {
            $responsableModel->beginTransaction();

            $responsableModel->cambiarEstado($idResponsable, $estadoNuevo);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CAMBIAR_ESTADO_RESPONSABLE',
                modulo: 'Responsables',
                resultado: 'exitoso',
                descripcion:
                    'Cambio de estado: ' .
                    $estadoAnterior .
                    ' → ' .
                    $estadoNuevo .
                    ' (NIT ' .
                    $responsable['nit'] .
                    ').',
                tablaAfectada: 'responsables',
                idRegistroAfectado: $idResponsable,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $responsableModel->commit();

            setFlash('success', 'Estado actualizado correctamente', 'El estado del responsable fue actualizado.');

            header('Location: index.php?modulo=responsables&accion=ver&id=' . $idResponsable);
            exit;
        } catch (Throwable $e) {
            if ($responsableModel->inTransaction()) {
                $responsableModel->rollBack();
            }

            error_log('Error al cambiar el estado del responsable: ' . $e->getMessage());

            echo 'No fue posible cambiar el estado del responsable. Intente nuevamente.';
            return;
        }
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

        if (str_contains($detalleError, 'uq_responsables_nit')) {
            return 'El NIT ingresado ya está registrado.';
        }

        return null;
    }
}
