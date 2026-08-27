<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TarjetasController::index()).
// Filtros por GET (búsqueda + estado): el controlador los lee de $_GET y los pasa a
// TarjetaResponsabilidad::getAll(), que sin argumentos sigue devolviendo el listado completo.
// Estados reales de tarjetas_responsabilidad.estado_tarjeta: Emitida / Anulada (Anular todavía no
// está implementado — no se ofrece acción de anulación aquí).
$tarjetas = $tarjetas ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$puedeGenerar = tieneRol(['Administrador', 'Operativo']);

$estadosTarjeta = ['Emitida', 'Anulada'];

$hayFiltros = ($q !== '' || $estado !== '');

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Emitida' => 'badge badge-exito',
        'Anulada' => 'badge badge-error',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Tarjeta de responsabilidad</h1>
            <p class="page-subtitle">Consulta de las tarjetas de responsabilidad registradas en el sistema.</p>
        </div>

        <?php if ($puedeGenerar): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="index.php?modulo=tarjetas&accion=generar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Generar tarjeta
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="tarjetas">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, responsable, ubicación o asignación">
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($estadosTarjeta as $estadoOpcion): ?>
                <option value="<?= $estadoOpcion ?>" <?= ($estado === $estadoOpcion) ? 'selected' : '' ?>><?= $estadoOpcion ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=tarjetas" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($tarjetas)): ?>
        <p class="estado-vacio">No se encontraron tarjetas de responsabilidad<?= $hayFiltros ? ' con esos filtros' : ' registradas' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-tarjetas">
                <thead>
                    <tr>
                        <th>Número de tarjeta</th>
                        <th>Fecha de emisión</th>
                        <th>Responsable</th>
                        <th>Ubicación</th>
                        <th>Asignación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tarjetas as $tarjeta): ?>
                        <tr>
                            <td><?= $mostrar($tarjeta['numero_tarjeta'] ?? null) ?></td>
                            <td><?= $mostrar(formatDate($tarjeta['fecha_emision'] ?? null)) ?></td>
                            <td><?= $mostrar($tarjeta['responsable_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($tarjeta['ubicacion_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($tarjeta['numero_asignacion'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($tarjeta['estado_tarjeta'] ?? null) ?>"><?= $mostrar($tarjeta['estado_tarjeta'] ?? null) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=tarjetas&accion=ver&id=<?= (int) $tarjeta['id_tarjeta_responsabilidad'] ?>">
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
