<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::bajas()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, estado, id_tipo_baja, id_ubicacion_anterior, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$filas = $filas ?? [];
$filtros = $filtros ?? [];
$error = $error ?? null;
$ubicaciones = $ubicaciones ?? [];
$tiposBaja = $tiposBaja ?? [];

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'pendiente' => 'badge badge-pendiente',
        'autorizada' => 'badge badge-info',
        'rechazada' => 'badge badge-error',
        'finalizada' => 'badge badge-exito',
        default => 'badge',
    };
};

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'bajas';
$paramsExportar['formato'] = 'excel';
$urlExportar = 'index.php?' . http_build_query($paramsExportar);

$paramsExportarPdf = $paramsExportar;
$paramsExportarPdf['formato'] = 'pdf';
$urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);

$svgDescarga = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>';

$totalValor = 0;
foreach ($filas as $filaTotal) {
    $totalValor += (float) ($filaTotal['valor_mostrado'] ?? 0);
}
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Bajas por período</h1>
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
    <input type="hidden" name="accion" value="bajas">

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
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="finalizada" <?= ($filtros['estado'] ?? 'finalizada') === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
            <option value="pendiente" <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="autorizada" <?= ($filtros['estado'] ?? '') === 'autorizada' ? 'selected' : '' ?>>Autorizada</option>
            <option value="rechazada" <?= ($filtros['estado'] ?? '') === 'rechazada' ? 'selected' : '' ?>>Rechazada</option>
            <option value="" <?= ($filtros['estado'] ?? '') === '' ? 'selected' : '' ?>>Todos</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_tipo_baja">Tipo de baja</label>
        <select id="id_tipo_baja" name="id_tipo_baja" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($tiposBaja as $tipoBaja): ?>
                <option value="<?= (int) $tipoBaja['id_tipo_baja'] ?>" <?= ((int) ($filtros['id_tipo_baja'] ?? 0) === (int) $tipoBaja['id_tipo_baja']) ? 'selected' : '' ?>><?= htmlspecialchars($tipoBaja['nombre_tipo_baja'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_ubicacion_anterior">Servicio/ubicación anterior</label>
        <select id="id_ubicacion_anterior" name="id_ubicacion_anterior" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($ubicaciones as $ubicacion): ?>
                <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_anterior'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>><?= htmlspecialchars($ubicacion['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=bajas" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filas)): ?>
        <p class="estado-vacio">No se encontraron bajas para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-reporte-bajas">
                <thead>
                    <tr>
                        <th>No. Baja</th>
                        <th>Fecha preparación</th>
                        <th>Fecha autorización</th>
                        <th>Fecha baja</th>
                        <th>Estado</th>
                        <th>No. de Bien</th>
                        <th>SICOIN</th>
                        <th>Descripción</th>
                        <th>Responsable anterior</th>
                        <th>Ubicación anterior</th>
                        <th>Tipo de baja</th>
                        <th>Valor</th>
                        <th>Bodega destino</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td><?= $mostrar($fila['numero_baja']) ?></td>
                            <td><?= $mostrar(formatDateTime($fila['fecha_preparacion'])) ?></td>
                            <td><?= $mostrar(formatDateTime($fila['fecha_autorizacion'])) ?></td>
                            <td><?= $mostrar(formatDate($fila['fecha_baja'])) ?></td>
                            <td><span class="<?= $claseBadgeEstado($fila['estado_baja'] ?? null) ?>"><?= $mostrar(ucfirst((string) $fila['estado_baja'])) ?></span></td>
                            <td><?= $mostrar($fila['codigo_interno_mostrado']) ?></td>
                            <td><?= $mostrar($fila['codigo_sicoin_mostrado']) ?></td>
                            <td><?= $mostrar($fila['descripcion_mostrada']) ?></td>
                            <td><?= $mostrar($fila['responsable_anterior']) ?></td>
                            <td><?= $mostrar($fila['ubicacion_anterior']) ?></td>
                            <td><?= $mostrar($fila['nombre_tipo_baja']) ?></td>
                            <td><?= formatearQuetzales($fila['valor_mostrado'] ?? null) ?></td>
                            <td><?= $mostrar($fila['bodega_destino']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="11"><strong>Total</strong></td>
                        <td><strong><?= formatearQuetzales($totalValor) ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
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
