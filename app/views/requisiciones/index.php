<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver RequisicionesController::index()).
// Filtros por GET (búsqueda + estado): el controlador los lee de $_GET y los pasa a Requisicion::getAll(),
// que sin argumentos sigue devolviendo el listado completo (comportamiento previo).

$requisiciones = $requisiciones ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

$estadosRequisicion = ['Pendiente', 'Autorizada', 'Entregada', 'Anulada'];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Pendiente' => 'badge badge-pendiente',
        'Autorizada' => 'badge badge-info',
        'Entregada' => 'badge badge-exito',
        'Anulada' => 'badge badge-error',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Requisiciones</h1>
            <p class="page-subtitle">Gestión y consulta de las requisiciones de bienes institucionales.</p>
        </div>

        <?php if ($puedeRegistrar): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="index.php?modulo=requisiciones&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Nueva requisición
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="requisiciones">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="No. sistema, requisición, oficio o responsable">
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($estadosRequisicion as $estadoOpcion): ?>
                <option value="<?= $estadoOpcion ?>" <?= ($estado === $estadoOpcion) ? 'selected' : '' ?>><?= $estadoOpcion ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=requisiciones" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($requisiciones)): ?>
        <p class="estado-vacio">No se encontraron requisiciones<?= ($q !== '' || $estado !== '') ? ' con esos filtros' : ' registradas' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-resizable table-requisiciones">
                <thead>
                    <tr>
                        <th>No. sistema</th>
                        <th>Requisiciones institucionales</th>
                        <th>Oficio No.</th>
                        <th>Responsable solicitante</th>
                        <th>Área / Servicio</th>
                        <th>Bienes</th>
                        <th>Estado</th>
                        <th>Fecha de creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisiciones as $requisicion): ?>
                        <tr>
                            <td><?= $mostrar($requisicion['numero_requisicion_sistema'] ?? null) ?></td>
                            <td><?= $mostrar($requisicion['numeros_institucionales'] ?? null) ?></td>
                            <td><?= $mostrar($requisicion['numero_oficio'] ?? null) ?></td>
                            <td><?= $mostrar($requisicion['responsable_solicitante_mostrado'] ?? null) ?></td>
                            <td><?= $mostrar($requisicion['ubicacion_solicitante_mostrada'] ?? null) ?></td>
                            <td><?= (int) ($requisicion['total_bienes'] ?? 0) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($requisicion['estado_requisicion'] ?? null) ?>"><?= $mostrar($requisicion['estado_requisicion'] ?? null) ?></span>
                            </td>
                            <td><?= $mostrar(formatDateTime($requisicion['created_at'] ?? null)) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=requisiciones&accion=ver&id=<?= (int) $requisicion['id_requisicion'] ?>">
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
