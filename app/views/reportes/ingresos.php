<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::ingresos()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, nombre_bien, id_forma_ingreso, id_categoria, procedencia, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$filas = $filas ?? [];
$filtros = $filtros ?? [];
$error = $error ?? null;
$formasIngreso = $formasIngreso ?? [];
$categorias = $categorias ?? [];

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Activo' => 'badge badge-exito',
        'Baja' => 'badge badge-error',
        'En reparación', 'Reparación' => 'badge badge-pendiente',
        default => 'badge',
    };
};

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'ingresos';
$paramsExportar['formato'] = 'excel';
$urlExportar = 'index.php?' . http_build_query($paramsExportar);

$paramsExportarPdf = $paramsExportar;
$paramsExportarPdf['formato'] = 'pdf';
$urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);

$svgDescarga = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>';

$totalValor = 0;
foreach ($filas as $filaTotal) {
    $totalValor += (float) ($filaTotal['valor'] ?? 0);
}
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Ingresos de bienes por período</h1>
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
    <input type="hidden" name="accion" value="ingresos">

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
        <label class="form-label" for="nombre_bien">Nombre del bien</label>
        <input type="text" id="nombre_bien" name="nombre_bien" class="form-control" value="<?= $valorInput($filtros['nombre_bien'] ?? '') ?>" autocomplete="off" placeholder="Ej. impresora">
    </div>

    <div class="form-group">
        <label class="form-label" for="id_forma_ingreso">Forma de ingreso</label>
        <select id="id_forma_ingreso" name="id_forma_ingreso" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($formasIngreso as $forma): ?>
                <option value="<?= (int) $forma['id_forma_ingreso'] ?>" <?= ((int) ($filtros['id_forma_ingreso'] ?? 0) === (int) $forma['id_forma_ingreso']) ? 'selected' : '' ?>><?= htmlspecialchars($forma['nombre_forma'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_categoria">Categoría</label>
        <select id="id_categoria" name="id_categoria" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id_categoria'] ?>" <?= ((int) ($filtros['id_categoria'] ?? 0) === (int) $categoria['id_categoria']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="procedencia">Procedencia / Proveedor</label>
        <input type="text" id="procedencia" name="procedencia" class="form-control" value="<?= $valorInput($filtros['procedencia'] ?? '') ?>" autocomplete="off">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=ingresos" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filas)): ?>
        <p class="estado-vacio">No se encontraron ingresos de bienes para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-reporte-ingresos">
                <thead>
                    <tr>
                        <th>Fecha ingreso</th>
                        <th>No. de Bien</th>
                        <th>SICOIN</th>
                        <th>Descripción</th>
                        <th>Forma de ingreso</th>
                        <th>Categoría</th>
                        <th>Procedencia / Proveedor</th>
                        <th>Valor</th>
                        <th>Estado actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td><?= $mostrar(formatDate($fila['fecha_ingreso'])) ?></td>
                            <td><?= $mostrar($fila['codigo_interno']) ?></td>
                            <td><?= $mostrar($fila['codigo_sicoin']) ?></td>
                            <td><?= $mostrar($fila['descripcion']) ?></td>
                            <td><?= $mostrar($fila['nombre_forma']) ?></td>
                            <td><?= $mostrar($fila['nombre_categoria']) ?></td>
                            <td><?= $mostrar($fila['procedencia']) ?></td>
                            <td><?= formatearQuetzales($fila['valor'] ?? null) ?></td>
                            <td><span class="<?= $claseBadgeEstado($fila['nombre_estado'] ?? null) ?>"><?= $mostrar($fila['nombre_estado']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7"><strong>Total</strong></td>
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
