<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver RequisicionesController::ver()).
// Ficha de solo lectura de una requisición ya registrada — mismos datos que ya recibía la vista
// anterior ($requisicion / $numeros / $detalles) y mismos endpoints POST (autorizar / confirmar_entrega
// / anular) con su csrfField(); solo cambió el marcado visual. No se añade confirm() donde antes no lo
// había (esta fase no crea confirmaciones nuevas).
$requisicion = $requisicion ?? [];
$numeros = $numeros ?? [];
$detalles = $detalles ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$idRequisicion = (int) ($requisicion['id_requisicion'] ?? 0);
$estado = $requisicion['estado_requisicion'] ?? '';
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);
$esAdministrador = tieneRol(['Administrador']);

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Pendiente' => 'badge badge-pendiente',
        'Autorizada' => 'badge badge-info',
        'Entregada' => 'badge badge-exito',
        'Anulada' => 'badge badge-error',
        default => 'badge',
    };
};

$etiquetasEstadoDetalle = [
    'pendiente' => 'Pendiente',
    'reservado' => 'Reservado',
    'entregado' => 'Entregado',
    'anulado' => 'Anulado',
];

$claseBadgeDetalle = static function (?string $estadoDetalle): string {
    return match ($estadoDetalle) {
        'pendiente' => 'badge badge-pendiente',
        'reservado' => 'badge badge-info',
        'entregado' => 'badge badge-exito',
        'anulado' => 'badge badge-error',
        default => 'badge',
    };
};

$puedeDescargarConstancia = in_array($estado, ['Autorizada', 'Entregada'], true);
$puedeAnular = in_array($estado, ['Pendiente', 'Autorizada'], true) && $puedeGestionar;
$tieneAccionesFlujo = $puedeGestionar && (
    ($estado === 'Pendiente')
    || ($estado === 'Autorizada')
);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de requisición</h1>
            <p class="page-subtitle">Consulta de la información registrada de la requisición.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=requisiciones" class="btn btn-secondary">Volver</a>
            <?php if ($puedeDescargarConstancia): ?>
                <a href="index.php?modulo=requisiciones&accion=descargar_constancia&id=<?= $idRequisicion ?>" class="btn btn-azul-suave">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                    Descargar constancia
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($requisicion['numero_requisicion_sistema'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($requisicion['responsable_solicitante_mostrado'] ?? null) ?> · <?= $mostrar($requisicion['ubicacion_solicitante_mostrada'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">No. sistema</span>
                <span class="detail-value"><?= $mostrar($requisicion['numero_requisicion_sistema'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Oficio No.</span>
                <span class="detail-value"><?= $mostrar($requisicion['numero_oficio'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado($estado) ?>"><?= $mostrar($estado) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Bienes</span>
                <span class="detail-value"><?= count($detalles) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable solicitante</span>
                <span class="detail-value"><?= $mostrar($requisicion['responsable_solicitante_mostrado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Área / Servicio</span>
                <span class="detail-value"><?= $mostrar($requisicion['ubicacion_solicitante_mostrada'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $mostrar($requisicion['usuario_registra_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($requisicion['created_at'] ?? null)) ?></span>
            </div>

            <?php if (!empty($requisicion['fecha_autorizacion'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Autorizado por</span>
                    <span class="detail-value"><?= $mostrar($requisicion['usuario_autoriza_nombre'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fecha de autorización</span>
                    <span class="detail-value"><?= $mostrar(formatDateTime($requisicion['fecha_autorizacion'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($requisicion['fecha_entrega'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Entrega confirmada por</span>
                    <span class="detail-value"><?= $mostrar($requisicion['usuario_entrega_nombre'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fecha de entrega</span>
                    <span class="detail-value"><?= $mostrar(formatDateTime($requisicion['fecha_entrega'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($requisicion['motivo_anulacion'])): ?>
                <div class="detail-item detail-full">
                    <span class="detail-label">Motivo de anulación</span>
                    <span class="detail-value"><?= $mostrar($requisicion['motivo_anulacion']) ?></span>
                </div>
            <?php endif; ?>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($requisicion['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Requisiciones institucionales</h2>
        <?php if (empty($numeros)): ?>
            <p class="estado-vacio">No hay números de requisición registrados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($numeros as $numero): ?>
                            <tr>
                                <td><?= $mostrar($numero['numero_requisicion'] ?? null) ?></td>
                                <td><?= $mostrar(formatDate($numero['fecha_requisicion'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes</h2>
        <?php if (empty($detalles)): ?>
            <p class="estado-vacio">Esta requisición no tiene bienes registrados.</p>
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
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = 0; ?>
                        <?php foreach ($detalles as $detalle): ?>
                            <?php $total += (float) ($detalle['valor_mostrado'] ?? 0); ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['valor_mostrado'] ?? null) ?></td>
                                <td>
                                    <span class="<?= $claseBadgeDetalle($detalle['estado_detalle'] ?? null) ?>"><?= $mostrar($etiquetasEstadoDetalle[$detalle['estado_detalle'] ?? ''] ?? ($detalle['estado_detalle'] ?? null)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6"><strong>Total: <?= number_format($total, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($tieneAccionesFlujo): ?>
    <div class="card">
        <h2 class="card-titulo">Acciones</h2>
        <div class="detail-actions">
            <?php if ($estado === 'Pendiente'): ?>
                <a href="index.php?modulo=requisiciones&accion=editar&id=<?= $idRequisicion ?>" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Editar
                </a>

                <?php if ($esAdministrador): ?>
                    <form method="POST" action="index.php?modulo=requisiciones&accion=autorizar&id=<?= $idRequisicion ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                            Autorizar
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($estado === 'Autorizada'): ?>
                <form method="POST" action="index.php?modulo=requisiciones&accion=confirmar_entrega&id=<?= $idRequisicion ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4L19 6"/></svg>
                        Confirmar entrega
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($puedeAnular): ?>
    <form method="POST" action="index.php?modulo=requisiciones&accion=anular&id=<?= $idRequisicion ?>" class="form-card">
        <?= csrfField() ?>
        <h2 class="form-section-title">Anular requisición</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="motivo_anulacion">Motivo de anulación <span class="required-mark">*</span></label>
                <textarea id="motivo_anulacion" name="motivo_anulacion" class="form-control" rows="3" required></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                Anular
            </button>
        </div>
    </form>
<?php endif; ?>
