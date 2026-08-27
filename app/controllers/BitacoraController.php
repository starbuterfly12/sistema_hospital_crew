<?php

require_once __DIR__ . '/../core/Controller.php';

// Controlador del módulo Bitácora — acceso exclusivo al rol Administrador. Módulo de SOLO LECTURA:
// no existe ninguna acción de escritura aquí (Bitacora::registrar() sigue siendo responsabilidad de
// cada módulo que la llama). Ver auditoría M12-A para el detalle de las decisiones funcionales.
class BitacoraController extends Controller
{
    private const POR_PAGINA = 25;

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        $bitacoraModel = $this->model('Bitacora');

        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        $buscar = trim($_GET['buscar'] ?? '');
        // 'filtro_modulo', no 'modulo': ese nombre ya está tomado por el router (?modulo=bitacora).
        $modulo = trim($_GET['filtro_modulo'] ?? '');
        $resultado = in_array($_GET['resultado'] ?? '', ['exitoso', 'fallido'], true) ? $_GET['resultado'] : '';

        $error = null;

        if ($fechaDesde !== '' && !isValidIsoDate($fechaDesde)) {
            $error = 'La fecha "desde" no es válida.';
        } elseif ($fechaHasta !== '' && !isValidIsoDate($fechaHasta)) {
            $error = 'La fecha "hasta" no es válida.';
        } elseif ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
            $error = 'La fecha "desde" no puede ser posterior a la fecha "hasta".';
        }

        $modulosDisponibles = $bitacoraModel->getModulosDistinct();

        $filtros = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'buscar' => $buscar,
            'modulo' => $modulo,
            'resultado' => $resultado,
        ];

        if ($error !== null) {
            $this->view('bitacora/index', [
                'registros' => [],
                'error' => $error,
                'filtros' => $filtros,
                'modulos' => $modulosDisponibles,
                'pagina' => 1,
                'totalPaginas' => 1,
                'total' => 0,
                'tituloPagina' => 'Bitácora',
            ], 'main');
            return;
        }

        $pagina = (int) ($_GET['pagina'] ?? 1);
        if ($pagina < 1) {
            $pagina = 1;
        }

        $total = $bitacoraModel->contar($filtros);
        $totalPaginas = $total > 0 ? (int) ceil($total / self::POR_PAGINA) : 1;

        // Página fuera de rango: se ajusta silenciosamente a la última página válida en vez de
        // generar un error o una redirección — nunca puede quedar vacía "por accidente" mientras
        // existan resultados.
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $registros = $total > 0 ? $bitacoraModel->getAll($filtros, $pagina, self::POR_PAGINA) : [];

        $this->view('bitacora/index', [
            'registros' => $registros,
            'error' => null,
            'filtros' => $filtros,
            'modulos' => $modulosDisponibles,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'tituloPagina' => 'Bitácora',
        ], 'main');
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        $idBitacora = (int) ($_GET['id'] ?? 0);

        if ($idBitacora <= 0) {
            http_response_code(404);
            echo 'Registro no válido.';
            return;
        }

        $bitacoraModel = $this->model('Bitacora');
        $registro = $bitacoraModel->findById($idBitacora);

        if ($registro === false) {
            http_response_code(404);
            echo 'Registro no encontrado.';
            return;
        }

        $this->view('bitacora/ver', [
            'registro' => $registro,
            'tituloPagina' => 'Detalle de bitácora',
        ], 'main');
    }
}
