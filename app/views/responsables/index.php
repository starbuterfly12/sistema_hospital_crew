<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ResponsablesController::index()).
// Filtro real existente: búsqueda por nombre o NIT (?q=). No se agregan filtros nuevos.
// Columnas Profesión y Teléfono (datos personales) se dejan solo para la ficha "Ver", no en el listado.

$responsables = $responsables ?? [];
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
        'activo' => 'badge badge-exito',
        'inactivo' => 'badge badge-error',
        default => 'badge',
    };
};

$etiquetaEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'Activo',
        'inactivo' => 'Inactivo',
        default => (string) ($estado ?? '—'),
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Responsables</h1>
            <p class="page-subtitle">Gestión y consulta de los responsables registrados en el sistema.</p>
        </div>

        <?php if ($puedeGestionar): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="index.php?modulo=responsables&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar responsable
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="responsables">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Nombre completo o NIT">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=responsables" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($responsables)): ?>
        <p class="estado-vacio">No se encontraron responsables<?= $q !== '' ? ' con ese criterio' : '' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-responsables">
                <thead>
                    <tr>
                        <th>NIT</th>
                        <th>Nombre completo</th>
                        <th>Cargo</th>
                        <th>Área / ubicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responsables as $responsable): ?>
                        <tr>
                            <td><?= $mostrar($responsable['nit'] ?? null) ?></td>
                            <td><?= $mostrar($responsable['nombre_completo'] ?? null) ?></td>
                            <td><?= $mostrar($responsable['cargo'] ?? null) ?></td>
                            <td><?= $mostrar($responsable['nombre_ubicacion'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($responsable['estado_responsable'] ?? null) ?>"><?= $mostrar($etiquetaEstado($responsable['estado_responsable'] ?? null)) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=responsables&accion=ver&id=<?= (int) $responsable['id_responsable'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <?php if ($puedeGestionar): ?>
                                        <a class="table-action-btn table-action-editar" href="index.php?modulo=responsables&accion=editar&id=<?= (int) $responsable['id_responsable'] ?>">
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
