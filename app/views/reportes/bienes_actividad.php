<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::bienesActividad()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, tipo, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$filas = $filas ?? [];
$filtros = $filtros ?? [];
$error = $error ?? null;

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'bienesActividad';
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
            <h1 class="page-title">Bienes con actividad en un período</h1>
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
    <input type="hidden" name="accion" value="bienesActividad">

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

    <div class="form-group">
        <label class="form-label" for="tipo">Tipo de actividad</label>
        <select id="tipo" name="tipo" class="form-control">
            <option value="">Todos</option>
            <?php foreach (ReportesService::TIPOS_EVENTO as $tipoEvento): ?>
                <option value="<?= htmlspecialchars($tipoEvento, ENT_QUOTES, 'UTF-8') ?>" <?= ($filtros['tipo'] ?? '') === $tipoEvento ? 'selected' : '' ?>><?= htmlspecialchars($tipoEvento, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="codigo">Código interno / SICOIN</label>
        <input type="text" id="codigo" name="codigo" class="form-control" value="<?= $valorInput($filtros['codigo'] ?? '') ?>" placeholder="Ej. HC-131313 o 131313" autocomplete="off">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=bienesActividad" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filas)): ?>
        <p class="estado-vacio">No se encontraron bienes con actividad para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-reporte-bienes-actividad">
                <thead>
                    <tr>
                        <th>No. de Bien</th>
                        <th>SICOIN</th>
                        <th>Descripción</th>
                        <th>Cantidad de eventos</th>
                        <th>Primer evento</th>
                        <th>Último evento</th>
                        <th>Tipos de actividad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td><?= $mostrar($fila['codigo_interno']) ?></td>
                            <td><?= $mostrar($fila['codigo_sicoin']) ?></td>
                            <td><?= $mostrar($fila['descripcion']) ?></td>
                            <td><?= (int) $fila['eventos'] ?></td>
                            <td><?= $mostrar(formatFechaSegunTipo($fila['primer_evento'], $fila['primer_evento_es_datetime'])) ?></td>
                            <td><?= $mostrar(formatFechaSegunTipo($fila['ultimo_evento'], $fila['ultimo_evento_es_datetime'])) ?></td>
                            <td><?= $mostrar($fila['tipos']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=bienes&accion=historial&id=<?= (int) $fila['id_bien'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l3 2"/><path d="M3.05 11a9 9 0 1 1 .5 4"/><path d="M3 4v5h5"/></svg>
                                        Historial
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="tabla-nota">Total de registros: <strong><?= count($filas) ?></strong></p>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script src="<?= url('public/js/app.js') ?>"></script>
<script>
    inicializarSelectoresFecha(['fecha_desde', 'fecha_hasta']);
</script>
