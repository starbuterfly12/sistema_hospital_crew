<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php.
// Lo comparten dos llamadores del controlador SIN cambios de lógica:
//   - BajasController::ver()      -> flujo normal de Baja ($origenSolicitudes = false)
//   - BajasController::revisar()  -> bandeja Solicitudes, solo Administrador ($origenSolicitudes = true)
// Todas las acciones (endpoints, estados, roles, contraseña de Aceptar) se conservan EXACTAMENTE como
// estaban; solo cambió el marcado visual y las confirmaciones usan el modal de confirmación GLOBAL del sistema
// (#modal-confirm en layouts/main.php). La bandeja en sí (bajas/solicitudes.php) NO se migra aquí.
$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$baja = $baja ?? [];
$detalles = $detalles ?? [];
$idBaja = (int) ($baja['id_baja'] ?? 0);
$estado = $baja['estado_baja'] ?? '';
$origenSolicitudes = $origenSolicitudes ?? false;

$etiquetasEstado = [
    'pendiente' => 'Pendiente',
    'autorizada' => 'Autorizada',
    'rechazada' => 'Rechazada',
    'finalizada' => 'Finalizada',
];

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'pendiente' => 'badge badge-pendiente',
        'autorizada' => 'badge badge-info',
        'rechazada' => 'badge badge-error',
        'finalizada' => 'badge badge-exito',
        default => 'badge',
    };
};

// Finalizar es exclusivo de quien registró la solicitud (id_usuario_registra), sin importar su rol
// (revisión funcional del módulo). Regla intacta.
$idUsuarioActual = (int) ($_SESSION['id_usuario'] ?? 0);
$idUsuarioRegistra = (int) ($baja['id_usuario_registra'] ?? 0);
$esSolicitanteOriginal = $idUsuarioRegistra > 0 && $idUsuarioRegistra === $idUsuarioActual;
$puedeGestionar = tieneRol(['Administrador', 'Operativo']);

$volverHref = $origenSolicitudes ? 'index.php?modulo=bajas&accion=solicitudes' : 'index.php?modulo=bajas';

