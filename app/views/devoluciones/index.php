<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver DevolucionesController::index()).
// Filtros por GET (búsqueda + estado): el controlador los lee de $_GET y los pasa a Devolucion::getAll(),
// que sin argumentos sigue devolviendo el listado completo (comportamiento previo).
// "estado_devolucion" (parcial / completa) SÍ es una columna persistente real de `devoluciones`,
// escrita por DevolucionesController::crear() según cuántos bienes quedan pendientes en el préstamo.
$devoluciones = $devoluciones ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

$etiquetasEstadoDevolucion = DevolucionesController::ETIQUETAS_ESTADO; // ['parcial'=>'Parcial','completa'=>'Completa']

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeDevolucion = static function (?string $estado): string {
    return match ($estado) {
        'parcial' => 'badge badge-pendiente',
        'completa' => 'badge badge-exito',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Devoluciones</h1>
            <p class="page-subtitle">Gestión y consulta de las devoluciones de bienes prestados.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
            <?php if ($puedeRegistrar): ?>
                <a class="btn btn-primary" href="index.php?modulo=devoluciones&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar devolución
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="devoluciones">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, préstamo o responsable">
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($etiquetasEstadoDevolucion as $valorEstado => $etiquetaEstado): ?>
                <option value="<?= $valorEstado ?>" <?= ($estado === $valorEstado) ? 'selected' : '' ?>><?= $etiquetaEstado ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=devoluciones" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($devoluciones)): ?>
        <p class="estado-vacio">No se encontraron devoluciones<?= ($q !== '' || $estado !== '') ? ' con esos filtros' : ' registradas' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-devoluciones">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Préstamo</th>
                        <th>Fecha de devolución</th>
                        <th>Responsable temporal</th>
                        <th>Bienes devueltos</th>
                        <th>Registrada por</th>
                        <th>Resultado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devoluciones as $devolucion): ?>
                        <tr>
                            <td><?= $mostrar($devolucion['numero_devolucion'] ?? null) ?></td>
                            <td><?= $mostrar($devolucion['numero_prestamo'] ?? null) ?></td>
                            <td><?= $mostrar(formatDate($devolucion['fecha_devolucion'] ?? null)) ?></td>
                            <td><?= $mostrar($devolucion['responsable_destino_mostrado'] ?? null) ?></td>
                            <td><?= (int) ($devolucion['cantidad_bienes'] ?? 0) ?></td>
                            <td><?= $mostrar($devolucion['usuario_recibe_nombre'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeDevolucion($devolucion['estado_devolucion'] ?? null) ?>"><?= $mostrar($etiquetasEstadoDevolucion[$devolucion['estado_devolucion'] ?? ''] ?? ($devolucion['estado_devolucion'] ?? null)) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=devoluciones&accion=ver&id=<?= (int) $devolucion['id_devolucion'] ?>">
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
