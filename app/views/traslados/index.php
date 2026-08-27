<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TrasladosController::index()).
// Filtro por GET (búsqueda): el controlador lo lee de $_GET y lo pasa a Movimiento::getAll(), que sin
// argumento sigue devolviendo el listado completo (comportamiento previo).
// Un traslado no tiene estados (queda firme al crearse), por eso no hay badge de estado.
$movimientos = $movimientos ?? [];
$q = $q ?? '';
$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Traslados</h1>
            <p class="page-subtitle">Gestión y consulta de los traslados de bienes institucionales.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
            <?php if ($puedeRegistrar): ?>
                <a class="btn btn-primary" href="index.php?modulo=traslados&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar traslado
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="traslados">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, responsable o ubicación">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=traslados" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($movimientos)): ?>
        <p class="estado-vacio">No se encontraron traslados<?= $q !== '' ? ' con ese criterio' : ' registrados' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-traslados">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Responsable origen</th>
                        <th>Responsable destino</th>
                        <th>Ubicación destino</th>
                        <th>Bienes</th>
                        <th>Registrado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?= $mostrar($movimiento['numero_movimiento'] ?? null) ?></td>
                            <td><?= $mostrar(formatDateTime($movimiento['fecha_movimiento'] ?? null)) ?></td>
                            <td><?= $mostrar($movimiento['responsable_origen_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($movimiento['responsable_destino_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($movimiento['ubicacion_destino_nombre'] ?? null) ?></td>
                            <td><?= (int) ($movimiento['cantidad_bienes'] ?? 0) ?></td>
                            <td><?= $mostrar($movimiento['usuario_registra_nombre'] ?? null) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=traslados&accion=ver&id=<?= (int) $movimiento['id_movimiento'] ?>">
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
    <?php endif; ?>
</div>

<script src="<?= url('public/js/app.js') ?>"></script>
