<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver RequisicionesController::ver()).
// Ficha de solo lectura de una requisición ya registrada — mismos datos ($requisicion / $numeros /
// $detalles) y mismos endpoints POST (autorizar / confirmar_entrega / anular) con su csrfField().
// Las acciones cuelgan sueltas bajo el detalle (sin tarjeta "Acciones"); Autorizar y Anular usan el
// modal de confirmación global (#modal-confirm) — nunca window.confirm(). El motivo de anulación se
// escribe dentro del modal y se vuelca al mismo campo name="motivo_anulacion" del <form> real oculto.
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
                                <td><?= formatearQuetzales($detalle['valor_mostrado'] ?? null) ?></td>
                                <td>
                                    <span class="<?= $claseBadgeDetalle($detalle['estado_detalle'] ?? null) ?>"><?= $mostrar($etiquetasEstadoDetalle[$detalle['estado_detalle'] ?? ''] ?? ($detalle['estado_detalle'] ?? null)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"></td>
                            <td><strong>Total: <?= formatearQuetzales($total) ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
    // Acciones sueltas y compactas bajo el detalle — sin tarjeta ni título "Acciones", sin franja de
    // ancho completo. Mismos estados/permisos/endpoints/CSRF que antes. Autorizar y Anular confirman
    // con el modal global (#modal-confirm); el motivo de anulación se captura DENTRO del modal y viaja
    // en el mismo campo name="motivo_anulacion" del <form> real (oculto). Editar sigue siendo enlace.
    $puedeEditar           = $estado === 'Pendiente'  && $puedeGestionar;
    $puedeAutorizar        = $estado === 'Pendiente'  && $esAdministrador;
    $puedeConfirmarEntrega = $estado === 'Autorizada' && $puedeGestionar;
    // $puedeAnular ya definido arriba (Pendiente|Autorizada + gestión)
    $hayAccionesInline = $puedeEditar || $puedeAutorizar || $puedeConfirmarEntrega || $puedeAnular;
?>
<?php if ($hayAccionesInline): ?>
    <div class="detail-inline-actions">
        <?php if ($puedeEditar): ?>
            <a href="index.php?modulo=requisiciones&accion=editar&id=<?= $idRequisicion ?>" class="btn btn-lila">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Editar
            </a>
        <?php endif; ?>

        <?php if ($puedeAutorizar): ?>
            <button type="button" class="btn btn-success"
                data-confirm
                data-confirm-form="form-req-autorizar"
                data-confirm-icon="check" data-confirm-variant="menta"
                data-confirm-title="Confirmar autorización"
                data-confirm-text="La requisición quedará autorizada para continuar con el proceso de entrega."
                data-confirm-subtext="¿Desea autorizar la requisición?"
                data-confirm-ok="Autorizar"
                data-confirm-btnclass="btn-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                Autorizar
            </button>
        <?php endif; ?>

        <?php if ($puedeConfirmarEntrega): ?>
            <button type="submit" form="form-req-entregar" class="btn btn-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4L19 6"/></svg>
                Confirmar entrega
            </button>
        <?php endif; ?>

        <?php if ($puedeAnular): ?>
            <button type="button" class="btn btn-danger"
                data-confirm
                data-confirm-form="form-req-anular"
                data-confirm-icon="alerta" data-confirm-variant="rosa"
                data-confirm-title="Anular requisición"
                data-confirm-text="Ingrese el motivo por el cual desea anular la requisición."
                data-confirm-input="1"
                data-confirm-input-label="Motivo de anulación *"
                data-confirm-input-target="req-anular-motivo"
                data-confirm-ok="Continuar"
                data-confirm-btnclass="btn-primary"
                data-confirm-step2-icon="alerta" data-confirm-step2-variant="rosa"
                data-confirm-step2-title="Confirmar anulación"
                data-confirm-step2-text="La requisición será anulada y ya no podrá continuar con su proceso."
                data-confirm-step2-subtext="¿Desea anular la requisición?"
                data-confirm-step2-ok="Anular requisición"
                data-confirm-step2-btnclass="btn-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                Anular
            </button>
        <?php endif; ?>
    </div>

    <?php if ($puedeAutorizar): ?>
        <form method="POST" action="index.php?modulo=requisiciones&accion=autorizar&id=<?= $idRequisicion ?>" id="form-req-autorizar" hidden>
            <?= csrfField() ?>
            <button type="submit" tabindex="-1" aria-hidden="true">Autorizar</button>
        </form>
    <?php endif; ?>

    <?php if ($puedeConfirmarEntrega): ?>
        <form method="POST" action="index.php?modulo=requisiciones&accion=confirmar_entrega&id=<?= $idRequisicion ?>" id="form-req-entregar" hidden>
            <?= csrfField() ?>
        </form>
    <?php endif; ?>

    <?php if ($puedeAnular): ?>
        <form method="POST" action="index.php?modulo=requisiciones&accion=anular&id=<?= $idRequisicion ?>" id="form-req-anular" hidden>
            <?= csrfField() ?>
            <textarea name="motivo_anulacion" id="req-anular-motivo" hidden></textarea>
            <button type="submit" tabindex="-1" aria-hidden="true">Anular</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
