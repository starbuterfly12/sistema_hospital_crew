<?php

date_default_timezone_set('America/Guatemala');

// -----------------------------------------------------------------------------
// Entorno de ejecucion y visibilidad de errores (H-02)
// -----------------------------------------------------------------------------
// El entorno se declara en config/app.php -> 'env'.
//   'development' | 'local' | 'dev'  -> se MUESTRAN los errores en pantalla
//   cualquier otro valor, clave ausente o config ilegible -> NO se muestran
//     (lado seguro): incluye 'production', 'produccion', 'prod' y tambien
//     cualquier valor mal escrito, para que un descuido nunca exponga errores.
// config/app.php esta fuera de Git (credenciales locales); config/app.example.php
// lo lleva por defecto en 'development'. El entorno local funciona con normalidad
// porque su config/app.php declara 'development' de forma explicita.
$rutaConfigApp = __DIR__ . '/config/app.php';
$configApp = is_file($rutaConfigApp) ? require $rutaConfigApp : [];
$entornoApp = (is_array($configApp) && isset($configApp['env']))
    ? strtolower(trim((string) $configApp['env']))
    : '';
// Solo un valor de desarrollo EXPLICITO y bien escrito muestra errores en pantalla.
// Cualquier otro caso (clave ausente, valor desconocido, config ilegible, o los
// valores de produccion) cae en el lado seguro: errores ocultos.
$esEntornoProduccion = !in_array($entornoApp, ['development', 'local', 'dev'], true);

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', $esEntornoProduccion ? '0' : '1');
ini_set('display_startup_errors', $esEntornoProduccion ? '0' : '1');

// -----------------------------------------------------------------------------
// Endurecimiento de la cookie de sesion (H-03)
// -----------------------------------------------------------------------------
// Debe ejecutarse ANTES de session_start(). No cambia el modelo de sesion
// (sigue siendo de sesion, lifetime 0): solo fija banderas de seguridad.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// HTTPS real (conexion directa) o puerto 443. No se confia en cabeceras de proxy
// (X-Forwarded-Proto) en esta version; si el servidor institucional termina TLS
// en un proxy, se documenta ajustarlo en ese entorno controlado.
$conexionHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

// Ruta de la cookie derivada de la ubicacion real del front controller, sin
// hardcodear el nombre del proyecto:
//   XAMPP  ->  /sistema_hospital/index.php  -> path '/sistema_hospital/'
//   raiz   ->  /index.php                   -> path '/'
$directorioApp = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$rutaCookieSesion = ($directorioApp === '' || $directorioApp === '/' || $directorioApp === '.')
    ? '/'
    : rtrim($directorioApp, '/') . '/';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => $rutaCookieSesion,
    'domain' => '',
    'secure' => $conexionHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/helpers/url.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';
require_once __DIR__ . '/app/helpers/flash.php';
require_once __DIR__ . '/app/helpers/fecha.php';
require_once __DIR__ . '/app/helpers/moneda.php';
require_once __DIR__ . '/app/helpers/archivos.php';
require_once __DIR__ . '/app/helpers/exportacion.php';
require_once __DIR__ . '/app/helpers/respaldo_bd.php';
require_once __DIR__ . '/app/helpers/notificaciones.php';

$modulo = $_GET['modulo'] ?? 'auth';
$accion = $_GET['accion'] ?? 'index';

