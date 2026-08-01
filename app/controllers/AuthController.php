<?php

require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('auth/login', ['error' => 'No se pudo procesar la solicitud.']);
            return;
        }

        $usuarioInput = trim($_POST['usuario'] ?? '');
        $passwordInput = $_POST['password'] ?? '';

        if ($usuarioInput === '' || $passwordInput === '') {
            $this->view('auth/login', ['error' => 'Debe completar usuario y contraseña.']);
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $usuario = $usuarioModel->findByUsuario($usuarioInput);

        if (!$usuario) {
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }

        if (($usuario['estado_usuario'] ?? '') !== 'activo') {
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }

        if (($usuario['estado_rol'] ?? '') !== 'activo') {
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }

        if (!password_verify($passwordInput, $usuario['password_hash'])) {
            $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['usuario'] = $usuario['usuario'];
        $_SESSION['nombre_rol'] = $usuario['nombre_rol'];

        header('Location: index.php?modulo=dashboard');
        exit;
    }
}

