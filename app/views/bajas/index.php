<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BajasController::index()).
// Filtros por GET (búsqueda + estado + tipo de baja): el controlador los lee de $_GET y los pasa a
// Baja::getAll(), que sin argumentos sigue devolviendo el listado completo (comportamiento previo,
// del que también depende BajasController::solicitudes()).
// Estados reales de `bajas.estado_baja`: pendiente / autorizada / rechazada / finalizada (no existe
// "anulada" — descartado en la revisión funcional del módulo).
$bajas = $bajas ?? [];
$tiposBaja = $tiposBaja ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$idTipoBaja = (int) ($idTipoBaja ?? 0);
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);

$etiquetasEstado = [
    'pendiente' => 'Pendiente',
    'autorizada' => 'Autorizada',
    'rechazada' => 'Rechazada',
    'finalizada' => 'Finalizada',
];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'pendiente' => 'badge badge-pendiente',
        'autorizada' => 'badge badge-info',
        'rechazada' => 'badge badge-error',
        'finalizada' => 'badge badge-exito',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Bajas</h1>
            <p class="page-subtitle">Gestión y consulta de las bajas de bienes institucionales.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
            <?php if ($puedeGestionar): ?>
                <a class="btn btn-primary" href="index.php?modulo=bajas&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar baja
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="bajas">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, bien o responsable">
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($etiquetasEstado as $valorEstado => $etiquetaEstado): ?>
                <option value="<?= $valorEstado ?>" <?= ($estado === $valorEstado) ? 'selected' : '' ?>><?= $etiquetaEstado ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_tipo_baja">Tipo de baja</label>
        <select id="id_tipo_baja" name="id_tipo_baja" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($tiposBaja as $tipo): ?>
                <option value="<?= (int) $tipo['id_tipo_baja'] ?>" <?= ($idTipoBaja === (int) $tipo['id_tipo_baja']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tipo['nombre_tipo_baja'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=bajas" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($bajas)): ?>
        <p class="estado-vacio">No se encontraron bajas<?= ($q !== '' || $estado !== '' || $idTipoBaja > 0) ? ' con esos filtros' : ' registradas' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-bajas">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha de preparación</th>
                        <th>Responsable del área</th>
                        <th>Bodega destino</th>
                        <th>Bienes</th>
                        <th>Registrada por</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bajas as $baja): ?>
                        <?php $estadoBaja = $baja['estado_baja'] ?? ''; ?>
                        <tr>
                            <td><?= $mostrar($baja['numero_baja'] ?? null) ?></td>
                            <td><?= $mostrar(formatDateTime($baja['fecha_preparacion'] ?? null)) ?></td>
                            <td><?= $mostrar($baja['responsable_descarga'] ?? null) ?></td>
                            <td><?= $mostrar($baja['ubicacion_bodega_destino'] ?? null) ?></td>
                            <td><?= (int) ($baja['total_bienes'] ?? 0) ?></td>
                            <td><?= $mostrar($baja['auxiliar_encargado'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($estadoBaja) ?>"><?= $mostrar($etiquetasEstado[$estadoBaja] ?? ($estadoBaja ?: null)) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=bajas&accion=ver&id=<?= (int) $baja['id_baja'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <?php if ($estadoBaja === 'pendiente' && $puedeGestionar): ?>
                                        <a class="table-action-btn table-action-editar" href="index.php?modulo=bajas&accion=editar&id=<?= (int) $baja['id_baja'] ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Editar
                                        </a>
                                    <?php endif; ?>
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
