<?php

require_once __DIR__ . '/../core/Controller.php';

class AsignacionesController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $asignacionModel = $this->model('Asignacion');

        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $asignaciones = $asignacionModel->buscar($q);
        } else {
            $asignaciones = $asignacionModel->getAll();
        }

        $this->view('asignaciones/index', [
            'asignaciones' => $asignaciones,
            'q' => $q,
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $asignacion = $asignacionModel->findById($idAsignacion);

        if ($asignacion === false) {
            echo 'Asignación no encontrada.';
            return;
        }

        $bienesAsignacion = $asignacionModel->getBienesPorAsignacion($idAsignacion);

        $this->view('asignaciones/ver', [
            'asignacion' => $asignacion,
            'bienesAsignacion' => $bienesAsignacion,
        ]);
    }

    // Las asignaciones ya NO se crean, editan, ni se les agregan/retiran bienes o confirman
    // manualmente desde este módulo (ver regla institucional definitiva): se generan y actualizan
    // automáticamente como consecuencia de Ingreso de bienes a Almacén (BienesController), Requisición
    // entregada (RequisicionesController) y Traslado (TrasladosController) — esos tres flujos siguen
    // usando internamente los métodos de escritura de Asignacion.php (crear(), agregarBien(),
    // actualizarAsignacionActual() vía Bien, etc.), que por eso NO se eliminaron del modelo. Las cinco
    // acciones HTTP manuales de abajo quedan retiradas: cualquier acceso directo por URL (GET o POST)
    // se redirige de inmediato al listado con un aviso, sin procesar ningún dato.

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->redirigirGestionManualNoDisponible();
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->redirigirGestionManualNoDisponible();
    }

    public function agregarBien(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->redirigirGestionManualNoDisponible();
    }

    public function retirarBien(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->redirigirGestionManualNoDisponible();
    }

    public function confirmar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->redirigirGestionManualNoDisponible();
    }

    private function redirigirGestionManualNoDisponible(): void
    {
        $_SESSION['mensaje_error'] = 'Las asignaciones se generan automáticamente a partir de los movimientos del sistema.';
        header('Location: index.php?modulo=asignaciones');
        exit;
    }
}