<?php
require_once APP_ROOT . '/app/core/Controller.php';

class AuthController extends Controller
{
    private const MODULO = 'Autenticacion';

    public function login(): void
    {
        if (isLoggedIn()) {
            $this->redirect('dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->attemptLogin();
            return;
        }

        $this->view('auth/login', [
            'error' => flash('error'),
        ], 'auth');
    }

    private function attemptLogin(): void
    {
        if (!verifyCsrf()) {
            flash('error', 'Sesion de formulario invalida. Intenta de nuevo.');
            $this->redirect('auth/login');
            return;
        }

        $usuarioIngresado = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';

        /** @var Usuario $usuarioModel */
        $usuarioModel = $this->model('Usuario');
        /** @var Bitacora $bitacora */
        $bitacora = $this->model('Bitacora');

        $row = $usuarioIngresado !== '' ? $usuarioModel->findByUsuario($usuarioIngresado) : false;

        if (!$row) {
            $bitacora->registrar(
                null,
                $usuarioIngresado,
                'Inicio de sesion',
                self::MODULO,
                'fallido',
                'El usuario ingresado no existe en el sistema.',
                'usuarios',
                null
            );
            flash('error', 'Usuario o contrasena incorrectos.');
            $this->redirect('auth/login');
            return;
        }

        if ($row['estado_usuario'] !== 'activo') {
            $bitacora->registrar(
                (int) $row['id_usuario'],
                $usuarioIngresado,
                'Inicio de sesion',
                self::MODULO,
                'fallido',
                'El usuario no esta activo (estado: ' . $row['estado_usuario'] . ').',
                'usuarios',
                (int) $row['id_usuario']
            );
            flash('error', 'Tu usuario no esta activo. Contacta al administrador.');
            $this->redirect('auth/login');
            return;
        }

        if ($row['estado_rol'] !== 'activo') {
            $bitacora->registrar(
                (int) $row['id_usuario'],
                $usuarioIngresado,
                'Inicio de sesion',
                self::MODULO,
                'fallido',
                'El rol del usuario no esta activo (estado: ' . $row['estado_rol'] . ').',
                'usuarios',
                (int) $row['id_usuario']
            );
            flash('error', 'Tu rol no esta autorizado. Contacta al administrador.');
            $this->redirect('auth/login');
            return;
        }

        if (!password_verify($password, $row['password_hash'])) {
            $bitacora->registrar(
                (int) $row['id_usuario'],
                $usuarioIngresado,
                'Inicio de sesion',
                self::MODULO,
                'fallido',
                'Contrasena incorrecta.',
                'usuarios',
                (int) $row['id_usuario']
            );
            flash('error', 'Usuario o contrasena incorrectos.');
            $this->redirect('auth/login');
            return;
        }

        // Login correcto
        session_regenerate_id(true);
        $_SESSION['id_usuario']      = (int) $row['id_usuario'];
        $_SESSION['nombre_completo'] = $row['nombre_completo'];
        $_SESSION['usuario']         = $row['usuario'];
        $_SESSION['id_rol']          = (int) $row['id_rol'];
        $_SESSION['nombre_rol']      = $row['nombre_rol'];

        $usuarioModel->touchUltimoAcceso((int) $row['id_usuario']);

        $bitacora->registrar(
            (int) $row['id_usuario'],
            $usuarioIngresado,
            'Inicio de sesion',
            self::MODULO,
            'exitoso',
            'Inicio de sesion correcto.',
            'usuarios',
            (int) $row['id_usuario']
        );

        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        requireLogin();

        /** @var Bitacora $bitacora */
        $bitacora = $this->model('Bitacora');
        $bitacora->registrar(
            (int) $_SESSION['id_usuario'],
            $_SESSION['usuario'],
            'Cierre de sesion',
            self::MODULO,
            'exitoso',
            'Cierre de sesion manual.',
            'usuarios',
            (int) $_SESSION['id_usuario']
        );

        $_SESSION = [];
        session_destroy();

        $this->redirect('auth/login');
    }
}
