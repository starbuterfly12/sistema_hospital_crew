<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ResponsablesController::ver()).
// Ficha de solo lectura — mismos datos que ya recibía la vista anterior ($responsable), mismo endpoint
// POST de cambio de estado (cambiar_estado) con su csrfField(); solo cambió el marcado visual.
// El detalle actual no muestra bienes asignados ni enlace a Tarjeta de responsabilidad — no se inventan.
$responsable = $responsable ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$idResponsable = (int) ($responsable['id_responsable'] ?? 0);
$estado = $responsable['estado_responsable'] ?? null;
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);

$claseBadgeEstado = match ($estado) {
    'activo' => 'badge badge-exito',
    'inactivo' => 'badge badge-error',
    default => 'badge',
};

$etiquetaEstado = match ($estado) {
    'activo' => 'Activo',
    'inactivo' => 'Inactivo',
    default => (string) ($estado ?? '—'),
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle del responsable</h1>
            <p class="page-subtitle">Consulta de la información registrada del responsable.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=responsables" class="btn btn-secondary">Volver</a>
            <?php if ($puedeGestionar): ?>
                <a href="index.php?modulo=responsables&accion=editar&id=<?= $idResponsable ?>" class="btn btn-lila">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Modificar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($responsable['nombre_completo'] ?? null) ?></p>
    <p class="detail-identidad-descripcion">NIT: <?= $mostrar($responsable['nit'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Información del responsable</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Nombre completo</span>
                <span class="detail-value"><?= $mostrar($responsable['nombre_completo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">NIT</span>
                <span class="detail-value"><?= $mostrar($responsable['nit'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Cargo</span>
                <span class="detail-value"><?= $mostrar($responsable['cargo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Profesión</span>
                <span class="detail-value"><?= $mostrar($responsable['profesion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Teléfono</span>
                <span class="detail-value"><?= $mostrar($responsable['telefono'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Área / ubicación</span>
                <span class="detail-value"><?= $mostrar($responsable['nombre_ubicacion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo de ubicación</span>
                <span class="detail-value"><?= $mostrar($responsable['tipo_ubicacion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado ?>"><?= $mostrar($etiquetaEstado) ?></span></span>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeGestionar && in_array($estado, ['activo', 'inactivo'], true)): ?>
    <div class="card">
        <h2 class="card-titulo">Estado del responsable</h2>
        <div class="detail-actions">
            <?php if ($estado === 'activo'): ?>
                <form method="POST" action="index.php?modulo=responsables&accion=cambiar_estado&id=<?= $idResponsable ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64M12 2v10"/></svg>
                        Inactivar responsable
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="index.php?modulo=responsables&accion=cambiar_estado&id=<?= $idResponsable ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        Activar responsable
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
