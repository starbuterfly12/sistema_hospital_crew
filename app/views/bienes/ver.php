<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BienesController::ver()).
// Ficha de solo lectura — sin <form>, sin inputs. Los datos son exactamente los que ya recibía la
// vista anterior (mismo $bien/$formaNombre/$datosIngreso); solo cambió el marcado visual.
$bien = $bien ?? [];
$formaNombre = $formaNombre ?? '';
$datosIngreso = $datosIngreso ?? [];

$valor = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $nombreEstado): string {
    return match ($nombreEstado) {
        'Activo' => 'badge badge-exito',
        'Baja' => 'badge badge-error',
        default => 'badge',
    };
};

$claseBadgeCondicion = static function (?string $condicion): string {
    return match ($condicion) {
        'Bueno' => 'badge badge-exito',
        'Regular' => 'badge badge-pendiente',
        'Malo' => 'badge badge-error',
        default => 'badge',
    };
};

$puedeEditar = tieneRol(['Administrador', 'Operativo']);

// El resultado de "Registrar bien" / "Modificar" / "Regenerar QR" se muestra ahora con el modal de
// feedback global (flash de sesión consumido en layouts/main.php), no como .alert en esta vista.

$documentoIngreso = $datosIngreso['documento_respaldo'] ?? null;
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle del bien</h1>
            <p class="page-subtitle">Consulta de la información registrada del bien institucional.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bienes" class="btn btn-secondary">Volver</a>
            <?php if ($puedeEditar): ?>
                <a href="index.php?modulo=bienes&accion=editar&id=<?= (int) $bien['id_bien'] ?>" class="btn btn-lila">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Modificar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $valor($bien['codigo_interno'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $valor($bien['descripcion'] ?? null) ?></p>
</div>

<div class="detail-layout-con-lateral">
<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Información del bien</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Código interno</span>
                <span class="detail-value"><?= $valor($bien['codigo_interno'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Código SICOIN</span>
                <span class="detail-value"><?= $valor($bien['codigo_sicoin'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Marca</span>
                <span class="detail-value"><?= $valor($bien['marca'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Modelo</span>
                <span class="detail-value"><?= $valor($bien['modelo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Serie</span>
                <span class="detail-value"><?= $valor($bien['serie'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Categoría</span>
                <span class="detail-value"><?= $valor($bien['nombre_categoria'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value">
                    <span class="<?= $claseBadgeEstado($bien['nombre_estado'] ?? null) ?>"><?= $valor($bien['nombre_estado'] ?? null) ?></span>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Condición</span>
                <span class="detail-value">
                    <span class="<?= $claseBadgeCondicion($bien['condicion_bien'] ?? null) ?>"><?= $valor($bien['condicion_bien'] ?? null) ?></span>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de ingreso</span>
                <span class="detail-value"><?= $valor(formatDate($bien['fecha_ingreso'] ?? null)) ?></span>
            </div>

            <?php if ($formaNombre === 'compra' || $formaNombre === 'traslado'): ?>
                <div class="detail-item">
                    <span class="detail-label">Costo</span>
                    <span class="detail-value"><?= formatearQuetzales($bien['costo'] ?? null) ?></span>
                </div>
            <?php elseif ($formaNombre === 'donacion'): ?>
                <div class="detail-item">
                    <span class="detail-label">Valor estimado</span>
                    <span class="detail-value"><?= formatearQuetzales($bien['valor_estimado'] ?? null) ?></span>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $valor(formatDateTime($bien['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de actualización</span>
                <span class="detail-value"><?= $valor(formatDateTime($bien['updated_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Descripción</span>
                <span class="detail-value"><?= $valor($bien['descripcion'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $valor($bien['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Asignación actual</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Responsable actual</span>
                <span class="detail-value"><?= $valor($bien['responsable_actual'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación actual</span>
                <span class="detail-value"><?= $valor($bien['ubicacion_actual'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo de ubicación</span>
                <span class="detail-value"><?= $valor($bien['tipo_ubicacion'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Datos de ingreso</h2>
        <div class="detail-grid">
            <div class="detail-item detail-full">
                <span class="detail-label">Forma de ingreso</span>
                <span class="detail-value"><?= $valor($bien['nombre_forma'] ?? null) ?></span>
            </div>

            <?php if ($formaNombre === 'compra'): ?>
                <div class="detail-item">
                    <span class="detail-label">Proveedor</span>
                    <span class="detail-value"><?= $valor($datosIngreso['proveedor'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Número de factura</span>
                    <span class="detail-value"><?= $valor($datosIngreso['numero_factura'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Serie de factura</span>
                    <span class="detail-value"><?= $valor($datosIngreso['serie_factura'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fecha de factura</span>
                    <span class="detail-value"><?= $valor(formatDate($datosIngreso['fecha_factura'] ?? null)) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Número de liquidación</span>
                    <span class="detail-value"><?= $valor($datosIngreso['numero_liquidacion'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">¿Tiene garantía?</span>
                    <span class="detail-value"><?= ((int) ($datosIngreso['tiene_garantia'] ?? 0) === 1) ? 'Sí' : 'No' ?></span>
                </div>

                <?php if ((int) ($datosIngreso['tiene_garantia'] ?? 0) === 1): ?>
                    <?php
                        $mesesGarantia = (int) ($datosIngreso['tiempo_garantia'] ?? 0);
                        $finGarantia = calcularFinGarantia($datosIngreso['fecha_factura'] ?? null, $mesesGarantia);
                    ?>
                    <div class="detail-item">
                        <span class="detail-label">Tiempo de garantía</span>
                        <span class="detail-value"><?= $valor($mesesGarantia > 0 ? $mesesGarantia . ($mesesGarantia === 1 ? ' mes' : ' meses') : null) ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Fecha estimada de fin de garantía</span>
                        <span class="detail-value"><?= $valor(formatDate($finGarantia)) ?></span>
                    </div>
                <?php endif; ?>
            <?php elseif ($formaNombre === 'donacion'): ?>
                <div class="detail-item">
                    <span class="detail-label">Procedencia</span>
                    <span class="detail-value"><?= $valor($datosIngreso['procedencia'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Entidad donante</span>
                    <span class="detail-value"><?= $valor($datosIngreso['entidad_donante'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Número de acta</span>
                    <span class="detail-value"><?= $valor($datosIngreso['numero_acta'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fecha de acta</span>
                    <span class="detail-value"><?= $valor(formatDate($datosIngreso['fecha_acta'] ?? null)) ?></span>
                </div>
            <?php elseif ($formaNombre === 'traslado'): ?>
                <div class="detail-item">
                    <span class="detail-label">Procedencia</span>
                    <span class="detail-value"><?= $valor($datosIngreso['procedencia'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Unidad ejecutora de origen</span>
                    <span class="detail-value"><?= $valor($datosIngreso['unidad_ejecutora_origen'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Código de unidad de origen</span>
                    <span class="detail-value"><?= $valor($datosIngreso['codigo_unidad_origen'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Número de acta</span>
                    <span class="detail-value"><?= $valor($datosIngreso['numero_acta'] ?? null) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fecha de acta</span>
                    <span class="detail-value"><?= $valor(formatDate($datosIngreso['fecha_acta'] ?? null)) ?></span>
                </div>
            <?php endif; ?>

            <?php if (in_array($formaNombre, ['compra', 'donacion', 'traslado'], true)): ?>
                <div class="detail-item detail-full">
                    <span class="detail-label">Documento de respaldo</span>
                    <?php if (!empty($documentoIngreso)): ?>
                        <a href="<?= htmlspecialchars(url('index.php?modulo=bienes&accion=ver_documento&id=' . (int) $bien['id_bien']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                            Ver documento de respaldo
                        </a>
                    <?php else: ?>
                        <span class="detail-value">Sin documento de respaldo</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<aside class="form-layout-lateral">
    <div class="card card-detalle">
        <h2 class="card-titulo">Código QR</h2>
        <?php if (!empty($bien['ruta_qr'])): ?>
            <?php
                // Cache busting: el nombre de archivo es estable (bien_{id}.png), así que sin este
                // parámetro el navegador puede seguir mostrando el PNG anterior tras regenerar el QR.
                $versionQr = !empty($bien['updated_at']) ? strtotime((string) $bien['updated_at']) : time();
                $rutaQrConVersion = $bien['ruta_qr'] . '?v=' . $versionQr;
            ?>
            <div class="qr-preview qr-preview-real qr-preview-grande">
                <img src="<?= htmlspecialchars($rutaQrConVersion, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
            </div>
            <p class="qr-estado-texto"><?= $valor($bien['codigo_interno'] ?? null) ?></p>
            <?php if (!empty($bien['codigo_sicoin'])): ?>
                <p class="form-hint form-hint-centrado">SICOIN: <?= htmlspecialchars($bien['codigo_sicoin'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="qr-preview qr-preview-grande" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <path d="M14 14h3v3h-3zM19 14h2M14 19h2M19 19h2"/>
                </svg>
            </div>
            <p class="qr-estado-texto">QR no disponible</p>
        <?php endif; ?>
    </div>

    <div class="card card-detalle">
        <h2 class="card-titulo">Estado del bien</h2>
        <div class="detail-item">
            <span class="<?= $claseBadgeEstado($bien['nombre_estado'] ?? null) ?>"><?= $valor($bien['nombre_estado'] ?? null) ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Condición</span>
            <span class="<?= $claseBadgeCondicion($bien['condicion_bien'] ?? null) ?>"><?= $valor($bien['condicion_bien'] ?? null) ?></span>
        </div>
    </div>

    <div class="card card-detalle">
        <h2 class="card-titulo">Acciones</h2>
        <div class="detail-actions">
            <?php if (!empty($bien['ruta_qr'])): ?>
                <button type="button" id="btn-ver-imprimir-qr" class="action-tile action-tile-azul">
                    <span class="action-tile-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 14h2M14 19h2M19 19h2"/></svg>
                    </span>
                    Ver / imprimir QR
                </button>
            <?php endif; ?>

            <?php if ($puedeEditar): ?>
                <form method="POST" action="index.php?modulo=bienes&accion=generar_qr&id=<?= (int) $bien['id_bien'] ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="action-tile action-tile-lila">
                        <span class="action-tile-icono" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
                        </span>
                        <?= !empty($bien['ruta_qr']) ? 'Regenerar QR' : 'Generar QR' ?>
                    </button>
                </form>

                <a href="index.php?modulo=verificaciones&accion=crear&id_bien=<?= (int) $bien['id_bien'] ?>" class="action-tile action-tile-menta">
                    <span class="action-tile-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M9 12l2 2 4-4"/></svg>
                    </span>
                    Registrar verificación física
                </a>
            <?php endif; ?>

            <a href="index.php?modulo=verificaciones&id_bien=<?= (int) $bien['id_bien'] ?>" class="action-tile action-tile-celeste">
                <span class="action-tile-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l4 2"/></svg>
                </span>
                Ver historial de verificaciones
            </a>
        </div>
    </div>
</aside>
</div>

<?php if (!empty($bien['ruta_qr'])): ?>
    <?php
        // Nombre de archivo sugerido para la descarga: código interno real, saneado a caracteres
        // seguros para nombre de archivo (el resto del comportamiento de descarga lo resuelve el
        // navegador de forma nativa vía el atributo download, sin endpoint nuevo).
        $nombreArchivoQr = 'QR_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($bien['codigo_interno'] ?? 'bien')) . '.png';
    ?>
    <div id="modal-qr" class="modal-overlay">
        <div class="modal-caja modal-caja-qr" role="dialog" aria-modal="true" aria-labelledby="modal-qr-titulo">
            <h2 id="modal-qr-titulo" class="modal-qr-titulo">Código QR del bien</h2>

            <div id="qr-imprimible" class="modal-qr-contenido">
                <img src="<?= htmlspecialchars($rutaQrConVersion, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien" class="modal-qr-imagen">
                <p class="modal-qr-dato">Código interno: <strong><?= $valor($bien['codigo_interno'] ?? null) ?></strong></p>
                <?php if (!empty($bien['codigo_sicoin'])): ?>
                    <p class="modal-qr-dato">Código SICOIN: <strong><?= htmlspecialchars($bien['codigo_sicoin'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <a href="<?= htmlspecialchars($rutaQrConVersion, ENT_QUOTES, 'UTF-8') ?>" download="<?= htmlspecialchars($nombreArchivoQr, ENT_QUOTES, 'UTF-8') ?>" id="btn-descargar-qr" class="btn btn-azul-suave">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                    Descargar QR
                </a>
                <button type="button" id="btn-imprimir-qr" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="1"/><path d="M6 17v4h12v-4"/><rect x="9" y="13" width="6" height="4"/></svg>
                    Imprimir
                </button>
                <button type="button" id="btn-cerrar-modal-qr" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modalQr = document.getElementById('modal-qr');
            var btnAbrirQr = document.getElementById('btn-ver-imprimir-qr');
            var btnCerrarQr = document.getElementById('btn-cerrar-modal-qr');
            var btnImprimirQr = document.getElementById('btn-imprimir-qr');

            function abrirModalQr() {
                modalQr.classList.add('modal-abierto');
            }

            function cerrarModalQr() {
                modalQr.classList.remove('modal-abierto');
            }

            btnAbrirQr.addEventListener('click', abrirModalQr);
            btnCerrarQr.addEventListener('click', cerrarModalQr);

            // Cerrar al hacer click sobre el fondo oscuro, no sobre la caja del modal.
            modalQr.addEventListener('click', function (evento) {
                if (evento.target === modalQr) {
                    cerrarModalQr();
                }
            });

            document.addEventListener('keydown', function (evento) {
                if (evento.key === 'Escape' && modalQr.classList.contains('modal-abierto')) {
                    cerrarModalQr();
                }
            });

            btnImprimirQr.addEventListener('click', function () {
                window.print();
            });
        })();
    </script>
<?php endif; ?>
