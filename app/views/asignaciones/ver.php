<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver AsignacionesController::ver()).
// Ficha de SOLO CONSULTA — mismos datos que ya recibía la vista anterior ($asignacion /
// $bienesAsignacion). Sin acciones administrativas (el módulo no las tiene), sin enlace a Tarjeta
// (no existe en esta vista). Solo cambió el marcado visual.
$asignacion = $asignacion ?? [];
$bienesAsignacion = $bienesAsignacion ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$estado = $asignacion['estado_asignacion'] ?? null;

$claseBadgeEstado = match ($estado) {
    'Pendiente' => 'badge badge-pendiente',
    'Asignada' => 'badge badge-exito',
    default => 'badge',
};

$claseBadgeDetalle = static function (?string $estadoDetalle): string {
    return match ($estadoDetalle) {
        'activo' => 'badge badge-exito',
        'retirado' => 'badge',
        default => 'badge',
    };
};

$etiquetaDetalle = static function (?string $estadoDetalle): string {
    return match ($estadoDetalle) {
        'activo' => 'Activo',
        'retirado' => 'Retirado',
        default => (string) ($estadoDetalle ?? '—'),
    };
};

$nombreUbicacion = $asignacion['nombre_ubicacion'] ?? null;
$tipoUbicacion = $asignacion['tipo_ubicacion'] ?? null;
$ubicacionTexto = ($nombreUbicacion !== null && $nombreUbicacion !== '')
    ? $nombreUbicacion . ($tipoUbicacion !== null && $tipoUbicacion !== '' ? ' - ' . $tipoUbicacion : '')
    : null;
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de asignación</h1>
            <p class="page-subtitle">Consulta de la información registrada de la asignación.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=asignaciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número de asignación</span>
                <span class="detail-value"><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado ?>"><?= $mostrar($estado) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable</span>
                <span class="detail-value"><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación</span>
                <span class="detail-value"><?= $mostrar($ubicacionTexto) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de asignación</span>
                <span class="detail-value"><?= $mostrar(formatDate($asignacion['fecha_asignacion'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $mostrar($asignacion['usuario_registra_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($asignacion['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última actualización</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($asignacion['updated_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($asignacion['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes de la asignación</h2>
        <?php if (empty($bienesAsignacion)): ?>
            <p class="estado-vacio">Esta asignación no contiene bienes.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>Código interno</th>
                            <th>SICOIN</th>
                            <th>Descripción</th>
                            <th>Serie</th>
                            <th>Fecha agregado</th>
                            <th>Fecha retirado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bienesAsignacion as $detalle): ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_interno'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['codigo_sicoin'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['descripcion'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['serie'] ?? null) ?></td>
                                <td><?= $mostrar(formatDate($detalle['fecha_agregado'] ?? null)) ?></td>
                                <td><?= $mostrar(formatDate($detalle['fecha_retirado'] ?? null)) ?></td>
                                <td>
                                    <span class="<?= $claseBadgeDetalle($detalle['estado_detalle'] ?? null) ?>"><?= $mostrar($etiquetaDetalle($detalle['estado_detalle'] ?? null)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
