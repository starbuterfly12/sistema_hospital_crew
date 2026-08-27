<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver DevolucionesController::crear()).
// CRÍTICO: el <script> del final se conserva íntegro salvo cambios puramente presentacionales
// (clases CSS en la ficha y la tabla que genera el JS, y formato). NO se renombró ningún
// id/name/data-attribute: #id_prestamo, #contenedor-info-prestamo, #contenedor-bienes,
// #seleccionar-todos, .chk-detalle, #fecha_devolucion, #motivo, #observaciones,
// name="detalles[]", name="condicion_devolucion[ID]", name="observaciones_bien[ID]",
// .campo-condicion, .campo-observacion-bien, data-flatpickr-target — todos intactos.
// Los préstamos pendientes y sus bienes pendientes los resuelve el controlador
// (Prestamo::getPendientesDevolucion + DetallePrestamo::listarPendientesPorPrestamo) y NO se tocan.
$prestamosPendientes = $prestamosPendientes ?? [];
$detallesPorPrestamo = $detallesPorPrestamo ?? [];
$idPrestamoPreseleccionado = (int) ($idPrestamoPreseleccionado ?? 0);
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$idPrestamoSeleccionado = (int) ($datosFormulario['id_prestamo'] ?? $idPrestamoPreseleccionado);
$detallesSeleccionados = array_map('intval', $datosFormulario['detalles'] ?? []);
$condicionesDevolucionValor = $datosFormulario['condicion_devolucion'] ?? [];
$observacionesPorBienValor = $datosFormulario['observaciones_bien'] ?? [];
$fechaDevolucionValor = $datosFormulario['fecha_devolucion'] ?? '';
$motivoValor = $datosFormulario['motivo'] ?? '';
$observacionesValor = $datosFormulario['observaciones'] ?? '';

$etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

$prestamosInfo = [];

foreach ($prestamosPendientes as $prestamo) {
    $prestamosInfo[(int) $prestamo['id_prestamo']] = [
        'numero_prestamo' => $prestamo['numero_prestamo'],
        'tipo' => $etiquetasTipo[$prestamo['tipo_prestamo']] ?? $prestamo['tipo_prestamo'],
        'numero_oficio' => $prestamo['numero_oficio'],
        'responsable_origen' => $prestamo['responsable_origen_mostrado'],
        'ubicacion_origen' => $prestamo['ubicacion_origen_mostrada'],
        'responsable_destino' => $prestamo['responsable_destino_mostrado'],
        'ubicacion_destino' => $prestamo['ubicacion_destino_mostrada'],
        'fecha_prestamo' => formatDate($prestamo['fecha_prestamo']),
        'fecha_devolucion_estimada' => formatDate($prestamo['fecha_devolucion_estimada']),
        'motivo' => $prestamo['motivo'],
    ];
}
?>
<style>
    /* Controles de la tabla de bienes que construye el JS: acotados para no estirar la fila. */
    .campo-condicion { max-width: 11em; }
    .campo-observacion-bien { width: 100%; min-width: 12em; }
    .chk-todos { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; margin-bottom: 10px; }
</style>

<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar devolución</h1>
            <p class="page-subtitle">Registre la devolución de bienes asociados al préstamo.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=devoluciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($prestamosPendientes)): ?>
    <div class="card">
        <p class="estado-vacio">No hay préstamos con bienes pendientes de devolución.</p>
    </div>
