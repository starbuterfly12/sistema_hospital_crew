<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver PrestamosController::index()).
// Filtros por GET (búsqueda + estado): el controlador los lee de $_GET y los pasa a Prestamo::getAll(),
// que sin argumentos sigue devolviendo el listado completo (comportamiento previo).
// "Vencido" NO es un estado persistente: Prestamo::getAll() lo calcula como
//   estado_prestamo IN ('activo','parcial') AND fecha_devolucion_estimada < CURDATE()
// y llega como la columna booleana `vencido`. Se muestra como indicador adicional, nunca reemplaza al badge de estado.
$prestamos = $prestamos ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

$etiquetasEstado = [
    'activo' => 'Activo',
    'parcial' => 'Parcialmente devuelto',
    'finalizado' => 'Finalizado',
    'anulado' => 'Anulado',
];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-info',
        'parcial' => 'badge badge-pendiente',
        'finalizado' => 'badge badge-exito',
        'anulado' => 'badge badge-error',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Préstamos</h1>
            <p class="page-subtitle">Gestión y consulta de los préstamos temporales de bienes institucionales.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
            <?php if ($puedeRegistrar): ?>
                <a class="btn btn-primary" href="index.php?modulo=prestamos&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar préstamo
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="prestamos">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, responsable o ubicación">
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

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=prestamos" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($prestamos)): ?>
        <p class="estado-vacio">No se encontraron préstamos<?= ($q !== '' || $estado !== '') ? ' con esos filtros' : ' registrados' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-prestamos">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha de préstamo</th>
                        <th>Responsable origen</th>
                        <th>Responsable destino</th>
                        <th>Ubicación destino</th>
                        <th>Devolución estimada</th>
                        <th>Bienes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <?php $vencido = (bool) ($prestamo['vencido'] ?? false); ?>
                        <tr>
                            <td><?= $mostrar($prestamo['numero_prestamo'] ?? null) ?></td>
                            <td><?= $mostrar(formatDate($prestamo['fecha_prestamo'] ?? null)) ?></td>
                            <td><?= $mostrar($prestamo['responsable_origen_mostrado'] ?? null) ?></td>
                            <td><?= $mostrar($prestamo['responsable_destino_mostrado'] ?? null) ?></td>
                            <td><?= $mostrar($prestamo['ubicacion_destino_mostrada'] ?? null) ?></td>
                            <td><?= $mostrar(formatDate($prestamo['fecha_devolucion_estimada'] ?? null)) ?></td>
                            <td><?= (int) ($prestamo['total_bienes'] ?? 0) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($prestamo['estado_prestamo'] ?? null) ?>"><?= $mostrar($etiquetasEstado[$prestamo['estado_prestamo'] ?? ''] ?? ($prestamo['estado_prestamo'] ?? null)) ?></span>
                                <?php if ($vencido): ?>
                                    <span class="badge badge-vencido">Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=prestamos&accion=ver&id=<?= (int) $prestamo['id_prestamo'] ?>">
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
