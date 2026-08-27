<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BitacoraController::index()).
// Módulo de SOLO LECTURA, exclusivo de Administrador. Solo presentación: consultas, filtros GET
// (fecha_desde, fecha_hasta, buscar, filtro_modulo, resultado, pagina), validación de fechas y
// paginación (25/pág) NO cambian. Flatpickr local intacto.
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$mostrarUsuario = static function (array $fila) use ($mostrar): string {
    if ($fila['id_usuario'] !== null) {
        if (($fila['nombre_completo'] ?? null) !== null && ($fila['usuario'] ?? null) !== null) {
            return $mostrar($fila['nombre_completo']) . ' (' . $mostrar($fila['usuario']) . ')';
        }
        return 'Usuario #' . (int) $fila['id_usuario'];
    }
    if (!empty($fila['usuario_intentado'])) {
        return 'No autenticado (intento: ' . $mostrar($fila['usuario_intentado']) . ')';
    }
    return 'Sistema';
};

$registros = $registros ?? [];
$error = $error ?? null;
$filtros = $filtros ?? [];
$modulos = $modulos ?? [];
$pagina = (int) ($pagina ?? 1);
$totalPaginas = (int) ($totalPaginas ?? 1);
$total = (int) ($total ?? 0);

$hayFiltros = (($filtros['fecha_desde'] ?? '') !== '' || ($filtros['fecha_hasta'] ?? '') !== ''
    || ($filtros['buscar'] ?? '') !== '' || ($filtros['modulo'] ?? '') !== '' || ($filtros['resultado'] ?? '') !== '');

$paramsBase = [
    'modulo' => 'bitacora',
    'fecha_desde' => $filtros['fecha_desde'] ?? '',
    'fecha_hasta' => $filtros['fecha_hasta'] ?? '',
    'buscar' => $filtros['buscar'] ?? '',
    'filtro_modulo' => $filtros['modulo'] ?? '',
    'resultado' => $filtros['resultado'] ?? '',
];
$urlPagina = static function (int $numeroPagina) use ($paramsBase): string {
    $params = $paramsBase;
    $params['pagina'] = $numeroPagina;
    return 'index.php?' . http_build_query($params);
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Bitácora</h1>
            <p class="page-subtitle">Consulta de las acciones registradas en el sistema.</p>
        </div>
    </div>
</div>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= $mostrar($error) ?></div>
<?php endif; ?>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="bitacora">

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
        <label class="form-label" for="buscar">Buscar</label>
        <input type="text" id="buscar" name="buscar" class="form-control" value="<?= $valorInput($filtros['buscar'] ?? '') ?>" placeholder="Usuario, acción o descripción">
    </div>

    <div class="form-group">
        <label class="form-label" for="filtro_modulo">Módulo</label>
        <select id="filtro_modulo" name="filtro_modulo" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($modulos as $moduloOpcion): ?>
                <option value="<?= htmlspecialchars((string) $moduloOpcion, ENT_QUOTES, 'UTF-8') ?>" <?= ($filtros['modulo'] ?? '') === $moduloOpcion ? 'selected' : '' ?>><?= htmlspecialchars((string) $moduloOpcion, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="resultado">Resultado</label>
        <select id="resultado" name="resultado" class="form-control">
            <option value="">Todos</option>
            <option value="exitoso" <?= ($filtros['resultado'] ?? '') === 'exitoso' ? 'selected' : '' ?>>Exitoso</option>
            <option value="fallido" <?= ($filtros['resultado'] ?? '') === 'fallido' ? 'selected' : '' ?>>Fallido</option>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=bitacora" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if ($error === null && empty($registros)): ?>
        <p class="estado-vacio">No se encontraron registros de bitácora para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-bitacora">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Resultado</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                        <?php $exito = ($registro['resultado'] ?? '') === 'exitoso'; ?>
                        <tr>
                            <td><?= $mostrar(formatDateTimeSeconds($registro['fecha_hora'] ?? null)) ?></td>
                            <td><?= $mostrarUsuario($registro) ?></td>
                            <td><?= $mostrar($registro['accion'] ?? null) ?></td>
                            <td><?= $mostrar($registro['modulo'] ?? null) ?></td>
                            <td><span class="badge <?= $exito ? 'badge-exito' : 'badge-error' ?>"><?= $exito ? 'Exitoso' : 'Fallido' ?></span></td>
                            <td><?= $mostrar($registro['descripcion'] ?? null) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=bitacora&accion=ver&id=<?= (int) $registro['id_bitacora'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="<?= htmlspecialchars($urlPagina($pagina - 1), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">« Anterior</a>
            <?php else: ?>
                <span class="btn btn-secondary" aria-disabled="true" style="opacity:.5;pointer-events:none;">« Anterior</span>
            <?php endif; ?>

            <span>Página <?= $pagina ?> de <?= $totalPaginas ?> · <?= $total ?> registro<?= $total === 1 ? '' : 's' ?></span>

            <?php if ($pagina < $totalPaginas): ?>
                <a href="<?= htmlspecialchars($urlPagina($pagina + 1), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Siguiente »</a>
            <?php else: ?>
                <span class="btn btn-secondary" aria-disabled="true" style="opacity:.5;pointer-events:none;">Siguiente »</span>
            <?php endif; ?>
        </div>
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
