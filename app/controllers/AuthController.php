<?php

require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/login', [], 'auth');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('auth/login', ['error' => 'No se pudo procesar la solicitud.'], 'auth');
            return;
        }

        // Mismo mecanismo CSRF ya usado en el resto del sistema (BienesController, BajasController,
        // etc.): se verifica justo después de confirmar que es POST y antes de tocar cualquier otro
        // campo de $_POST. Login era el único formulario del sistema sin esta validación.
        verifyCsrf();

        $usuarioInput = trim($_POST['usuario'] ?? '');
        $passwordInput = $_POST['password'] ?? '';

        if ($usuarioInput === '' || $passwordInput === '') {
            $this->view('auth/login', ['error' => 'Debe completar usuario y contraseña.'], 'auth');
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $usuario = $usuarioModel->findByUsuario($usuarioInput);

        if (!$usuario) {
            $this->registrarIntentoLogin(null, $usuarioInput, 'Intento de inicio de sesión con usuario inexistente.');
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.'], 'auth');
            return;
        }

        if (($usuario['estado_usuario'] ?? '') !== 'activo') {
            $this->registrarIntentoLogin((int) $usuario['id_usuario'], $usuarioInput, 'Intento de inicio de sesión con usuario inactivo.');
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.'], 'auth');
            return;
        }

        if (($usuario['estado_rol'] ?? '') !== 'activo') {
            $this->registrarIntentoLogin((int) $usuario['id_usuario'], $usuarioInput, 'Intento de inicio de sesión con rol inactivo.');
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.'], 'auth');
            return;
        }

        if (!password_verify($passwordInput, $usuario['password_hash'])) {
            $this->registrarIntentoLogin((int) $usuario['id_usuario'], $usuarioInput, 'Intento de inicio de sesión con contraseña incorrecta.');
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.'], 'auth');
            return;
        }

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['usuario'] = $usuario['usuario'];
        $_SESSION['nombre_rol'] = $usuario['nombre_rol'];

        // Ambas son acciones "best effort": una falla aquí nunca debe impedir un login ya autenticado
        // correctamente, ni dejar la sesión a medio construir — solo se registra el error internamente.
        try {
            $usuarioModel->actualizarUltimoAcceso((int) $usuario['id_usuario']);
        } catch (Throwable $e) {
            error_log('No fue posible actualizar ultimo_acceso del usuario ' . $usuario['id_usuario'] . ': ' . $e->getMessage());
        }

        $this->registrarIntentoLogin((int) $usuario['id_usuario'], null, 'Inicio de sesión exitoso.', 'exitoso');

        header('Location: index.php?modulo=dashboard');
        exit;
    }

    public function logout(): void
    {
        $idUsuario = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null;

        if ($idUsuario !== null) {
            try {
                $bitacoraModel = $this->model('Bitacora');

                $bitacoraModel->registrar(
                    idUsuario: $idUsuario,
                    accion: 'CERRAR_SESION',
                    modulo: 'Autenticación',
                    resultado: 'exitoso',
                    descripcion: 'Cierre de sesión.',
                    tablaAfectada: 'usuarios',
                    idRegistroAfectado: $idUsuario,
                    ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                    usuarioIntentado: null
                );
            } catch (Throwable $e) {
                error_log('No fue posible registrar en bitácora el cierre de sesión del usuario ' . $idUsuario . ': ' . $e->getMessage());
            }
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametrosCookie = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametrosCookie['path'],
                $parametrosCookie['domain'],
                $parametrosCookie['secure'],
                $parametrosCookie['httponly']
            );
        }

        session_destroy();

        header('Location: index.php');
        exit;
    }

    // Centraliza el registro de INICIAR_SESION (éxito o fallo) para no repetir la llamada a
    // Bitacora::registrar() en cada punto de salida de login(). $idUsuario es null cuando el usuario
    // ingresado ni siquiera existe en la tabla; en los demás casos de fallo sí se conoce el id pero el
    // login igual se rechaza. Nunca recibe ni registra la contraseña. Es "best effort": si falla,
    // queda solo en error_log — nunca debe alterar el resultado visible del login.
    private function registrarIntentoLogin(?int $idUsuario, ?string $usuarioIntentado, string $descripcion, string $resultado = 'fallido'): void
    {
        try {
            $bitacoraModel = $this->model('Bitacora');

            $bitacoraModel->registrar(
                idUsuario: $idUsuario,
                accion: 'INICIAR_SESION',
                modulo: 'Autenticación',
                resultado: $resultado,
                descripcion: $descripcion,
                tablaAfectada: 'usuarios',
                idRegistroAfectado: $idUsuario,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: $usuarioIntentado
            );
        } catch (Throwable $e) {
            error_log('No fue posible registrar en bitácora el intento de inicio de sesión: ' . $e->getMessage());
        }
    }
}
