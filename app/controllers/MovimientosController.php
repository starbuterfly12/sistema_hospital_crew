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

        $this->view('movimientos/index', []);
    }
}
