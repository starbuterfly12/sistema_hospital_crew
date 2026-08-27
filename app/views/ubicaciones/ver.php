<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UbicacionesController::ver()).
// Ficha de solo lectura — mismos datos que ya recibía la vista anterior ($ubicacion), mismo endpoint
// POST de cambio de estado (cambiar_estado) con su csrfField(); solo cambió el marcado visual.
// El detalle actual no muestra responsables ni bienes vinculados — no se inventan tablas nuevas.
$ubicacion = $ubicacion ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$idUbicacion = (int) ($ubicacion['id_ubicacion'] ?? 0);
$estado = $ubicacion['estado_ubicacion'] ?? null;
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);

$claseBadgeEstado = match ($estado) {
    'activa' => 'badge badge-exito',
    'inactiva' => 'badge badge-error',
    default => 'badge',
};

$etiquetaEstado = match ($estado) {
    'activa' => 'Activa',
    'inactiva' => 'Inactiva',
    default => (string) ($estado ?? '—'),
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de ubicación</h1>
            <p class="page-subtitle">Consulta de la información registrada del área o ubicación.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=ubicaciones" class="btn btn-secondary">Volver</a>
            <?php if ($puedeGestionar): ?>
                <a href="index.php?modulo=ubicaciones&accion=editar&id=<?= $idUbicacion ?>" class="btn btn-lila">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Modificar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($ubicacion['nombre_ubicacion'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($ubicacion['tipo_ubicacion'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Información de la ubicación</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Nombre</span>
                <span class="detail-value"><?= $mostrar($ubicacion['nombre_ubicacion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo</span>
                <span class="detail-value"><span class="badge"><?= $mostrar($ubicacion['tipo_ubicacion'] ?? null) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado ?>"><?= $mostrar($etiquetaEstado) ?></span></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Descripción</span>
                <span class="detail-value"><?= $mostrar($ubicacion['descripcion'] ?? null) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeGestionar && in_array($estado, ['activa', 'inactiva'], true)): ?>
    <div class="card">
        <h2 class="card-titulo">Estado de la ubicación</h2>
        <div class="detail-actions">
            <?php if ($estado === 'activa'): ?>
                <form method="POST" action="index.php?modulo=ubicaciones&accion=cambiar_estado&id=<?= $idUbicacion ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64M12 2v10"/></svg>
                        Inactivar
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="index.php?modulo=ubicaciones&accion=cambiar_estado&id=<?= $idUbicacion ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        Activar
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
