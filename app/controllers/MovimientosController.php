<?php

require_once __DIR__ . '/../core/Controller.php';

class MovimientosController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        // El contador solo se calcula para Administrador: es el único rol que puede ver el enlace
        // de Solicitudes de baja (bandeja administrativa, BajasController::solicitudes()).
        $totalBajasPendientes = null;

        if (tieneRol(['Administrador'])) {
            $bajaModel = $this->model('Baja');
            $totalBajasPendientes = $bajaModel->contarPendientes();
        }

        $this->view('movimientos/index', [
            'totalBajasPendientes' => $totalBajasPendientes,
        ]);
    }
}