$tituloDetalle = $origenSolicitudes ? 'Detalle de solicitud de baja' : 'Detalle de baja';
$subtituloDetalle = $origenSolicitudes
    ? 'Revisión de la solicitud antes de autorizarla o rechazarla.'
    : 'Consulta de la información registrada del proceso de baja.';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title"><?= htmlspecialchars($tituloDetalle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="page-subtitle"><?= htmlspecialchars($subtituloDetalle, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="page-actions">
            <a href="<?= $volverHref ?>" class="btn btn-secondary">Volver</a>
            <?php if ($estado === 'finalizada'): ?>
                <a href="index.php?modulo=bajas&accion=descargarComprobante&id=<?= $idBaja ?>" class="btn btn-azul-suave">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                    Descargar comprobante
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($baja['numero_baja'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($etiquetasEstado[$estado] ?? ($estado ?: null)) ?> · <?= $mostrar($baja['responsable_descarga'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número</span>
                <span class="detail-value"><?= $mostrar($baja['numero_baja'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado($estado) ?>"><?= $mostrar($etiquetasEstado[$estado] ?? ($estado ?: null)) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable del área</span>
                <span class="detail-value"><?= $mostrar($baja['responsable_descarga'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Servicio</span>
                <span class="detail-value"><?= $mostrar($baja['ubicacion_responsable_descarga'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Auxiliar de Inventarios</span>
                <span class="detail-value"><?= $mostrar($baja['auxiliar_encargado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Bodega destino</span>
                <span class="detail-value"><?= $mostrar($baja['ubicacion_bodega_destino'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de preparación</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($baja['fecha_preparacion'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $mostrar($baja['usuario_registra'] ?? null) ?></span>
            </div>

            <?php if (!empty($baja['fecha_autorizacion'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Autorizado por</span>
                    <span class="detail-value"><?= $mostrar($baja['usuario_autoriza'] ?? null) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha de autorización</span>
                    <span class="detail-value"><?= $mostrar(formatDateTime($baja['fecha_autorizacion'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($baja['fecha_rechazo'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Rechazada por</span>
                    <span class="detail-value"><?= $mostrar($baja['usuario_rechaza'] ?? null) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha de rechazo</span>
                    <span class="detail-value"><?= $mostrar(formatDateTime($baja['fecha_rechazo'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($baja['fecha_baja'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Fecha de baja (finalización)</span>
                    <span class="detail-value"><?= $mostrar(formatDate($baja['fecha_baja'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Número de acta</span>
                <span class="detail-value"><?= $mostrar($baja['numero_acta'] ?? null) ?></span>
            </div>

            <?php if (!empty($baja['documento_respaldo'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Documento de respaldo</span>
                    <span class="detail-value">
                        <a href="<?= url($baja['documento_respaldo']) ?>" target="_blank" rel="noopener" class="btn btn-azul-suave">Ver documento de respaldo</a>
                    </span>
                </div>
            <?php endif; ?>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($baja['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes</h2>
        <?php if (empty($detalles)): ?>
            <p class="estado-vacio">Esta baja no tiene bienes registrados.</p>
        <?php else: ?>
            <?php $total = 0; ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered table-resizable table-bajas-detalle">
                    <thead>
                        <tr>
                            <th>No. de Bien</th>
                            <th>No. SICOIN</th>
                            <th>Descripción</th>
                            <th>Serie</th>
                            <th>Valor</th>
                            <th>Tipo de baja</th>
                            <th>Justificación</th>
                            <th>Responsable anterior</th>
                            <th>Ubicación anterior</th>
                            <th>Estado anterior</th>
                            <th>Imagen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <?php $total += (float) ($detalle['valor_mostrado'] ?? 0); ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                                <td><?= formatearQuetzales($detalle['valor_mostrado'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['nombre_tipo_baja'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['justificacion'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['responsable_anterior'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['ubicacion_anterior'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['estado_bien_anterior'] ?? null) ?></td>
                                <td>
                                    <?php if (!empty($detalle['imagen_bien'])): ?>
                                        <button type="button" class="table-action-btn table-action-ver" data-foto-bien
                                            data-imagen="<?= htmlspecialchars(url($detalle['imagen_bien']), ENT_QUOTES, 'UTF-8') ?>"
                                            data-codigo="<?= htmlspecialchars((string) ($detalle['codigo_interno_mostrado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-descripcion="<?= htmlspecialchars((string) ($detalle['descripcion_mostrada'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                            Ver foto
                                        </button>
                                    <?php else: ?>
                                        Sin imagen
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"></td>
                            <td><strong><?= formatearQuetzales($total) ?></strong></td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
    // Acciones inferiores COMPACTAS — sin tarjeta blanca, sin título "Acciones", sin franja de ancho
    // completo. Mismas condiciones/estados/endpoints/permisos que antes. Las confirmaciones usan el
    // modal de confirmación GLOBAL del sistema (#modal-confirm en layouts/main.php), nunca
    // window.confirm(). "Descargar comprobante" (estado finalizada) vive SOLO en el .page-header.
    $puedeEditarPendiente  = !$origenSolicitudes && $estado === 'pendiente'  && $puedeGestionar;
    $puedeFinalizar        = !$origenSolicitudes && $estado === 'autorizada' && $esSolicitanteOriginal && $puedeGestionar;
    $puedeDecidirSolicitud = $origenSolicitudes && $estado === 'pendiente';
    $solicitudSinDecision  = $origenSolicitudes && $estado !== 'pendiente';

    $svgLapiz  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $svgCheck  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4L19 6"/></svg>';
    $svgEquis  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>';
?>

<?php if ($puedeEditarPendiente): ?>
    <div class="detail-inline-actions">
        <a href="index.php?modulo=bajas&accion=editar&id=<?= $idBaja ?>" class="table-action-btn table-action-editar">
            <?= $svgLapiz ?>
            Editar
        </a>
    </div>
<?php elseif ($puedeFinalizar): ?>
    <div class="detail-inline-actions">
        <button type="button" class="btn btn-primary"
            data-confirm
            data-confirm-form="form-finalizar-baja"
            data-confirm-icon="check" data-confirm-variant="azul"
            data-confirm-title="Confirmar finalización de baja"
            data-confirm-text="Los bienes serán retirados de la responsabilidad actual y trasladados a la bodega destino."
            data-confirm-subtext="¿Desea finalizar la baja?"
            data-confirm-ok="Finalizar baja"
            data-confirm-btnclass="btn-primary">
            <?= $svgCheck ?>
            Finalizar baja
        </button>
    </div>

    <form method="POST" action="index.php?modulo=bajas&accion=finalizar&id=<?= $idBaja ?>" id="form-finalizar-baja" hidden>
        <?= csrfField() ?>
        <button type="submit" tabindex="-1" aria-hidden="true">Finalizar baja</button>
    </form>
<?php elseif ($puedeDecidirSolicitud): ?>
    <form method="POST" action="index.php?modulo=bajas&accion=autorizar&id=<?= $idBaja ?>" id="form-aceptar-baja" class="detail-inline-form">
        <?= csrfField() ?>
        <div class="form-group" style="max-width:320px;">
            <label class="form-label" for="password">Contraseña de Administrador <span class="required-mark">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="detail-inline-actions">
            <button type="button" class="btn btn-success"
                data-confirm
                data-confirm-form="form-aceptar-baja"
                data-confirm-require-field="password"
                data-confirm-icon="check" data-confirm-variant="menta"
                data-confirm-title="Confirmar autorización"
                data-confirm-text="La solicitud será autorizada y podrá continuar con el proceso de baja."
                data-confirm-subtext="¿Desea autorizar la solicitud?"
                data-confirm-ok="Autorizar solicitud"
                data-confirm-btnclass="btn-success">
                <?= $svgCheck ?>
                Autorizar solicitud
            </button>

            <button type="button" class="btn btn-danger"
                data-confirm
                data-confirm-form="form-rechazar-baja"
                data-confirm-icon="alerta" data-confirm-variant="rosa"
                data-confirm-title="Confirmar rechazo"
                data-confirm-text="La solicitud será rechazada y no podrá continuar con el proceso de baja."
                data-confirm-subtext="¿Desea rechazar la solicitud?"
                data-confirm-ok="Rechazar solicitud"
                data-confirm-btnclass="btn-danger">
                <?= $svgEquis ?>
                Rechazar solicitud
            </button>
        </div>

        <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Autorizar</button>
    </form>

    <form method="POST" action="index.php?modulo=bajas&accion=rechazar&id=<?= $idBaja ?>" id="form-rechazar-baja" hidden>
        <?= csrfField() ?>
        <button type="submit" tabindex="-1" aria-hidden="true">Rechazar</button>
    </form>
<?php elseif ($solicitudSinDecision): ?>
    <p class="estado-vacio detail-inline-actions">Esta solicitud ya no admite decisiones nuevas.</p>
<?php endif; ?>

<script src="<?= url('public/js/app.js') ?>"></script>

<div id="modal-foto-bien" class="modal-overlay">
    <div class="modal-caja modal-caja-qr" role="dialog" aria-modal="true" aria-labelledby="modal-foto-bien-titulo">
        <h2 id="modal-foto-bien-titulo" class="modal-qr-titulo">Imagen actual del bien</h2>

        <div class="modal-qr-contenido">
            <img id="modal-foto-bien-img" src="" alt="Imagen del bien" class="modal-foto-imagen">
            <p class="modal-qr-dato">Código interno: <strong id="modal-foto-bien-codigo">—</strong></p>
            <p class="modal-qr-dato" id="modal-foto-bien-desc-wrap" hidden><span id="modal-foto-bien-desc"></span></p>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-cerrar-modal-foto>Cerrar</button>
        </div>
    </div>
</div>

<script>
    // Modal único y reutilizable para "Ver foto" de los bienes de la baja. Solo presentación: nunca
    // toca la imagen guardada. Delegación en document para cubrir la tabla estática de esta vista.
    (function () {
        var modal = document.getElementById('modal-foto-bien');
        if (!modal) { return; }

        var img = document.getElementById('modal-foto-bien-img');
        var codigo = document.getElementById('modal-foto-bien-codigo');
        var descWrap = document.getElementById('modal-foto-bien-desc-wrap');
        var desc = document.getElementById('modal-foto-bien-desc');

        function abrir(src, cod, dsc) {
            img.setAttribute('src', src || '');
            codigo.textContent = cod && cod !== '—' ? cod : '—';
            if (dsc && dsc !== '—') {
                desc.textContent = dsc;
                descWrap.hidden = false;
            } else {
                desc.textContent = '';
                descWrap.hidden = true;
            }
            modal.classList.add('modal-abierto');
        }

        function cerrar() {
            modal.classList.remove('modal-abierto');
            img.setAttribute('src', '');
        }

        document.addEventListener('click', function (evento) {
            var disparador = evento.target.closest('[data-foto-bien]');
            if (disparador) {
                abrir(
                    disparador.getAttribute('data-imagen'),
                    disparador.getAttribute('data-codigo'),
                    disparador.getAttribute('data-descripcion')
                );
                return;
            }

            if (evento.target === modal || evento.target.closest('[data-cerrar-modal-foto]')) {
                cerrar();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && modal.classList.contains('modal-abierto')) {
                cerrar();
            }
        });
    })();
</script>
