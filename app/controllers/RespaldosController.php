<?php

require_once __DIR__ . '/../core/Controller.php';

// Controlador del módulo Respaldos — acceso exclusivo al rol Administrador. Solo coordina: la
// lógica de mysqldump/proc_open vive en app/helpers/respaldo_bd.php. Alcance de esta versión:
// generar + listar + descargar. No hay restaurar ni eliminar (ver auditoría/decisiones M13-A/M13-B).
class RespaldosController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        $respaldoModel = $this->model('Respaldo');
        $respaldos = $respaldoModel->getAll();

        $this->view('respaldos/index', [
            'respaldos' => $respaldos,
            'tituloPagina' => 'Respaldos',
        ], 'main');
    }

    public function generar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?modulo=respaldos');
            exit;
        }

        verifyCsrf();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $ipOrigen = $_SERVER['REMOTE_ADDR'] ?? null;
        $bitacoraModel = $this->model('Bitacora');

        $resultado = generarArchivoRespaldoBaseDatos();

        if (!$resultado['exito']) {
            $bitacoraModel->registrar(
                idUsuario: $idUsuario,
                accion: 'GENERAR_RESPALDO',
                modulo: 'Respaldos',
                resultado: 'fallido',
                descripcion: 'No fue posible generar el respaldo de la base de datos.',
                tablaAfectada: 'respaldos',
                idRegistroAfectado: null,
                ipOrigen: $ipOrigen,
                usuarioIntentado: null
            );

            setFlash('error', 'No fue posible generar el respaldo', 'No fue posible generar el respaldo. Intente nuevamente.');
            header('Location: index.php?modulo=respaldos');
            exit;
        }

        $respaldoModel = $this->model('Respaldo');

        $idRespaldo = $respaldoModel->registrar([
            'tipo_respaldo' => 'base_datos',
            'nombre_archivo' => $resultado['nombre_archivo'],
            'ruta_archivo' => $resultado['ruta_relativa'],
            'estado_respaldo' => 'generado',
            'id_usuario_genera' => $idUsuario,
            'observaciones' => null,
        ]);

        if ($idRespaldo === false) {
            eliminarArchivoRespaldoFisico($resultado['ruta_relativa']);

            $bitacoraModel->registrar(
                idUsuario: $idUsuario,
                accion: 'GENERAR_RESPALDO',
                modulo: 'Respaldos',
                resultado: 'fallido',
                descripcion: 'No fue posible generar el respaldo de la base de datos.',
                tablaAfectada: 'respaldos',
                idRegistroAfectado: null,
                ipOrigen: $ipOrigen,
                usuarioIntentado: null
            );

            setFlash('error', 'No fue posible generar el respaldo', 'No fue posible generar el respaldo. Intente nuevamente.');
            header('Location: index.php?modulo=respaldos');
            exit;
        }

        $bitacoraModel->registrar(
            idUsuario: $idUsuario,
            accion: 'GENERAR_RESPALDO',
            modulo: 'Respaldos',
            resultado: 'exitoso',
            descripcion: 'Se generó un respaldo de la base de datos.',
            tablaAfectada: 'respaldos',
            idRegistroAfectado: $idRespaldo,
            ipOrigen: $ipOrigen,
            usuarioIntentado: null
        );

        setFlash('success', 'Respaldo generado correctamente', 'El respaldo de la base de datos fue creado correctamente.');
        header('Location: index.php?modulo=respaldos');
        exit;
    }

    public function descargar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        // Descarga = acción explícita del usuario que además escribe en Bitácora y entrega un archivo
        // sensible: solo POST + CSRF. Un GET a esta acción (enlace embebido, prefetch, <img>, etc.) se
        // rechaza con 405 SIN descargar ni registrar nada en Bitácora.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idRespaldo = (int) ($_POST['id'] ?? 0);

        if ($idRespaldo <= 0) {
            http_response_code(404);
            echo 'Respaldo no válido.';
            return;
        }

        $respaldoModel = $this->model('Respaldo');
        $respaldo = $respaldoModel->findById($idRespaldo);

        if ($respaldo === false) {
            http_response_code(404);
            echo 'Respaldo no encontrado.';
            return;
        }

        $rutaFisica = resolverRutaFisicaRespaldo($respaldo['ruta_archivo']);

        if ($rutaFisica === null || !is_file($rutaFisica) || !is_readable($rutaFisica)) {
            http_response_code(404);
            echo 'El archivo de respaldo no está disponible.';
            return;
        }

        $bitacoraModel = $this->model('Bitacora');
        $bitacoraModel->registrar(
            idUsuario: (int) $_SESSION['id_usuario'],
            accion: 'DESCARGAR_RESPALDO',
            modulo: 'Respaldos',
            resultado: 'exitoso',
            descripcion: 'Se descargó un respaldo de la base de datos.',
            tablaAfectada: 'respaldos',
            idRegistroAfectado: $idRespaldo,
            ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
            usuarioIntentado: null
        );

        $nombreDescarga = basename($respaldo['nombre_archivo']);

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($rutaFisica));
        header('Cache-Control: no-store');

        readfile($rutaFisica);
        exit;
    }
}
