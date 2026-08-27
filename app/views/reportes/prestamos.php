<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::prestamos()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, estado, id_responsable, vencido, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$filas = $filas ?? [];
$filtros = $filtros ?? [];
$error = $error ?? null;
$responsables = $responsables ?? [];

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-info',
        'parcial' => 'badge badge-pendiente',
        default => 'badge',
    };
};

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'prestamos';
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
            <h1 class="page-title">Préstamos pendientes o vencidos</h1>
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
    <input type="hidden" name="accion" value="prestamos">

    <div class="form-group">
        <label class="form-label" for="fecha_desde">Fecha préstamo desde</label>
        <div class="campo-fecha">
            <input type="text" id="fecha_desde" name="fecha_desde" class="form-control" value="<?= $valorInput($filtros['fecha_desde'] ?? '') ?>" autocomplete="off">
            <button type="button" class="btn-calendario" data-flatpickr-target="fecha_desde" aria-label="Abrir calendario">📅</button>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="fecha_hasta">Fecha préstamo hasta</label>
        <div class="campo-fecha">
            <input type="text" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?= $valorInput($filtros['fecha_hasta'] ?? '') ?>" autocomplete="off">
            <button type="button" class="btn-calendario" data-flatpickr-target="fecha_hasta" aria-label="Abrir calendario">📅</button>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos (activo + parcial)</option>
            <option value="activo" <?= ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="parcial" <?= ($filtros['estado'] ?? '') === 'parcial' ? 'selected' : '' ?>>Parcial</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_responsable">Receptor</label>
        <select id="id_responsable" name="id_responsable" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($responsables as $responsable): ?>
                <option value="<?= (int) $responsable['id_responsable'] ?>" <?= ((int) ($filtros['id_responsable_destino'] ?? 0) === (int) $responsable['id_responsable']) ? 'selected' : '' ?>><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="vencido">Vencido</label>
        <select id="vencido" name="vencido" class="form-control">
            <option value="">Todos</option>
            <option value="1" <?= ($filtros['vencido'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
            <option value="0" <?= ($filtros['vencido'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=prestamos" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filas)): ?>
        <p class="estado-vacio">No existen préstamos pendientes o vencidos con los criterios seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-reporte-prestamos">
                <thead>
                    <tr>
                        <th>No. préstamo</th>
                        <th>Receptor</th>
                        <th>Fecha préstamo</th>
                        <th>Fecha prevista de devolución</th>
                        <th>Estado</th>
                        <th>Bienes pendientes</th>
                        <th>Vencido</th>
                        <th>Días vencidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td><?= $mostrar($fila['numero_prestamo']) ?></td>
                            <td><?= $mostrar($fila['responsable_destino_mostrado']) ?></td>
                            <td><?= $mostrar(formatDate($fila['fecha_prestamo'])) ?></td>
                            <td><?= $mostrar(formatDate($fila['fecha_devolucion_estimada'])) ?></td>
                            <td><span class="<?= $claseBadgeEstado($fila['estado_prestamo'] ?? null) ?>"><?= $mostrar(ucfirst((string) $fila['estado_prestamo'])) ?></span></td>
                            <td><?= (int) $fila['bienes_pendientes'] ?></td>
                            <td><span class="badge <?= $fila['vencido'] ? 'badge-error' : 'badge-exito' ?>"><?= $fila['vencido'] ? 'Sí' : 'No' ?></span></td>
                            <td><?= (int) $fila['dias_vencido'] ?></td>
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
