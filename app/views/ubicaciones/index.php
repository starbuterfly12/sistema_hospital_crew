<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UbicacionesController::index()).
// Filtro real existente: búsqueda por nombre o tipo (?q=). No se agregan filtros nuevos.
// El módulo no tiene responsable/encargado en la tabla ubicaciones — no se muestra esa columna.

$ubicaciones = $ubicaciones ?? [];
$q = $q ?? '';
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activa' => 'badge badge-exito',
        'inactiva' => 'badge badge-error',
        default => 'badge',
    };
};

$etiquetaEstado = static function (?string $estado): string {
    return match ($estado) {
        'activa' => 'Activa',
        'inactiva' => 'Inactiva',
        default => (string) ($estado ?? '—'),
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Áreas / ubicaciones</h1>
            <p class="page-subtitle">Gestión y consulta de las áreas y ubicaciones registradas en el sistema.</p>
        </div>

        <?php if ($puedeGestionar): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="index.php?modulo=ubicaciones&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar ubicación
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="ubicaciones">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Nombre o tipo">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=ubicaciones" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($ubicaciones)): ?>
        <p class="estado-vacio">No se encontraron ubicaciones<?= $q !== '' ? ' con ese criterio' : '' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-ubicaciones">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ubicaciones as $ubicacion): ?>
                        <tr>
                            <td><?= $mostrar($ubicacion['nombre_ubicacion'] ?? null) ?></td>
                            <td><?= $mostrar($ubicacion['tipo_ubicacion'] ?? null) ?></td>
                            <td><?= $mostrar($ubicacion['descripcion'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($ubicacion['estado_ubicacion'] ?? null) ?>"><?= $mostrar($etiquetaEstado($ubicacion['estado_ubicacion'] ?? null)) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=ubicaciones&accion=ver&id=<?= (int) $ubicacion['id_ubicacion'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <?php if ($puedeGestionar): ?>
                                        <a class="table-action-btn table-action-editar" href="index.php?modulo=ubicaciones&accion=editar&id=<?= (int) $ubicacion['id_ubicacion'] ?>">
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
