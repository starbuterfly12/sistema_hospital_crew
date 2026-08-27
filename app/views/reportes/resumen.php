<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::resumen()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$resultado = $resultado ?? ['filas' => [], 'total_operaciones' => 0, 'total_bienes_unicos' => 0];
$filtros = $filtros ?? [];
$error = $error ?? null;
$filasResumen = $resultado['filas'] ?? [];

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'resumen';
$paramsExportar['formato'] = 'excel';
$urlExportar = 'index.php?' . http_build_query($paramsExportar);

$paramsExportarPdf = $paramsExportar;
$paramsExportarPdf['formato'] = 'pdf';
$urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);

$svgDescarga = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Resumen de movimientos por período</h1>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=reportes" class="btn btn-secondary">Volver</a>
            <a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-verde-suave"><?= $svgDescarga ?> Descargar Excel</a>
            <a href="<?= htmlspecialchars($urlExportarPdf, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-azul-suave"><?= $svgDescarga ?> Descargar PDF</a>
        </div>
    </div>
</div>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= $mostrar($error) ?></div>
<?php endif; ?>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="reportes">
    <input type="hidden" name="accion" value="resumen">

    <div class="form-group">
        <label class="form-label" for="fecha_desde">Fecha desde</label>
        <div class="campo-fecha">
            <input type="text" id="fecha_desde" name="fecha_desde" class="form-control" value="<?= $valorInput($filtros['fecha_desde'] ?? '') ?>" autocomplete="off">
            <button type="button" class="btn-calendario" data-flatpickr-target="fecha_desde" aria-label="Abrir calendario">📅</button>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="fecha_hasta">Fecha hasta</label>
        <div class="campo-fecha">
            <input type="text" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?= $valorInput($filtros['fecha_hasta'] ?? '') ?>" autocomplete="off">
            <button type="button" class="btn-calendario" data-flatpickr-target="fecha_hasta" aria-label="Abrir calendario">📅</button>
        </div>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=resumen" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filasResumen)): ?>
        <p class="estado-vacio">No se encontraron movimientos para el período seleccionado.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Operaciones</th>
                        <th>Bienes involucrados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filasResumen as $fila): ?>
                        <tr>
                            <td><?= $mostrar($fila['tipo']) ?></td>
                            <td><?= (int) $fila['operaciones'] ?></td>
                            <td><?= (int) $fila['bienes_involucrados'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total operaciones</strong></td>
                        <td colspan="2"><strong><?= (int) $resultado['total_operaciones'] ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total bienes únicos involucrados</strong></td>
                        <td colspan="2"><strong><?= (int) $resultado['total_bienes_unicos'] ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script>
    inicializarSelectoresFecha(['fecha_desde', 'fecha_hasta']);
</script>
