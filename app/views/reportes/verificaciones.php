<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::verificaciones()).
// Solo presentación: consultas, filtros backend, exportaciones y lógica de fechas NO cambian.
// Parámetros GET conservados: fecha_desde, fecha_hasta, resultado, localizado, id_ubicacion, formato.
$mostrar = static function ($value): string {
    return ($value !== null && $value !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$mostrarSiNo = static function ($valor): string {
    if ($valor === null) {
        return 'No aplica';
    }
    return (int) $valor === 1 ? 'Sí' : 'No';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$filas = $filas ?? [];
$filtros = $filtros ?? [];
$error = $error ?? null;
$ubicaciones = $ubicaciones ?? [];

$paramsExportar = $_GET;
$paramsExportar['modulo'] = 'reportes';
$paramsExportar['accion'] = 'verificaciones';
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
            <h1 class="page-title">Verificaciones con diferencias</h1>
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
    <input type="hidden" name="accion" value="verificaciones">

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
        <label class="form-label" for="resultado">Resultado</label>
        <select id="resultado" name="resultado" class="form-control">
            <option value="1" <?= ($filtros['resultado'] ?? '1') === '1' ? 'selected' : '' ?>>Con diferencias</option>
            <option value="0" <?= ($filtros['resultado'] ?? '') === '0' ? 'selected' : '' ?>>Sin diferencias</option>
            <option value="" <?= ($filtros['resultado'] ?? '') === '' ? 'selected' : '' ?>>Todas</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="localizado">Localizado</label>
        <select id="localizado" name="localizado" class="form-control">
            <option value="">Todos</option>
            <option value="1" <?= ($filtros['localizado'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
            <option value="0" <?= ($filtros['localizado'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_ubicacion">Ubicación registrada</label>
        <select id="id_ubicacion" name="id_ubicacion" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($ubicaciones as $ubicacion): ?>
                <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_registrada'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>><?= htmlspecialchars($ubicacion['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=reportes&accion=verificaciones" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($filas)): ?>
        <p class="estado-vacio">No se encontraron verificaciones para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-reporte-verificaciones">
                <thead>
                    <tr>
                        <th>Fecha/hora</th>
                        <th>No. de Bien</th>
                        <th>Descripción</th>
                        <th>Responsable registrado</th>
                        <th>Ubicación registrada</th>
                        <th>Condición registrada</th>
                        <th>Localizado</th>
                        <th>Responsable correcto</th>
                        <th>Ubicación correcta</th>
                        <th>Condición observada</th>
                        <th>Resultado</th>
                        <th>Observaciones</th>
                        <th>Verificado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <?php $conDiferencias = (int) $fila['tiene_diferencias'] === 1; ?>
                        <tr>
                            <td><?= $mostrar(formatDateTime($fila['fecha_hora'])) ?></td>
                            <td><?= $mostrar($fila['codigo_interno']) ?></td>
                            <td><?= $mostrar($fila['descripcion']) ?></td>
                            <td><?= $mostrar($fila['responsable_registrado']) ?></td>
                            <td><?= $mostrar($fila['ubicacion_registrada']) ?></td>
                            <td><?= $mostrar($fila['condicion_registrada']) ?></td>
                            <td><span class="badge <?= (int) $fila['bien_localizado'] === 1 ? 'badge-exito' : 'badge-error' ?>"><?= (int) $fila['bien_localizado'] === 1 ? 'Sí' : 'No' ?></span></td>
                            <td><?= $mostrarSiNo($fila['responsable_correcto']) ?></td>
                            <td><?= $mostrarSiNo($fila['ubicacion_correcta']) ?></td>
                            <td><?= $fila['condicion_observada'] !== null ? $mostrar($fila['condicion_observada']) : 'No aplica' ?></td>
                            <td><span class="badge <?= $conDiferencias ? 'badge-pendiente' : 'badge-exito' ?>"><?= $conDiferencias ? 'Con diferencias' : 'Sin diferencias' ?></span></td>
                            <td><?= $mostrar($fila['observaciones']) ?></td>
                            <td><?= $mostrar($fila['usuario_verifica']) ?></td>
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
