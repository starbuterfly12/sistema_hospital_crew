<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BajasController::crear()).
// CRÍTICO: el <script> se conserva íntegro salvo cambios presentacionales (clases/estructura del HTML
// que genera el JS y el patrón .file-picker para la imagen). NO se renombró ningún id/name/data-attr:
// #id_ubicacion_bodega_destino, #id_responsable_descarga, #servicio_derivado, #contenedor-bienes,
// #form-baja, #observaciones, name="bienes[ID][seleccionado|id_tipo_baja|justificacion]",
// name="imagen_bien[ID]", .check-bien, .bloque-bien-datos, data-id-bien, data-datos-bien,
// data-ubicacion — todos intactos. La elegibilidad de bienes la resuelve el controlador
// (Bien::getElegiblesParaBajaPorResponsable, que ya excluye préstamo activo, reserva de requisición y
// bienes en otra Baja pendiente/autorizada) y NO se toca. Sigue siendo un envío multipart.
$responsables = $responsables ?? [];
$tiposBaja = $tiposBaja ?? [];
$bodegas = $bodegas ?? [];
$nombreAuxiliarActual = $nombreAuxiliarActual ?? '';
$bienesPorResponsable = $bienesPorResponsable ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$idBodegaSeleccionada = (int) ($datosFormulario['id_ubicacion_bodega_destino'] ?? 0);
$idResponsableSeleccionado = (int) ($datosFormulario['id_responsable_descarga'] ?? 0);
$observacionesValor = $datosFormulario['observaciones'] ?? '';
$bienesSeleccionadosPrevios = $datosFormulario['bienes'] ?? [];
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar baja</h1>
            <p class="page-subtitle">Registre la información correspondiente al proceso de baja de bienes.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bajas" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="form-baja" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="id_ubicacion_bodega_destino">Bodega destino <span class="required-mark">*</span></label>
                <select id="id_ubicacion_bodega_destino" name="id_ubicacion_bodega_destino" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($bodegas as $bodega): ?>
                        <option value="<?= (int) $bodega['id_ubicacion'] ?>" <?= ($idBodegaSeleccionada === (int) $bodega['id_ubicacion']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bodega['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($bodegas)): ?>
                    <p class="form-hint form-hint-error">No hay ninguna Bodega activa configurada. No es posible registrar una baja.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="id_responsable_descarga">Responsable del área <span class="required-mark">*</span></label>
                <select id="id_responsable_descarga" name="id_responsable_descarga" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($responsables as $responsable): ?>
                        <?php $etiqueta = $responsable['nombre_completo'] . ' — ' . ($responsable['nombre_ubicacion'] ?? '-'); ?>
                        <option
                            value="<?= (int) $responsable['id_responsable'] ?>"
                            data-ubicacion="<?= htmlspecialchars($responsable['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($idResponsableSeleccionado === (int) $responsable['id_responsable']) ? 'selected' : '' ?>
                        ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="servicio_derivado">Servicio / Área</label>
                <input type="text" id="servicio_derivado" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">Auxiliar de Inventarios</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($nombreAuxiliarActual, ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Bienes para solicitar baja</h2>
        <div id="contenedor-bienes">
            <p class="form-hint">Seleccione primero un responsable del área para ver sus bienes cargados actualmente.</p>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Observaciones generales</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn btn-primary"
            data-confirm
            data-confirm-form="form-baja"
            data-confirm-validate-form
            data-confirm-icon="doc" data-confirm-variant="azul"
            data-confirm-title="Confirmar solicitud de baja"
            data-confirm-text="La solicitud será enviada para autorización."
            data-confirm-subtext="¿Desea enviar la solicitud de baja?"
            data-confirm-ok="Enviar solicitud"
            data-confirm-btnclass="btn-primary">
            Enviar solicitud de baja
        </button>
        <a href="index.php?modulo=bajas" class="btn btn-secondary">Cancelar</a>
    </div>

    <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Enviar solicitud de baja</button>
</form>

<script>
    var bienesPorResponsable = <?= json_encode($bienesPorResponsable, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var tiposBaja = <?= json_encode($tiposBaja, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var bienesSeleccionadosPrevios = <?= json_encode($bienesSeleccionadosPrevios, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

    (function () {
        var selectResponsable = document.getElementById('id_responsable_descarga');
        var campoServicio = document.getElementById('servicio_derivado');
        var contenedorBienes = document.getElementById('contenedor-bienes');

        function escapeHtml(texto) {
            var div = document.createElement('div');
            div.textContent = texto === null || texto === undefined ? '' : String(texto);
            return div.innerHTML;
        }

        // Solo presentación: NO altera bien.valor.
        function formatearQ(valor) {
            if (valor === null || valor === undefined || valor === '') { return '—'; }
            var numero = Number(valor);
            if (isNaN(numero)) { return '—'; }
            return 'Q ' + numero.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function construirSelectTipo(idBien, tipoSeleccionado) {
            var html = '<select name="bienes[' + idBien + '][id_tipo_baja]" class="form-control">'
                + '<option value="">Seleccione</option>';

            tiposBaja.forEach(function (tipo) {
                var seleccionado = String(tipo.id_tipo_baja) === String(tipoSeleccionado) ? ' selected' : '';
                html += '<option value="' + tipo.id_tipo_baja + '"' + seleccionado + '>' + escapeHtml(tipo.nombre_tipo_baja) + '</option>';
            });

            html += '</select>';
            return html;
        }

        function renderizarBienes() {
            var idResponsable = selectResponsable.value;
            var bienes = idResponsable ? (bienesPorResponsable[idResponsable] || []) : [];

            if (!idResponsable) {
                contenedorBienes.innerHTML = '<p class="form-hint">Seleccione primero un responsable del área para ver sus bienes cargados actualmente.</p>';
                return;
            }

            if (bienes.length === 0) {
                contenedorBienes.innerHTML = '<p class="estado-vacio">Este responsable no tiene bienes elegibles para baja.</p>';
                return;
            }

            var html = '';

            bienes.forEach(function (bien) {
                var previo = bienesSeleccionadosPrevios[bien.id_bien];
                var marcado = previo ? ' checked' : '';
                var marcaModelo = [bien.marca, bien.modelo].filter(Boolean).join(' / ') || '—';
                var tipoPrevio = previo ? previo.id_tipo_baja : '';
                var justificacionPrevia = previo ? previo.justificacion : '';
                var idFileImg = 'imagen_bien_' + bien.id_bien;

                html += '<div class="bloque-bien">'
                    + '<label class="bloque-bien-cabecera">'
                    + '<input type="checkbox" class="check-bien" data-id-bien="' + bien.id_bien + '" name="bienes[' + bien.id_bien + '][seleccionado]" value="1"' + marcado + '> '
                    + '<span><strong>' + escapeHtml(bien.codigo_interno || '—') + '</strong> — ' + escapeHtml(bien.descripcion || '—')
                    + (bien.codigo_sicoin ? ' <span class="bloque-bien-sicoin">SICOIN: ' + escapeHtml(bien.codigo_sicoin) + '</span>' : '') + '</span>'
                    + '</label>'
                    + '<p class="bloque-bien-meta">Marca / Modelo: ' + escapeHtml(marcaModelo)
                    + ' &nbsp;·&nbsp; Serie: ' + escapeHtml(bien.serie || '—')
                    + ' &nbsp;·&nbsp; Condición: ' + escapeHtml(bien.condicion_bien || '—')
                    + ' &nbsp;·&nbsp; Ubicación actual: ' + escapeHtml(bien.ubicacion_actual || '—')
                    + ' &nbsp;·&nbsp; Valor: ' + escapeHtml(formatearQ(bien.valor)) + '</p>'
                    + '<div class="bloque-bien-datos form-grid" data-datos-bien="' + bien.id_bien + '">'
                    + '<div class="form-group"><label class="form-label">Tipo de baja <span class="required-mark">*</span>' + construirSelectTipo(bien.id_bien, tipoPrevio) + '</label></div>'
                    + '<div class="form-group form-grid-full"><label class="form-label">Justificación <span class="required-mark">*</span><textarea name="bienes[' + bien.id_bien + '][justificacion]" class="form-control" rows="2">' + escapeHtml(justificacionPrevia) + '</textarea></label></div>'
                    + '<div class="form-group form-grid-full"><label class="form-label" for="' + idFileImg + '">Imagen del bien (opcional, JPG o PNG)</label>'
                    + '<div class="file-picker">'
                    + '<input type="file" id="' + idFileImg + '" name="imagen_bien[' + bien.id_bien + ']" class="file-input visually-hidden" accept=".jpg,.jpeg,.png">'
                    + '<label for="' + idFileImg + '" class="file-picker-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg> Seleccionar archivo</label>'
                    + '<span class="file-picker-name">Ningún archivo seleccionado</span>'
                    + '</div></div>'
                    + '</div>'
                    + '</div>';
            });

            contenedorBienes.innerHTML = html;

            actualizarVisibilidadDatos();

            contenedorBienes.querySelectorAll('.check-bien').forEach(function (checkbox) {
                checkbox.addEventListener('change', actualizarVisibilidadDatos);
            });
        }

        function actualizarVisibilidadDatos() {
            contenedorBienes.querySelectorAll('.check-bien').forEach(function (checkbox) {
                var idBien = checkbox.getAttribute('data-id-bien');
                var bloqueDatos = contenedorBienes.querySelector('[data-datos-bien="' + idBien + '"]');

                if (!bloqueDatos) {
                    return;
                }

                bloqueDatos.style.display = checkbox.checked ? '' : 'none';
            });
        }

        function actualizarServicio() {
            var opcion = selectResponsable.options[selectResponsable.selectedIndex];

            if (!opcion || !opcion.value) {
                campoServicio.value = '';
                return;
            }

            campoServicio.value = opcion.getAttribute('data-ubicacion') || '';
        }

        // Nombre de archivo visible en cada .file-picker (delegación: los inputs se crean por JS).
        contenedorBienes.addEventListener('change', function (evento) {
            var input = evento.target;
            if (!input.classList || !input.classList.contains('file-input')) {
                return;
            }
            var picker = input.closest('.file-picker');
            var nombre = picker ? picker.querySelector('.file-picker-name') : null;
            if (!nombre) {
                return;
            }
            if (input.files && input.files.length > 0) {
                nombre.textContent = input.files[0].name;
                nombre.classList.add('file-picker-name-activo');
            } else {
                nombre.textContent = 'Ningún archivo seleccionado';
                nombre.classList.remove('file-picker-name-activo');
            }
        });

        selectResponsable.addEventListener('change', function () {
            renderizarBienes();
            actualizarServicio();
        });

        // La confirmación previa al envío la maneja el modal global (#modal-confirm) vía el botón
        // "Enviar solicitud de baja" (data-confirm). Aquí ya NO se usa window.confirm().

        renderizarBienes();
        actualizarServicio();
    })();
</script>
