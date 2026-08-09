<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/helpers/url.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';

$modulo = $_GET['modulo'] ?? 'auth';
$accion = $_GET['accion'] ?? 'index';

switch ($modulo) {
    case 'auth':
        require_once __DIR__ . '/app/controllers/AuthController.php';
        $authController = new AuthController();
        $authController->login();
        break;

    case 'dashboard':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/DashboardController.php';
        $dashboardController = new DashboardController();
        $dashboardController->index();
        break;

    case 'bienes':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/BienesController.php';
        $bienesController = new BienesController();

        if ($accion === 'crear') {
            $bienesController->crear();
        } elseif ($accion === 'ver') {
            $bienesController->ver();
        } elseif ($accion === 'editar') {
            $bienesController->editar();
        } elseif ($accion === 'cambiar_condicion') {
            $bienesController->cambiarCondicion();
        } elseif ($accion === 'generar_qr') {
            $bienesController->generarQr();
        } else {
            $bienesController->index();
        }
        break;

    case 'responsables':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/ResponsablesController.php';
        $responsablesController = new ResponsablesController();

        if ($accion === 'crear') {
            $responsablesController->crear();
        } elseif ($accion === 'ver') {
            $responsablesController->ver();
        } elseif ($accion === 'editar') {
            $responsablesController->editar();
        } elseif ($accion === 'cambiar_estado') {
            $responsablesController->cambiarEstado();
        } else {
            $responsablesController->index();
        }
        break;

    case 'ubicaciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/UbicacionesController.php';
        $ubicacionesController = new UbicacionesController();

        if ($accion === 'crear') {
            $ubicacionesController->crear();
        } elseif ($accion === 'ver') {
            $ubicacionesController->ver();
        } elseif ($accion === 'editar') {
            $ubicacionesController->editar();
        } elseif ($accion === 'cambiar_estado') {
            $ubicacionesController->cambiarEstado();
        } else {
            $ubicacionesController->index();
        }
        break;

    default:
        echo 'Módulo no encontrado';
        break;
}
