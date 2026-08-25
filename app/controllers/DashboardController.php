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

        // Cada bloque se aísla en su propio try/catch: si una consulta falla, el resto del Dashboard
        // (y el layout con sidebar/topbar) igual se muestra con un estado seguro en esa sección en
        // vez de dejar toda la pantalla en fatal. Nunca se expone el detalle técnico a la vista.
        $indicadores = ['bienes_registrados' => null, 'bienes_activos' => null, 'bienes_baja' => null, 'prestamos_activos' => null];
        $pendientes = ['bajas_pendientes' => null, 'prestamos_vencidos' => null, 'proximos_vencer' => null, 'bienes_sin_sicoin' => null];
        $ultimosMovimientos = [];
        $errorMovimientos = false;

        try {
            $dashboardService = $this->model('DashboardService');
            $indicadores = $dashboardService->getIndicadoresPrincipales();
            $pendientes = $dashboardService->getPendientesSupervision();
        } catch (Throwable $e) {
            error_log('No fue posible calcular los indicadores del Dashboard: ' . $e->getMessage());
        }

        try {
            $dashboardService ??= $this->model('DashboardService');
            $ultimosMovimientos = $dashboardService->getUltimosMovimientos(5);
        } catch (Throwable $e) {
            error_log('No fue posible obtener los últimos movimientos del Dashboard: ' . $e->getMessage());
            $errorMovimientos = true;
        }

        $this->view('dashboard/index', [
            'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
            'nombre_rol' => $_SESSION['nombre_rol'] ?? '',
            'usuario' => $_SESSION['usuario'] ?? '',
            'tituloPagina' => 'Panel principal',
            'indicadores' => $indicadores,
            'pendientes' => $pendientes,
            'ultimosMovimientos' => $ultimosMovimientos,
            'errorMovimientos' => $errorMovimientos,
        ], 'main');
    }
}