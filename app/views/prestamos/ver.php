<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver PrestamosController::ver()).
// Ficha de solo lectura — mismos datos que ya recibía la vista anterior ($prestamo / $detalles) y
// mismos endpoints (descargar_constancia -> XLSX; enlace real a devoluciones/crear). Solo cambió el
// marcado visual. "Vencido" se calcula en Prestamo::findById() (misma fórmula que el listado) y llega
// como la columna booleana `vencido`; se muestra como indicador adicional, nunca reemplaza al estado.
$prestamo = $prestamo ?? [];
$detalles = $detalles ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

$etiquetasEstado = [
    'activo' => 'Activo',
    'parcial' => 'Parcialmente devuelto',
    'finalizado' => 'Finalizado',
    'anulado' => 'Anulado',
];

$etiquetasEstadoDetalle = [
    'prestado' => 'Prestado',
    'devuelto' => 'Devuelto',
];

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-info',
        'parcial' => 'badge badge-pendiente',
        'finalizado' => 'badge badge-exito',
        'anulado' => 'badge badge-error',
        default => 'badge',
    };
};

$claseBadgeDetalle = static function (?string $estadoDetalle): string {
    return match ($estadoDetalle) {
        'prestado' => 'badge badge-pendiente',
        'devuelto' => 'badge badge-exito',
        default => 'badge',
    };
};

$idPrestamo = (int) ($prestamo['id_prestamo'] ?? 0);
$estadoPrestamo = $prestamo['estado_prestamo'] ?? '';
$tipoPrestamo = $prestamo['tipo_prestamo'] ?? '';
$vencido = (bool) ($prestamo['vencido'] ?? false);
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);
$puedeRegistrarDevolucion = in_array($estadoPrestamo, ['activo', 'parcial'], true) && $puedeGestionar;
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle del préstamo</h1>
            <p class="page-subtitle">Consulta de la información registrada del préstamo.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=prestamos" class="btn btn-secondary">Volver</a>
            <a href="index.php?modulo=prestamos&accion=descargar_constancia&id=<?= $idPrestamo ?>" class="btn btn-azul-suave">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                Descargar constancia
            </a>
            <?php if ($puedeRegistrarDevolucion): ?>
                <a href="index.php?modulo=devoluciones&accion=crear&id_prestamo=<?= $idPrestamo ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/></svg>
                    Registrar devolución
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($prestamo['numero_prestamo'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($prestamo['responsable_origen_mostrado'] ?? null) ?> &rarr; <?= $mostrar($prestamo['responsable_destino_mostrado'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número</span>
                <span class="detail-value"><?= $mostrar($prestamo['numero_prestamo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo</span>
                <span class="detail-value"><?= $mostrar($etiquetasTipo[$tipoPrestamo] ?? $tipoPrestamo) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Oficio No.</span>
                <span class="detail-value"><?= $mostrar($prestamo['numero_oficio'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value">
                    <span class="<?= $claseBadgeEstado($estadoPrestamo) ?>"><?= $mostrar($etiquetasEstado[$estadoPrestamo] ?? ($estadoPrestamo ?: null)) ?></span>
                    <?php if ($vencido): ?>
                        <span class="badge badge-vencido">Vencido</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha del préstamo</span>
                <span class="detail-value"><?= $mostrar(formatDate($prestamo['fecha_prestamo'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha estimada de devolución</span>
                <span class="detail-value"><?= $mostrar(formatDate($prestamo['fecha_devolucion_estimada'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $mostrar($prestamo['usuario_registra_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($prestamo['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Justificación del préstamo</span>
                <span class="detail-value"><?= $mostrar($prestamo['motivo'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($prestamo['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Custodia</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Responsable permanente (origen)</span>
                <span class="detail-value"><?= $mostrar($prestamo['responsable_origen_mostrado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación origen</span>
                <span class="detail-value"><?= $mostrar($prestamo['ubicacion_origen_mostrada'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable temporal (destino)</span>
                <span class="detail-value"><?= $mostrar($prestamo['responsable_destino_mostrado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación destino</span>
                <span class="detail-value"><?= $mostrar($prestamo['ubicacion_destino_mostrada'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes del préstamo</h2>
        <?php if (empty($detalles)): ?>
            <p class="estado-vacio">Este préstamo no tiene bienes registrados.</p>
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
                            <th>Estado del ítem</th>
                            <th>Devolución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <?php $devuelto = ($detalle['estado_detalle'] ?? '') === 'devuelto'; ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                                <td>
                                    <div class="celda-bien-foto">
                                        <?= fotoBienThumb((int) ($detalle['id_bien'] ?? 0), $detalle['imagen_bien'] ?? null, $detalle['codigo_interno_mostrado'] ?? null, $detalle['descripcion_mostrada'] ?? null, 'sm', 'raya') ?>
                                        <span><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></span>
                                    </div>
                                </td>
                                <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                                <td><?= formatearQuetzales($detalle['valor_prestamo'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['condicion_entrega'] ?? null) ?></td>
                                <td>
                                    <span class="<?= $claseBadgeDetalle($detalle['estado_detalle'] ?? null) ?>"><?= $mostrar($etiquetasEstadoDetalle[$detalle['estado_detalle'] ?? ''] ?? ($detalle['estado_detalle'] ?? null)) ?></span>
                                </td>
                                <td>
                                    <?php if ($devuelto): ?>
                                        <a href="index.php?modulo=devoluciones&accion=ver&id=<?= (int) ($detalle['id_devolucion'] ?? 0) ?>">
                                            <?= $mostrar(formatDate($detalle['fecha_devolucion'] ?? null)) ?>
                                        </a>
                                        <?php if (!empty($detalle['condicion_devolucion'])): ?>
                                            <br><small><?= $mostrar($detalle['condicion_devolucion']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/modal_foto_bien.php'; ?>