switch ($modulo) {
    case 'auth':
        require_once __DIR__ . '/app/controllers/AuthController.php';
        $authController = new AuthController();

        if ($accion === 'logout') {
            $authController->logout();
        } else {
            $authController->login();
        }
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
        } elseif ($accion === 'ver_documento') {
            $bienesController->verDocumento();
        } elseif ($accion === 'editar') {
            $bienesController->editar();
        } elseif ($accion === 'generar_qr') {
            $bienesController->generarQr();
        } elseif ($accion === 'imprimir_qr') {
            $bienesController->imprimirQr();
        } elseif ($accion === 'crear_categoria') {
            $bienesController->crearCategoria();
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

    case 'asignaciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/AsignacionesController.php';
        $asignacionesController = new AsignacionesController();

        if ($accion === 'crear') {
            $asignacionesController->crear();
        } elseif ($accion === 'ver') {
            $asignacionesController->ver();
        } elseif ($accion === 'editar') {
            $asignacionesController->editar();
        } elseif ($accion === 'agregar_bien') {
            $asignacionesController->agregarBien();
        } elseif ($accion === 'retirar_bien') {
            $asignacionesController->retirarBien();
        } elseif ($accion === 'confirmar') {
            $asignacionesController->confirmar();
        } else {
            $asignacionesController->index();
        }
        break;

    case 'tarjetas':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/TarjetasController.php';
        $tarjetasController = new TarjetasController();

        if ($accion === 'generar') {
            $tarjetasController->generar();
        } elseif ($accion === 'ver') {
            $tarjetasController->ver();
        } elseif ($accion === 'descargar_excel') {
            $tarjetasController->descargarExcel();
        } else {
            $tarjetasController->index();
        }
        break;

    case 'movimientos':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/MovimientosController.php';
        $movimientosController = new MovimientosController();
        $movimientosController->index();
        break;

    case 'traslados':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/TrasladosController.php';
        $trasladosController = new TrasladosController();

        if ($accion === 'crear') {
            $trasladosController->crear();
        } elseif ($accion === 'ver') {
            $trasladosController->ver();
        } elseif ($accion === 'descargar_constancia') {
            $trasladosController->descargarConstancia();
        } else {
            $trasladosController->index();
        }
        break;

    case 'prestamos':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/PrestamosController.php';
        $prestamosController = new PrestamosController();

        if ($accion === 'crear') {
            $prestamosController->crear();
        } elseif ($accion === 'ver') {
            $prestamosController->ver();
        } elseif ($accion === 'descargar_constancia') {
            $prestamosController->descargarConstancia();
        } else {
            $prestamosController->index();
        }
        break;

    case 'devoluciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/DevolucionesController.php';
        $devolucionesController = new DevolucionesController();

        if ($accion === 'crear') {
            $devolucionesController->crear();
        } elseif ($accion === 'ver') {
            $devolucionesController->ver();
        } else {
            $devolucionesController->index();
        }
        break;

    case 'requisiciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/RequisicionesController.php';
        $requisicionesController = new RequisicionesController();

        if ($accion === 'crear') {
            $requisicionesController->crear();
        } elseif ($accion === 'ver') {
            $requisicionesController->ver();
        } elseif ($accion === 'editar') {
            $requisicionesController->editar();
        } elseif ($accion === 'autorizar') {
            $requisicionesController->autorizar();
        } elseif ($accion === 'anular') {
            $requisicionesController->anular();
        } elseif ($accion === 'confirmar_entrega') {
            $requisicionesController->confirmarEntrega();
        } elseif ($accion === 'descargar_constancia') {
            $requisicionesController->descargarConstancia();
        } else {
            $requisicionesController->index();
        }
        break;

    case 'bajas':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/BajasController.php';
        $bajasController = new BajasController();

        if ($accion === 'crear') {
            $bajasController->crear();
        } elseif ($accion === 'ver') {
            $bajasController->ver();
        } elseif ($accion === 'ver_documento') {
            $bajasController->verDocumento();
        } elseif ($accion === 'ver_foto') {
            $bajasController->verFoto();
        } elseif ($accion === 'editar') {
            $bajasController->editar();
        } elseif ($accion === 'solicitudes') {
            $bajasController->solicitudes();
        } elseif ($accion === 'revisar') {
            $bajasController->revisar();
        } elseif ($accion === 'autorizar') {
            $bajasController->autorizar();
        } elseif ($accion === 'rechazar') {
            $bajasController->rechazar();
        } elseif ($accion === 'finalizar') {
            $bajasController->finalizar();
        } elseif ($accion === 'descargarComprobante') {
            $bajasController->descargarComprobante();
        } else {
            $bajasController->index();
        }
        break;

    case 'reportes':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/ReportesController.php';
        $reportesController = new ReportesController();

        if ($accion === 'movimientos') {
            $reportesController->movimientos();
        } elseif ($accion === 'bienesActividad') {
            $reportesController->bienesActividad();
        } elseif ($accion === 'prestamos') {
            $reportesController->prestamos();
        } elseif ($accion === 'bajas') {
            $reportesController->bajas();
        } elseif ($accion === 'verificaciones') {
            $reportesController->verificaciones();
        } elseif ($accion === 'ingresos') {
            $reportesController->ingresos();
        } elseif ($accion === 'resumen') {
            $reportesController->resumen();
        } else {
            $reportesController->index();
        }
        break;

    case 'usuarios':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/UsuariosController.php';
        $usuariosController = new UsuariosController();

        if ($accion === 'ver') {
            $usuariosController->ver();
        } elseif ($accion === 'crear') {
            $usuariosController->crear();
        } elseif ($accion === 'guardar') {
            $usuariosController->guardar();
        } elseif ($accion === 'editar') {
            $usuariosController->editar();
        } elseif ($accion === 'actualizar') {
            $usuariosController->actualizar();
        } elseif ($accion === 'cambiarEstado') {
            $usuariosController->cambiarEstado();
        } elseif ($accion === 'cambiarPassword') {
            $usuariosController->cambiarPassword();
        } else {
            $usuariosController->index();
        }
        break;

    case 'notificaciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/NotificacionesController.php';
        $notificacionesController = new NotificacionesController();

        if ($accion === 'abrir') {
            $notificacionesController->abrir();
        } elseif ($accion === 'marcar_todas') {
            $notificacionesController->marcarTodas();
        } else {
            header('Location: index.php?modulo=dashboard');
            exit;
        }
        break;

    case 'bitacora':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/BitacoraController.php';
        $bitacoraController = new BitacoraController();

        if ($accion === 'ver') {
            $bitacoraController->ver();
        } else {
            $bitacoraController->index();
        }
        break;

    case 'verificaciones':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/VerificacionesController.php';
        $verificacionesController = new VerificacionesController();

        if ($accion === 'crear') {
            $verificacionesController->crear();
        } elseif ($accion === 'ver') {
            $verificacionesController->ver();
        } else {
            $verificacionesController->index();
        }
        break;

    case 'respaldos':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/app/controllers/RespaldosController.php';
        $respaldosController = new RespaldosController();

        if ($accion === 'generar') {
            $respaldosController->generar();
        } elseif ($accion === 'descargar') {
            $respaldosController->descargar();
        } else {
            $respaldosController->index();
        }
        break;

    default:
        echo 'Módulo no encontrado';
        break;
}
