<?php

require_once __DIR__ . '/../core/Controller.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->view('dashboard/index', [
            'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
            'nombre_rol' => $_SESSION['nombre_rol'] ?? '',
            'usuario' => $_SESSION['usuario'] ?? '',
        ]);
    }
}