<?php else: ?>
    <form method="POST" id="form-devolucion" class="form-card">
        <?= csrfField() ?>

        <div class="form-section">
            <h2 class="form-section-title">Préstamo</h2>
            <div class="form-grid">
                <div class="form-group form-grid-full">
                    <label class="form-label" for="id_prestamo">Préstamo <span class="required-mark">*</span></label>
                    <select id="id_prestamo" name="id_prestamo" class="form-control" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($prestamosPendientes as $prestamo): ?>
                            <?php
                                $etiqueta = $prestamo['numero_prestamo']
                                    . ' — ' . ($etiquetasTipo[$prestamo['tipo_prestamo']] ?? $prestamo['tipo_prestamo'])
                                    . ' — ' . $prestamo['responsable_destino_mostrado'];
                            ?>
                            <option
                                value="<?= (int) $prestamo['id_prestamo'] ?>"
                                <?= ($idPrestamoSeleccionado === (int) $prestamo['id_prestamo']) ? 'selected' : '' ?>
                            ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="contenedor-info-prestamo"></div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Bienes pendientes de devolución</h2>
            <div id="contenedor-bienes">
                <p class="form-hint">Seleccione primero un préstamo para ver sus bienes pendientes.</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Datos de la devolución</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="fecha_devolucion">Fecha de devolución <span class="required-mark">*</span></label>
                    <div class="campo-fecha">
                        <input type="text" id="fecha_devolucion" name="fecha_devolucion" class="form-control" value="<?= htmlspecialchars($fechaDevolucionValor, ENT_QUOTES, 'UTF-8') ?>" placeholder="DD/MM/AAAA" required>
                        <button type="button" class="btn-calendario" data-flatpickr-target="fecha_devolucion" aria-label="Abrir calendario">📅</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="motivo">Motivo</label>
                    <input type="text" id="motivo" name="motivo" class="form-control" maxlength="150" value="<?= htmlspecialchars($motivoValor, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar devolución</button>
            <a href="index.php?modulo=devoluciones" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        var prestamosInfo = <?= json_encode($prestamosInfo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var detallesPorPrestamo = <?= json_encode($detallesPorPrestamo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var detallesSeleccionadosPrevios = <?= json_encode($detallesSeleccionados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var condicionesDevolucionPrevias = <?= json_encode($condicionesDevolucionValor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var observacionesPorBienPrevias = <?= json_encode($observacionesPorBienValor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var condicionesValidas = <?= json_encode(Bien::CONDICIONES_VALIDAS, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

        (function () {
            var selectPrestamo = document.getElementById('id_prestamo');
            var contenedorInfo = document.getElementById('contenedor-info-prestamo');
            var contenedorBienes = document.getElementById('contenedor-bienes');

            function escapeHtml(texto) {
                var div = document.createElement('div');
                div.textContent = texto === null || texto === undefined ? '' : String(texto);
                return div.innerHTML;
            }

            function construirSelectCondicion(nombreCampo, valorSeleccionado) {
                var html = '<select class="form-control campo-condicion" name="' + nombreCampo + '">'
                    + '<option value="">Seleccione...</option>';

                condicionesValidas.forEach(function (condicion) {
                    var seleccionada = condicion === valorSeleccionado ? ' selected' : '';
                    html += '<option value="' + escapeHtml(condicion) + '"' + seleccionada + '>' + escapeHtml(condicion) + '</option>';
                });

                html += '</select>';
                return html;
            }

            function filaFicha(etiqueta, valor) {
                if (!valor) {
                    return '';
                }
                return '<div class="detail-item"><span class="detail-label">' + escapeHtml(etiqueta) + '</span>'
                    + '<span class="detail-value">' + escapeHtml(valor) + '</span></div>';
            }

            function renderizarInfoPrestamo() {
                var idPrestamo = selectPrestamo.value;
                var info = idPrestamo ? prestamosInfo[idPrestamo] : null;

                if (!info) {
                    contenedorInfo.innerHTML = '';
                    return;
                }

                var html = '<div class="detail-card detail-card-embebida"><div class="detail-grid">'
                    + filaFicha('Tipo', info.tipo)
                    + filaFicha('Oficio No.', info.numero_oficio)
                    + filaFicha('Responsable permanente (origen)', info.responsable_origen)
                    + filaFicha('Ubicación origen', info.ubicacion_origen)
                    + filaFicha('Responsable temporal (destino)', info.responsable_destino)
                    + filaFicha('Ubicación destino', info.ubicacion_destino)
                    + filaFicha('Fecha del préstamo', info.fecha_prestamo)
                    + filaFicha('Fecha estimada de devolución', info.fecha_devolucion_estimada)
                    + (info.motivo ? '<div class="detail-item detail-full"><span class="detail-label">Justificación del préstamo</span><span class="detail-value">' + escapeHtml(info.motivo) + '</span></div>' : '')
                    + '</div></div>';

                contenedorInfo.innerHTML = html;
            }

            function renderizarBienes() {
                var idPrestamo = selectPrestamo.value;
                var detalles = idPrestamo ? (detallesPorPrestamo[idPrestamo] || []) : [];

                if (!idPrestamo) {
                    contenedorBienes.innerHTML = '<p class="form-hint">Seleccione primero un préstamo para ver sus bienes pendientes.</p>';
                    return;
                }

                if (detalles.length === 0) {
                    contenedorBienes.innerHTML = '<p class="estado-vacio">Este préstamo no tiene bienes pendientes de devolución.</p>';
                    return;
                }

                var html = '<label class="chk-todos"><input type="checkbox" id="seleccionar-todos"> Seleccionar todos</label>'
                    + '<div class="table-responsive"><table class="table-app table-detail-centered"><thead><tr>'
                    + '<th></th><th>No. Interno</th><th>No. SICOIN</th><th>Descripción</th>'
                    + '<th>Serie</th><th>Condición al entregar</th><th>Condición al devolver</th><th>Observación</th>'
                    + '</tr></thead><tbody>';

                detalles.forEach(function (detalle) {
                    var idDetalle = detalle.id_detalle_prestamo;
                    var marcado = detallesSeleccionadosPrevios.indexOf(idDetalle) !== -1 ? ' checked' : '';
                    var condicionPrevia = condicionesDevolucionPrevias && condicionesDevolucionPrevias[idDetalle] !== undefined
                        ? condicionesDevolucionPrevias[idDetalle]
                        : (detalle.condicion_entrega || '');
                    var observacionPrevia = observacionesPorBienPrevias && observacionesPorBienPrevias[idDetalle] !== undefined
                        ? observacionesPorBienPrevias[idDetalle]
                        : '';

                    html += '<tr>'
                        + '<td><input type="checkbox" class="chk-detalle" name="detalles[]" value="' + idDetalle + '"' + marcado + '></td>'
                        + '<td>' + escapeHtml(detalle.codigo_interno || '-') + '</td>'
                        + '<td>' + escapeHtml(detalle.codigo_sicoin || '-') + '</td>'
                        + '<td>' + escapeHtml(detalle.descripcion || '-') + '</td>'
                        + '<td>' + escapeHtml(detalle.serie || '-') + '</td>'
                        + '<td>' + escapeHtml(detalle.condicion_entrega || '-') + '</td>'
                        + '<td>' + construirSelectCondicion('condicion_devolucion[' + idDetalle + ']', condicionPrevia) + '</td>'
                        + '<td><input type="text" class="form-control campo-observacion-bien" maxlength="255" name="observaciones_bien[' + idDetalle + ']" value="' + escapeHtml(observacionPrevia) + '"></td>'
                        + '</tr>';
                });

                html += '</tbody></table></div>';
                contenedorBienes.innerHTML = html;

                var checkboxTodos = document.getElementById('seleccionar-todos');
                var checkboxesDetalle = contenedorBienes.querySelectorAll('.chk-detalle');

                checkboxTodos.addEventListener('change', function () {
                    checkboxesDetalle.forEach(function (chk) {
                        chk.checked = checkboxTodos.checked;
                    });
                });
            }

            selectPrestamo.addEventListener('change', function () {
                renderizarInfoPrestamo();
                renderizarBienes();
            });

            renderizarInfoPrestamo();
            renderizarBienes();

            inicializarSelectoresFecha(['fecha_devolucion']);
        })();
    </script>
<?php endif; ?>
