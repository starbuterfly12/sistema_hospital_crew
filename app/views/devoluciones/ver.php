<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver DevolucionesController::ver()).
// Ficha de solo lectura — mismos datos que ya recibía la vista anterior ($devolucion / $detalles) y
// mismo enlace real al préstamo (prestamos&accion=ver). No hay comprobante/constancia para Devoluciones
// (no existe ruta descargar_constancia en el case 'devoluciones' de index.php). Solo cambió el marcado.
$devolucion = $devolucion ?? [];
$detalles = $detalles ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;
$etiquetasEstadoDevolucion = DevolucionesController::ETIQUETAS_ESTADO;

$etiquetasEstadoPrestamo = [
    'activo' => 'Activo',
    'parcial' => 'Parcialmente devuelto',
    'finalizado' => 'Finalizado',
    'anulado' => 'Anulado',
];

$claseBadgeDevolucion = static function (?string $estado): string {
    return match ($estado) {
        'parcial' => 'badge badge-pendiente',
        'completa' => 'badge badge-exito',
        default => 'badge',
    };
};

$claseBadgePrestamo = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-info',
        'parcial' => 'badge badge-pendiente',
        'finalizado' => 'badge badge-exito',
        'anulado' => 'badge badge-error',
        default => 'badge',
    };
};

$idPrestamo = (int) ($devolucion['id_prestamo'] ?? 0);
$tipoPrestamo = $devolucion['tipo_prestamo'] ?? '';
$estadoPrestamo = $devolucion['estado_prestamo'] ?? '';
$estadoDevolucion = $devolucion['estado_devolucion'] ?? '';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de devolución</h1>
            <p class="page-subtitle">Consulta de la información registrada de la devolución.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=devoluciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($devolucion['numero_devolucion'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($devolucion['numero_prestamo'] ?? null) ?> · <?= $mostrar($devolucion['responsable_destino_mostrado'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número de devolución</span>
                <span class="detail-value"><?= $mostrar($devolucion['numero_devolucion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Resultado</span>
                <span class="detail-value"><span class="<?= $claseBadgeDevolucion($estadoDevolucion) ?>"><?= $mostrar($etiquetasEstadoDevolucion[$estadoDevolucion] ?? ($estadoDevolucion ?: null)) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de devolución</span>
                <span class="detail-value"><?= $mostrar(formatDate($devolucion['fecha_devolucion'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrada por</span>
                <span class="detail-value"><?= $mostrar($devolucion['usuario_recibe_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($devolucion['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Motivo</span>
                <span class="detail-value"><?= $mostrar($devolucion['motivo'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($devolucion['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Préstamo relacionado</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número de préstamo</span>
                <span class="detail-value">
                    <a href="index.php?modulo=prestamos&accion=ver&id=<?= $idPrestamo ?>"><?= $mostrar($devolucion['numero_prestamo'] ?? null) ?></a>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo</span>
                <span class="detail-value"><?= $mostrar($etiquetasTipo[$tipoPrestamo] ?? ($tipoPrestamo ?: null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable permanente (origen)</span>
                <span class="detail-value"><?= $mostrar($devolucion['responsable_origen_mostrado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación origen</span>
                <span class="detail-value"><?= $mostrar($devolucion['ubicacion_origen_mostrada'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable temporal (destino)</span>
                <span class="detail-value"><?= $mostrar($devolucion['responsable_destino_mostrado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación destino</span>
                <span class="detail-value"><?= $mostrar($devolucion['ubicacion_destino_mostrada'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado actual del préstamo</span>
                <span class="detail-value"><span class="<?= $claseBadgePrestamo($estadoPrestamo) ?>"><?= $mostrar($etiquetasEstadoPrestamo[$estadoPrestamo] ?? ($estadoPrestamo ?: null)) ?></span></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes devueltos</h2>
        <?php if (empty($detalles)): ?>
            <p class="estado-vacio">Esta devolución no tiene bienes registrados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>No. Interno</th>
                            <th>No. SICOIN</th>
                            <th>Descripción</th>
                            <th>Serie</th>
                            <th>Valor</th>
                            <th>Condición al entregar</th>
                            <th>Condición al devolver</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                                <td><?= formatearQuetzales($detalle['valor_prestamo'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['condicion_entrega'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['condicion_devolucion'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['observaciones'] ?? null) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
