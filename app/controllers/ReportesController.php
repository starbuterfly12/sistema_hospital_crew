<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ReportesService.php';
require_once __DIR__ . '/../helpers/exportacion.php';
require_once __DIR__ . '/../helpers/exportacion_pdf.php';

// Controlador delgado: cada acción solo lee/valida filtros, llama a ReportesService, y decide entre
// mostrar la vista web o exportar a Excel (según $_GET['formato']) usando el helper de exportación
// común. Ninguna consulta ni normalización vive aquí — todo eso está en ReportesService.
class ReportesController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $this->view('reportes/index', [
            'tituloPagina' => 'Reportes',
        ], 'main');
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 1 — Movimientos por período
    // ---------------------------------------------------------------------------------------
    public function movimientos(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        [$fechaDesde, $fechaHasta, $error] = $this->resolverRangoFechas();

        // Ubicación/usuario se quitaron del formulario visible (revisión de usabilidad: quedaban
        // amontonados sin aportar suficiente valor frente a Tipo/Responsable). El service SIGUE
        // soportando id_ubicacion/id_usuario internamente (ver getMovimientosPorPeriodo()) por si se
        // reutiliza en otro lugar más adelante — aquí simplemente no se leen del GET.
        $filtros = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'tipo' => in_array($_GET['tipo'] ?? '', ReportesService::TIPOS_EVENTO, true) ? $_GET['tipo'] : '',
            'id_responsable' => (int) ($_GET['id_responsable'] ?? 0),
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getMovimientosPorPeriodo($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarMovimientos($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarMovimientosPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/movimientos', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'responsables' => $this->model('Responsable')->getActivos(),
            'tituloPagina' => 'Reporte: Movimientos por período',
        ], 'main');
    }

    private function exportarMovimientos(array $filas, array $filtros): void
    {
        // Excel compacto (revisión de impresión): se quitan SICOIN y Usuario — ya identificamos el
        // bien por "No. de Bien" y el Detalle ya resume la operación; ambas columnas siguen
        // disponibles en la vista web, que no tiene esta restricción de ancho de impresión.
        $columnas = [
            ['clave' => 'fecha', 'titulo' => 'Fecha', 'tipo' => 'fecha_variable', 'ancho' => 13],
            ['clave' => 'tipo', 'titulo' => 'Tipo', 'tipo' => 'texto', 'ancho' => 13],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 26],
            ['clave' => 'responsable', 'titulo' => 'Responsable relacionado', 'tipo' => 'texto', 'ancho' => 22],
            ['clave' => 'ubicacion', 'titulo' => 'Ubicación relacionada', 'tipo' => 'texto', 'ancho' => 20],
            ['clave' => 'resumen', 'titulo' => 'Detalle', 'tipo' => 'texto', 'ancho' => 30],
        ];

        // 'fecha' pasa a ser un valor compuesto para que el Excel respete la misma distinción
        // DATE/DATETIME que la vista web (ver construirReporteExcel()/'fecha_variable').
        foreach ($filas as &$fila) {
            $fila['fecha'] = ['valor' => $fila['fecha'], 'es_datetime' => $fila['fecha_es_datetime']];
        }
        unset($fila);

        $meta = [$this->lineaPeriodo($filtros)];
        if ($filtros['tipo'] !== '') {
            $meta[] = 'Tipo: ' . $filtros['tipo'];
        }
        if (!empty($filtros['id_responsable'])) {
            $meta[] = 'Responsable: ' . $this->nombreResponsablePorId((int) $filtros['id_responsable']);
        }

        $spreadsheet = construirReporteExcel(
            'Movimientos por período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape'
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('Movimientos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    private function exportarMovimientosPdf(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'fecha', 'titulo' => 'Fecha', 'tipo' => 'fecha_variable', 'ancho' => 8],
            ['clave' => 'tipo', 'titulo' => 'Tipo', 'tipo' => 'texto', 'ancho' => 9],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 9],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 20],
            ['clave' => 'responsable', 'titulo' => 'Responsable relacionado', 'tipo' => 'texto', 'ancho' => 17],
            ['clave' => 'ubicacion', 'titulo' => 'Ubicación relacionada', 'tipo' => 'texto', 'ancho' => 15],
            ['clave' => 'resumen', 'titulo' => 'Detalle', 'tipo' => 'texto', 'ancho' => 22],
        ];

        foreach ($filas as &$fila) {
            $fila['fecha'] = ['valor' => $fila['fecha'], 'es_datetime' => $fila['fecha_es_datetime']];
        }
        unset($fila);

        $meta = [$this->lineaPeriodo($filtros)];
        if ($filtros['tipo'] !== '') {
            $meta[] = 'Tipo: ' . $filtros['tipo'];
        }
        if (!empty($filtros['id_responsable'])) {
            $meta[] = 'Responsable: ' . $this->nombreResponsablePorId((int) $filtros['id_responsable']);
        }

        $dompdf = construirPdfReporte('Movimientos por período', $columnas, $filas, $meta, $this->nombreUsuarioActual());

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('Movimientos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 2 — Bienes con actividad en un período
    // ---------------------------------------------------------------------------------------
    public function bienesActividad(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        [$fechaDesde, $fechaHasta, $error] = $this->resolverRangoFechas();

        $filtros = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'tipo' => in_array($_GET['tipo'] ?? '', ReportesService::TIPOS_EVENTO, true) ? $_GET['tipo'] : '',
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getBienesConActividad($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarBienesActividad($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarBienesActividadPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/bienes_actividad', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'tituloPagina' => 'Reporte: Bienes con actividad',
        ], 'main');
    }

    private function exportarBienesActividad(array $filas, array $filtros): void
    {
        // Excel compacto: se quita SICOIN (sigue disponible en la vista web).
        $columnas = [
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 26],
            ['clave' => 'eventos', 'titulo' => 'Cant. eventos', 'tipo' => 'entero', 'ancho' => 12],
            ['clave' => 'primer_evento', 'titulo' => 'Primer evento', 'tipo' => 'fecha_variable', 'ancho' => 13],
            ['clave' => 'ultimo_evento', 'titulo' => 'Último evento', 'tipo' => 'fecha_variable', 'ancho' => 13],
            ['clave' => 'tipos', 'titulo' => 'Tipos de actividad', 'tipo' => 'texto', 'ancho' => 26],
        ];

        foreach ($filas as &$fila) {
            $fila['primer_evento'] = ['valor' => $fila['primer_evento'], 'es_datetime' => $fila['primer_evento_es_datetime']];
            $fila['ultimo_evento'] = ['valor' => $fila['ultimo_evento'], 'es_datetime' => $fila['ultimo_evento_es_datetime']];
        }
        unset($fila);

        $meta = [$this->lineaPeriodo($filtros)];
        if ($filtros['tipo'] !== '') {
            $meta[] = 'Tipo de actividad: ' . $filtros['tipo'];
        }

        $spreadsheet = construirReporteExcel(
            'Bienes con actividad en el período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape' // el helper ahora siempre imprime horizontal; ver revisión de impresión
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('BienesConActividad', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    private function exportarBienesActividadPdf(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 26],
            ['clave' => 'eventos', 'titulo' => 'Cant. eventos', 'tipo' => 'entero', 'ancho' => 10],
            ['clave' => 'primer_evento', 'titulo' => 'Primer evento', 'tipo' => 'fecha_variable', 'ancho' => 12],
            ['clave' => 'ultimo_evento', 'titulo' => 'Último evento', 'tipo' => 'fecha_variable', 'ancho' => 12],
            ['clave' => 'tipos', 'titulo' => 'Tipos de actividad', 'tipo' => 'texto', 'ancho' => 30],
        ];

        foreach ($filas as &$fila) {
            $fila['primer_evento'] = ['valor' => $fila['primer_evento'], 'es_datetime' => $fila['primer_evento_es_datetime']];
            $fila['ultimo_evento'] = ['valor' => $fila['ultimo_evento'], 'es_datetime' => $fila['ultimo_evento_es_datetime']];
        }
        unset($fila);

        $meta = [$this->lineaPeriodo($filtros)];
        if ($filtros['tipo'] !== '') {
            $meta[] = 'Tipo de actividad: ' . $filtros['tipo'];
        }

        $dompdf = construirPdfReporte('Bienes con actividad en el período', $columnas, $filas, $meta, $this->nombreUsuarioActual());

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('BienesConActividad', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 5 — Préstamos pendientes o vencidos
    // ---------------------------------------------------------------------------------------
    public function prestamos(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
        $error = $this->validarRangoOpcional($fechaDesde, $fechaHasta);

        $filtros = [
            'fecha_desde' => $fechaDesde !== '' && $error === null ? $fechaDesde : '',
            'fecha_hasta' => $fechaHasta !== '' && $error === null ? $fechaHasta : '',
            'estado' => in_array($_GET['estado'] ?? '', ['activo', 'parcial'], true) ? $_GET['estado'] : '',
            'id_responsable_destino' => (int) ($_GET['id_responsable'] ?? 0),
            'vencido' => in_array($_GET['vencido'] ?? '', ['0', '1'], true) ? $_GET['vencido'] : '',
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getPrestamosPendientesVencidos($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarPrestamos($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarPrestamosPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/prestamos', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'responsables' => $this->model('Responsable')->getActivos(),
            'tituloPagina' => 'Reporte: Préstamos pendientes o vencidos',
        ], 'main');
    }

    private function exportarPrestamos(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'numero_prestamo', 'titulo' => 'No. préstamo', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'responsable_destino_mostrado', 'titulo' => 'Receptor', 'tipo' => 'texto', 'ancho' => 22],
            ['clave' => 'fecha_prestamo', 'titulo' => 'Fecha préstamo', 'tipo' => 'fecha', 'ancho' => 13],
            ['clave' => 'fecha_devolucion_estimada', 'titulo' => 'Fecha prevista', 'tipo' => 'fecha', 'ancho' => 13],
            ['clave' => 'estado_prestamo', 'titulo' => 'Estado', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'bienes_pendientes', 'titulo' => 'Bienes pendientes', 'tipo' => 'entero', 'ancho' => 12],
            ['clave' => 'vencido_texto', 'titulo' => 'Vencido', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'dias_vencido', 'titulo' => 'Días vencidos', 'tipo' => 'entero', 'ancho' => 12],
        ];

        foreach ($filas as &$fila) {
            $fila['vencido_texto'] = $fila['vencido'] ? 'Sí' : 'No';
        }
        unset($fila);

        $meta = [];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        if (!empty($filtros['estado'])) {
            $meta[] = 'Estado: ' . ucfirst($filtros['estado']);
        }
        if (!empty($filtros['id_responsable_destino'])) {
            $meta[] = 'Receptor: ' . $this->nombreResponsablePorId((int) $filtros['id_responsable_destino']);
        }
        if (isset($filtros['vencido']) && $filtros['vencido'] !== '') {
            $meta[] = 'Vencido: ' . ($filtros['vencido'] === '1' ? 'Sí' : 'No');
        }

        $spreadsheet = construirReporteExcel(
            'Préstamos pendientes o vencidos',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape' // el helper ahora siempre imprime horizontal; ver revisión de impresión
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('PrestamosPendientes', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    private function exportarPrestamosPdf(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'numero_prestamo', 'titulo' => 'No. préstamo', 'tipo' => 'texto', 'ancho' => 13],
            ['clave' => 'responsable_destino_mostrado', 'titulo' => 'Receptor', 'tipo' => 'texto', 'ancho' => 23],
            ['clave' => 'fecha_prestamo', 'titulo' => 'Fecha préstamo', 'tipo' => 'fecha', 'ancho' => 12],
            ['clave' => 'fecha_devolucion_estimada', 'titulo' => 'Fecha prevista', 'tipo' => 'fecha', 'ancho' => 12],
            ['clave' => 'estado_prestamo', 'titulo' => 'Estado', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'bienes_pendientes', 'titulo' => 'Bienes pendientes', 'tipo' => 'entero', 'ancho' => 10],
            ['clave' => 'vencido_texto', 'titulo' => 'Vencido', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'dias_vencido', 'titulo' => 'Días vencidos', 'tipo' => 'entero', 'ancho' => 10],
        ];

        foreach ($filas as &$fila) {
            $fila['vencido_texto'] = $fila['vencido'] ? 'Sí' : 'No';
        }
        unset($fila);

        $meta = [];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        if (!empty($filtros['estado'])) {
            $meta[] = 'Estado: ' . ucfirst($filtros['estado']);
        }
        if (!empty($filtros['id_responsable_destino'])) {
            $meta[] = 'Receptor: ' . $this->nombreResponsablePorId((int) $filtros['id_responsable_destino']);
        }
        if (isset($filtros['vencido']) && $filtros['vencido'] !== '') {
            $meta[] = 'Vencido: ' . ($filtros['vencido'] === '1' ? 'Sí' : 'No');
        }

        $dompdf = construirPdfReporte('Préstamos pendientes o vencidos', $columnas, $filas, $meta, $this->nombreUsuarioActual());

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('PrestamosPendientes', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 6 — Bajas por período
    // ---------------------------------------------------------------------------------------
    public function bajas(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
        $error = $this->validarRangoOpcional($fechaDesde, $fechaHasta);

        // Responsable anterior y Bodega destino se quitaron del formulario visible (revisión de
        // usabilidad). El service sigue soportando ambos filtros internamente.
        $filtros = [
            'fecha_desde' => $error === null ? $fechaDesde : '',
            'fecha_hasta' => $error === null ? $fechaHasta : '',
            'estado' => in_array($_GET['estado'] ?? 'finalizada', ['pendiente', 'autorizada', 'rechazada', 'finalizada', ''], true)
                ? ($_GET['estado'] ?? 'finalizada')
                : 'finalizada',
            'id_ubicacion_anterior' => (int) ($_GET['id_ubicacion_anterior'] ?? 0),
            'id_tipo_baja' => (int) ($_GET['id_tipo_baja'] ?? 0),
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getBajasPorPeriodo($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarBajas($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarBajasPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/bajas', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'ubicaciones' => $this->model('Ubicacion')->getActivas(),
            'tiposBaja' => $this->model('TipoBaja')->getActivos(),
            'tituloPagina' => 'Reporte: Bajas por período',
        ], 'main');
    }

    private function exportarBajas(array $filas, array $filtros): void
    {
        // Excel compacto: se quita SICOIN, y las 3 fechas (preparación/autorización/baja) se
        // consolidan en UNA sola columna "fecha funcional" — la misma regla que ya usa el filtro
        // (fecha_baja cuando el estado es Finalizada, fecha_preparacion en cualquier otro caso,
        // porque fecha_baja todavía no existe ahí). Mantiene la distinción DATE/DATETIME real:
        // fecha_baja es solo DATE, fecha_preparacion es DATETIME real.
        $esFinalizada = $filtros['estado'] === 'finalizada';
        $tituloColumnaFecha = $esFinalizada ? 'Fecha de baja' : 'Fecha de trámite';
        $tipoColumnaFecha = $esFinalizada ? 'fecha' : 'fecha_hora';

        foreach ($filas as &$fila) {
            $fila['fecha_funcional'] = $esFinalizada ? $fila['fecha_baja'] : $fila['fecha_preparacion'];
        }
        unset($fila);

        $columnas = [
            ['clave' => 'numero_baja', 'titulo' => 'No. Baja', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'fecha_funcional', 'titulo' => $tituloColumnaFecha, 'tipo' => $tipoColumnaFecha, 'ancho' => 13],
            ['clave' => 'estado_baja', 'titulo' => 'Estado', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'codigo_interno_mostrado', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'descripcion_mostrada', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 24],
            ['clave' => 'responsable_anterior', 'titulo' => 'Responsable anterior', 'tipo' => 'texto', 'ancho' => 20],
            ['clave' => 'ubicacion_anterior', 'titulo' => 'Servicio', 'tipo' => 'texto', 'ancho' => 18],
            ['clave' => 'nombre_tipo_baja', 'titulo' => 'Tipo de baja', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'valor_mostrado', 'titulo' => 'Valor', 'tipo' => 'moneda', 'ancho' => 13],
            ['clave' => 'bodega_destino', 'titulo' => 'Bodega destino', 'tipo' => 'texto', 'ancho' => 16],
        ];

        $totalValor = array_sum(array_map(static fn(array $f) => (float) ($f['valor_mostrado'] ?? 0), $filas));

        $meta = ['Estado: ' . ($filtros['estado'] !== '' ? ucfirst($filtros['estado']) : 'Todos')];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        if (!empty($filtros['id_tipo_baja'])) {
            $meta[] = 'Tipo de baja: ' . $this->nombreTipoBajaPorId((int) $filtros['id_tipo_baja']);
        }
        if (!empty($filtros['id_ubicacion_anterior'])) {
            $meta[] = 'Servicio/ubicación anterior: ' . $this->nombreUbicacionPorId((int) $filtros['id_ubicacion_anterior']);
        }

        $spreadsheet = construirReporteExcel(
            'Bajas por período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape',
            ['valores' => ['valor_mostrado' => $totalValor]]
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('Bajas', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    private function exportarBajasPdf(array $filas, array $filtros): void
    {
        // Misma consolidación de fecha aprobada para Excel: fecha_baja cuando el estado es
        // Finalizada, fecha_preparacion en cualquier otro caso.
        $esFinalizada = $filtros['estado'] === 'finalizada';
        $tituloColumnaFecha = $esFinalizada ? 'Fecha de baja' : 'Fecha de trámite';
        $tipoColumnaFecha = $esFinalizada ? 'fecha' : 'fecha_hora';

        foreach ($filas as &$fila) {
            $fila['fecha_funcional'] = $esFinalizada ? $fila['fecha_baja'] : $fila['fecha_preparacion'];
        }
        unset($fila);

        $columnas = [
            ['clave' => 'numero_baja', 'titulo' => 'No. Baja', 'tipo' => 'texto', 'ancho' => 7],
            ['clave' => 'fecha_funcional', 'titulo' => $tituloColumnaFecha, 'tipo' => $tipoColumnaFecha, 'ancho' => 9],
            ['clave' => 'estado_baja', 'titulo' => 'Estado', 'tipo' => 'texto', 'ancho' => 8],
            ['clave' => 'codigo_interno_mostrado', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 8],
            ['clave' => 'descripcion_mostrada', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 16],
            ['clave' => 'responsable_anterior', 'titulo' => 'Responsable anterior', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'ubicacion_anterior', 'titulo' => 'Servicio', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'nombre_tipo_baja', 'titulo' => 'Tipo de baja', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'valor_mostrado', 'titulo' => 'Valor', 'tipo' => 'moneda', 'ancho' => 9],
            ['clave' => 'bodega_destino', 'titulo' => 'Bodega destino', 'tipo' => 'texto', 'ancho' => 11],
        ];

        $totalValor = array_sum(array_map(static fn(array $f) => (float) ($f['valor_mostrado'] ?? 0), $filas));

        $meta = ['Estado: ' . ($filtros['estado'] !== '' ? ucfirst($filtros['estado']) : 'Todos')];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        if (!empty($filtros['id_tipo_baja'])) {
            $meta[] = 'Tipo de baja: ' . $this->nombreTipoBajaPorId((int) $filtros['id_tipo_baja']);
        }
        if (!empty($filtros['id_ubicacion_anterior'])) {
            $meta[] = 'Servicio/ubicación anterior: ' . $this->nombreUbicacionPorId((int) $filtros['id_ubicacion_anterior']);
        }

        $dompdf = construirPdfReporte(
            'Bajas por período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            ['valores' => ['valor_mostrado' => $totalValor]]
        );

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('Bajas', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 7 — Verificaciones con diferencias
    // ---------------------------------------------------------------------------------------
    public function verificaciones(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
        $error = $this->validarRangoOpcional($fechaDesde, $fechaHasta);

        // Responsable registrado, Condición registrada, Condición observada y Usuario se quitaron
        // del formulario visible (revisión de usabilidad). El service sigue soportando los cuatro
        // filtros internamente.
        $filtros = [
            'fecha_desde' => $error === null ? $fechaDesde : '',
            'fecha_hasta' => $error === null ? $fechaHasta : '',
            'resultado' => in_array($_GET['resultado'] ?? '1', ['0', '1', ''], true) ? ($_GET['resultado'] ?? '1') : '1',
            'localizado' => in_array($_GET['localizado'] ?? '', ['0', '1'], true) ? $_GET['localizado'] : '',
            'id_ubicacion_registrada' => (int) ($_GET['id_ubicacion'] ?? 0),
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getVerificacionesConDiferencias($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarVerificaciones($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarVerificacionesPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/verificaciones', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'ubicaciones' => $this->model('Ubicacion')->getActivas(),
            'tituloPagina' => 'Reporte: Verificaciones con diferencias',
        ], 'main');
    }

    private function exportarVerificaciones(array $filas, array $filtros): void
    {
        // Excel compacto: se quitan Responsable correcto y Ubicación correcta (el Resultado +
        // Observaciones ya resumen la diferencia, y esas dos columnas ensanchaban demasiado la
        // tabla) y Verificado por — las tres siguen visibles en la vista web.
        $columnas = [
            ['clave' => 'fecha_hora', 'titulo' => 'Fecha/hora', 'tipo' => 'fecha_hora', 'ancho' => 14],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 22],
            ['clave' => 'responsable_registrado', 'titulo' => 'Responsable registrado', 'tipo' => 'texto', 'ancho' => 20],
            ['clave' => 'ubicacion_registrada', 'titulo' => 'Ubicación registrada', 'tipo' => 'texto', 'ancho' => 18],
            ['clave' => 'localizado_texto', 'titulo' => 'Localizado', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'condicion_registrada', 'titulo' => 'Condición registrada', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'condicion_observada', 'titulo' => 'Condición observada', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'resultado_texto', 'titulo' => 'Resultado', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'observaciones', 'titulo' => 'Observaciones', 'tipo' => 'texto', 'ancho' => 32],
        ];

        foreach ($filas as &$fila) {
            $fila['localizado_texto'] = (int) $fila['bien_localizado'] === 1 ? 'Sí' : 'No';
            $fila['resultado_texto'] = (int) $fila['tiene_diferencias'] === 1 ? 'Con diferencias' : 'Sin diferencias';
        }
        unset($fila);

        $meta = [];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        $etiquetasResultado = ['1' => 'Con diferencias', '0' => 'Sin diferencias', '' => 'Todas'];
        $meta[] = 'Resultado: ' . $etiquetasResultado[$filtros['resultado']];
        if (isset($filtros['localizado']) && $filtros['localizado'] !== '') {
            $meta[] = 'Localizado: ' . ($filtros['localizado'] === '1' ? 'Sí' : 'No');
        }
        if (!empty($filtros['id_ubicacion_registrada'])) {
            $meta[] = 'Ubicación registrada: ' . $this->nombreUbicacionPorId((int) $filtros['id_ubicacion_registrada']);
        }

        $spreadsheet = construirReporteExcel(
            'Verificaciones con diferencias',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape'
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('Verificaciones', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    private function exportarVerificacionesPdf(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'fecha_hora', 'titulo' => 'Fecha/hora', 'tipo' => 'fecha_hora', 'ancho' => 8],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 7],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'responsable_registrado', 'titulo' => 'Responsable registrado', 'tipo' => 'texto', 'ancho' => 11],
            ['clave' => 'ubicacion_registrada', 'titulo' => 'Ubicación registrada', 'tipo' => 'texto', 'ancho' => 10],
            ['clave' => 'localizado_texto', 'titulo' => 'Localizado', 'tipo' => 'texto', 'ancho' => 6],
            ['clave' => 'condicion_registrada', 'titulo' => 'Condición registrada', 'tipo' => 'texto', 'ancho' => 8],
            ['clave' => 'condicion_observada', 'titulo' => 'Condición observada', 'tipo' => 'texto', 'ancho' => 8],
            ['clave' => 'resultado_texto', 'titulo' => 'Resultado', 'tipo' => 'texto', 'ancho' => 8],
            ['clave' => 'observaciones', 'titulo' => 'Observaciones', 'tipo' => 'texto', 'ancho' => 20],
        ];

        foreach ($filas as &$fila) {
            $fila['localizado_texto'] = (int) $fila['bien_localizado'] === 1 ? 'Sí' : 'No';
            $fila['resultado_texto'] = (int) $fila['tiene_diferencias'] === 1 ? 'Con diferencias' : 'Sin diferencias';
            $fila['condicion_observada'] = $fila['condicion_observada'] !== null ? $fila['condicion_observada'] : 'No aplica';
        }
        unset($fila);

        $meta = [];
        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $meta[] = $this->lineaPeriodo($filtros);
        }
        $etiquetasResultado = ['1' => 'Con diferencias', '0' => 'Sin diferencias', '' => 'Todas'];
        $meta[] = 'Resultado: ' . $etiquetasResultado[$filtros['resultado']];
        if (isset($filtros['localizado']) && $filtros['localizado'] !== '') {
            $meta[] = 'Localizado: ' . ($filtros['localizado'] === '1' ? 'Sí' : 'No');
        }
        if (!empty($filtros['id_ubicacion_registrada'])) {
            $meta[] = 'Ubicación registrada: ' . $this->nombreUbicacionPorId((int) $filtros['id_ubicacion_registrada']);
        }

        $dompdf = construirPdfReporte('Verificaciones con diferencias', $columnas, $filas, $meta, $this->nombreUsuarioActual());

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('Verificaciones', $filtros['fecha_desde'] ?: null, $filtros['fecha_hasta'] ?: null));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 8 — Ingresos de bienes por período
    // ---------------------------------------------------------------------------------------
    public function ingresos(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        [$fechaDesde, $fechaHasta, $error] = $this->resolverRangoFechas();

        // Estado actual se quitó del formulario visible (revisión de usabilidad); sigue mostrándose
        // como columna informativa en la tabla/Excel, solo dejó de ser un filtro. El service sigue
        // soportando 'id_estado_bien' internamente.
        $filtros = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'nombre_bien' => trim((string) ($_GET['nombre_bien'] ?? '')),
            'id_forma_ingreso' => (int) ($_GET['id_forma_ingreso'] ?? 0),
            'id_categoria' => (int) ($_GET['id_categoria'] ?? 0),
            'procedencia' => trim((string) ($_GET['procedencia'] ?? '')),
        ];

        $service = $this->model('ReportesService');
        $filas = $error === null ? $service->getIngresos($filtros) : [];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarIngresos($filas, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarIngresosPdf($filas, $filtros);
            return;
        }

        $this->view('reportes/ingresos', [
            'filas' => $filas,
            'filtros' => $filtros,
            'error' => $error,
            'formasIngreso' => $this->model('FormaIngreso')->getActivas(),
            'categorias' => $this->model('CategoriaBien')->getActivas(),
            'tituloPagina' => 'Reporte: Ingresos de bienes',
        ], 'main');
    }

    private function exportarIngresos(array $filas, array $filtros): void
    {
        // Excel compacto: se quitan SICOIN y Estado actual (siguen disponibles en la vista web).
        $columnas = [
            ['clave' => 'fecha_ingreso', 'titulo' => 'Fecha ingreso', 'tipo' => 'fecha', 'ancho' => 13],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 12],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 24],
            ['clave' => 'nombre_forma', 'titulo' => 'Forma de ingreso', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'nombre_categoria', 'titulo' => 'Categoría', 'tipo' => 'texto', 'ancho' => 16],
            ['clave' => 'procedencia', 'titulo' => 'Procedencia / Proveedor', 'tipo' => 'texto', 'ancho' => 24],
            ['clave' => 'valor', 'titulo' => 'Valor', 'tipo' => 'moneda', 'ancho' => 13],
        ];

        $totalValor = array_sum(array_map(static fn(array $f) => (float) ($f['valor'] ?? 0), $filas));

        $meta = [$this->lineaPeriodo($filtros)];
        if (($filtros['nombre_bien'] ?? '') !== '') {
            $meta[] = 'Nombre del bien contiene: ' . $filtros['nombre_bien'];
        }
        if (!empty($filtros['id_forma_ingreso'])) {
            $meta[] = 'Forma de ingreso: ' . $this->nombreFormaIngresoPorId((int) $filtros['id_forma_ingreso']);
        }
        if (!empty($filtros['id_categoria'])) {
            $meta[] = 'Categoría: ' . $this->nombreCategoriaPorId((int) $filtros['id_categoria']);
        }
        if ($filtros['procedencia'] !== '') {
            $meta[] = 'Procedencia / Proveedor contiene: ' . $filtros['procedencia'];
        }

        $spreadsheet = construirReporteExcel(
            'Ingresos de bienes por período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            'landscape',
            ['valores' => ['valor' => $totalValor]]
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('Ingresos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    private function exportarIngresosPdf(array $filas, array $filtros): void
    {
        $columnas = [
            ['clave' => 'fecha_ingreso', 'titulo' => 'Fecha ingreso', 'tipo' => 'fecha', 'ancho' => 10],
            ['clave' => 'codigo_interno', 'titulo' => 'No. de Bien', 'tipo' => 'texto', 'ancho' => 9],
            ['clave' => 'descripcion', 'titulo' => 'Descripción', 'tipo' => 'texto', 'ancho' => 22],
            ['clave' => 'nombre_forma', 'titulo' => 'Forma de ingreso', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'nombre_categoria', 'titulo' => 'Categoría', 'tipo' => 'texto', 'ancho' => 14],
            ['clave' => 'procedencia', 'titulo' => 'Procedencia / Proveedor', 'tipo' => 'texto', 'ancho' => 22],
            ['clave' => 'valor', 'titulo' => 'Valor', 'tipo' => 'moneda', 'ancho' => 9],
        ];

        $totalValor = array_sum(array_map(static fn(array $f) => (float) ($f['valor'] ?? 0), $filas));

        $meta = [$this->lineaPeriodo($filtros)];
        if (($filtros['nombre_bien'] ?? '') !== '') {
            $meta[] = 'Nombre del bien contiene: ' . $filtros['nombre_bien'];
        }
        if (!empty($filtros['id_forma_ingreso'])) {
            $meta[] = 'Forma de ingreso: ' . $this->nombreFormaIngresoPorId((int) $filtros['id_forma_ingreso']);
        }
        if (!empty($filtros['id_categoria'])) {
            $meta[] = 'Categoría: ' . $this->nombreCategoriaPorId((int) $filtros['id_categoria']);
        }
        if ($filtros['procedencia'] !== '') {
            $meta[] = 'Procedencia / Proveedor contiene: ' . $filtros['procedencia'];
        }

        $dompdf = construirPdfReporte(
            'Ingresos de bienes por período',
            $columnas,
            $filas,
            $meta,
            $this->nombreUsuarioActual(),
            ['valores' => ['valor' => $totalValor]]
        );

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('Ingresos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    // ---------------------------------------------------------------------------------------
    // Reporte 9 — Resumen de movimientos por período
    // ---------------------------------------------------------------------------------------
    public function resumen(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        [$fechaDesde, $fechaHasta, $error] = $this->resolverRangoFechas();

        $filtros = ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta];

        $service = $this->model('ReportesService');
        $resultado = $error === null
            ? $service->getResumenMovimientos($filtros)
            : ['filas' => [], 'total_operaciones' => 0, 'total_bienes_unicos' => 0];

        $formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf'], true) ? $_GET['formato'] : 'web';

        if ($error === null && $formato === 'excel') {
            $this->exportarResumen($resultado, $filtros);
            return;
        }

        if ($error === null && $formato === 'pdf') {
            $this->exportarResumenPdf($resultado, $filtros);
            return;
        }

        $this->view('reportes/resumen', [
            'resultado' => $resultado,
            'filtros' => $filtros,
            'error' => $error,
            'tituloPagina' => 'Reporte: Resumen de movimientos',
        ], 'main');
    }

    private function exportarResumen(array $resultado, array $filtros): void
    {
        $columnas = [
            ['clave' => 'tipo', 'titulo' => 'Tipo', 'tipo' => 'texto', 'ancho' => 26],
            ['clave' => 'operaciones', 'titulo' => 'Operaciones', 'tipo' => 'entero', 'ancho' => 16],
            ['clave' => 'bienes_involucrados', 'titulo' => 'Bienes involucrados', 'tipo' => 'entero', 'ancho' => 20],
        ];

        $filas = $resultado['filas'];
        $filas[] = [
            'tipo' => 'TOTAL (bienes únicos, sin duplicar entre tipos)',
            'operaciones' => $resultado['total_operaciones'],
            'bienes_involucrados' => $resultado['total_bienes_unicos'],
        ];

        $spreadsheet = construirReporteExcel(
            'Resumen de movimientos por período',
            $columnas,
            $filas,
            [$this->lineaPeriodo($filtros)],
            $this->nombreUsuarioActual(),
            'landscape' // el helper ahora siempre imprime horizontal; ver revisión de impresión
        );

        descargarExcelReporte($spreadsheet, nombreArchivoReporte('ResumenMovimientos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    private function exportarResumenPdf(array $resultado, array $filtros): void
    {
        $columnas = [
            ['clave' => 'tipo', 'titulo' => 'Tipo', 'tipo' => 'texto', 'ancho' => 50],
            ['clave' => 'operaciones', 'titulo' => 'Operaciones', 'tipo' => 'entero', 'ancho' => 25],
            ['clave' => 'bienes_involucrados', 'titulo' => 'Bienes involucrados', 'tipo' => 'entero', 'ancho' => 25],
        ];

        $filas = $resultado['filas'];
        $filas[] = [
            'tipo' => 'TOTAL (bienes únicos, sin duplicar entre tipos)',
            'operaciones' => $resultado['total_operaciones'],
            'bienes_involucrados' => $resultado['total_bienes_unicos'],
        ];

        $dompdf = construirPdfReporte('Resumen de movimientos por período', $columnas, $filas, [$this->lineaPeriodo($filtros)], $this->nombreUsuarioActual());

        descargarPdfReporte($dompdf, nombreArchivoReportePdf('ResumenMovimientos', $filtros['fecha_desde'], $filtros['fecha_hasta']));
    }

    // =========================================================================================
    // Helpers privados compartidos
    // =========================================================================================

    // Rango de fechas OBLIGATORIO (Movimientos/Bienes con actividad/Ingresos/Resumen): si no viene
    // nada en la URL, se precarga el mes calendario actual — no queda una consulta sin límite.
    private function resolverRangoFechas(): array
    {
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));

        if ($fechaDesde === '' && $fechaHasta === '') {
            $fechaDesde = date('Y-m-01');
            $fechaHasta = date('Y-m-d');
        }

        if (!isValidIsoDate($fechaDesde) || !isValidIsoDate($fechaHasta)) {
            return [$fechaDesde, $fechaHasta, 'Debe indicar un rango de fechas válido.'];
        }

        if ($fechaDesde > $fechaHasta) {
            return [$fechaDesde, $fechaHasta, 'La fecha "desde" no puede ser posterior a la fecha "hasta".'];
        }

        return [$fechaDesde, $fechaHasta, null];
    }

    // Rango de fechas OPCIONAL (Préstamos/Bajas/Verificaciones): válido dejarlo vacío (el reporte no
    // se define únicamente por período), pero si se indica alguna, ambas deben ser válidas y
    // coherentes.
    private function validarRangoOpcional(string $fechaDesde, string $fechaHasta): ?string
    {
        if ($fechaDesde === '' && $fechaHasta === '') {
            return null;
        }

        if ($fechaDesde === '' || $fechaHasta === '') {
            return 'Debe indicar ambas fechas (desde y hasta) o dejar ambas vacías.';
        }

        if (!isValidIsoDate($fechaDesde) || !isValidIsoDate($fechaHasta)) {
            return 'Debe indicar un rango de fechas válido.';
        }

        if ($fechaDesde > $fechaHasta) {
            return 'La fecha "desde" no puede ser posterior a la fecha "hasta".';
        }

        return null;
    }

    private function lineaPeriodo(array $filtros): string
    {
        return 'Período: ' . formatDate($filtros['fecha_desde']) . ' al ' . formatDate($filtros['fecha_hasta']);
    }

    private function nombreUsuarioActual(): string
    {
        return (string) ($_SESSION['nombre_completo'] ?? '');
    }

    // -----------------------------------------------------------------------------------------
    // Lookups por ID para armar las líneas "Filtros aplicados" del Excel (mostrar el nombre, no el
    // id crudo). Solo se llaman cuando el filtro correspondiente está realmente activo.
    // -----------------------------------------------------------------------------------------
    private function nombreResponsablePorId(int $idResponsable): string
    {
        $responsable = $this->model('Responsable')->findById($idResponsable);

        return $responsable !== false ? (string) $responsable['nombre_completo'] : '—';
    }

    private function nombreUbicacionPorId(int $idUbicacion): string
    {
        $ubicacion = $this->model('Ubicacion')->findById($idUbicacion);

        return $ubicacion !== false ? (string) $ubicacion['nombre_ubicacion'] : '—';
    }

    private function nombreCategoriaPorId(int $idCategoria): string
    {
        foreach ($this->model('CategoriaBien')->getActivas() as $categoria) {
            if ((int) $categoria['id_categoria'] === $idCategoria) {
                return (string) $categoria['nombre_categoria'];
            }
        }

        return '—';
    }

    private function nombreTipoBajaPorId(int $idTipoBaja): string
    {
        foreach ($this->model('TipoBaja')->getActivos() as $tipoBaja) {
            if ((int) $tipoBaja['id_tipo_baja'] === $idTipoBaja) {
                return (string) $tipoBaja['nombre_tipo_baja'];
            }
        }

        return '—';
    }

    private function nombreFormaIngresoPorId(int $idFormaIngreso): string
    {
        foreach ($this->model('FormaIngreso')->getActivas() as $forma) {
            if ((int) $forma['id_forma_ingreso'] === $idFormaIngreso) {
                return (string) $forma['nombre_forma'];
            }
        }

        return '—';
    }
}
