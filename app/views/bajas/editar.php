<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BajasController::editar()).
// Solo se puede editar mientras estado_baja = 'pendiente' (verificado en el controlador). CRÍTICO:
// el <script> se conserva íntegro salvo cambios presentacionales. NO se renombró ningún id/name/
// data-attr: #id_ubicacion_bodega_destino, #id_responsable_descarga, #servicio_derivado,
// #contenedor-bienes, #form-baja, #observaciones, name="bienes[ID][seleccionado|id_tipo_baja|
// justificacion]", name="imagen_bien[ID]", .check-bien, .bloque-bien-datos, data-id-bien,
// data-datos-bien, data-ubicacion. Elegibilidad de bienes: la resuelve el controlador
// (Bien::getElegiblesParaBajaPorResponsable con $idBajaExcluir), NO se toca. Envío multipart.
$baja = $baja ?? [];
$responsables = $responsables ?? [];
$tiposBaja = $tiposBaja ?? [];
$bodegas = $bodegas ?? [];
$bienesPorResponsable = $bienesPorResponsable ?? [];
$detallesActuales = $detallesActuales ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$idBaja = (int) ($baja['id_baja'] ?? 0);
$idBodegaSeleccionada = (int) ($datosFormulario['id_ubicacion_bodega_destino'] ?? 0);
$idResponsableSeleccionado = (int) ($datosFormulario['id_responsable_descarga'] ?? 0);
$observacionesValor = $datosFormulario['observaciones'] ?? '';
$bienesSeleccionadosPrevios = $datosFormulario['bienes'] ?? [];

$imagenesActuales = [];
foreach ($detallesActuales as $detalle) {
    if (!empty($detalle['imagen_bien'])) {
        // Ruta controlada y autenticada (no el path directo a storage/fotos_baja/, ya bloqueado):
        // el modal "Ver foto actual" carga la imagen desde este endpoint.
        $imagenesActuales[(int) $detalle['id_bien']] = url('index.php?modulo=bajas&accion=ver_foto&id=' . (int) $detalle['id_detalle_baja']);
    }
}
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Editar baja <?= htmlspecialchars($baja['numero_baja'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="page-subtitle">Modifique la información de la baja mientras está en estado Pendiente.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bajas&accion=ver&id=<?= $idBaja ?>" class="btn btn-secondary">Volver</a>
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
                <input type="text" class="form-control" value="<?= htmlspecialchars($baja['auxiliar_encargado'] ?? '-', ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Bienes para solicitar baja</h2>
        <div id="contenedor-bienes">
            <p class="form-hint">Cargando bienes del responsable seleccionado…</p>
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
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="index.php?modulo=bajas&accion=ver&id=<?= $idBaja ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
    var bienesPorResponsable = <?= json_encode($bienesPorResponsable, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var tiposBaja = <?= json_encode($tiposBaja, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var bienesSeleccionadosPrevios = <?= json_encode($bienesSeleccionadosPrevios, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var imagenesActuales = <?= json_encode($imagenesActuales, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

    (function () {
        var selectResponsable = document.getElementById('id_responsable_descarga');
        var campoServicio = document.getElementById('servicio_derivado');
        var contenedorBienes = document.getElementById('contenedor-bienes');

        function escapeHtml(texto) {
            var div = document.createElement('div');
            div.textContent = texto === null || texto === undefined ? '' : String(texto);
            return div.innerHTML;
        }

        // Igual que escapeHtml pero además neutraliza las comillas dobles, para valores que se
        // insertan dentro de atributos data-* con comillas dobles (ej. descripción del bien).
        function escapeAttr(texto) {
            return escapeHtml(texto).replace(/"/g, '&quot;');
        }

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
                var imagenActual = imagenesActuales[bien.id_bien];
                var lineaImagenActual = imagenActual
                    ? '<button type="button" class="table-action-btn table-action-ver bloque-bien-img-actual" data-foto-bien'
                        + ' data-imagen="' + escapeAttr(imagenActual) + '"'
                        + ' data-codigo="' + escapeAttr(bien.codigo_interno || '') + '"'
                        + ' data-descripcion="' + escapeAttr(bien.descripcion || '') + '">'
                        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>'
                        + ' Ver foto actual</button>'
                    : '';
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
                    + lineaImagenActual
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

        renderizarBienes();
        actualizarServicio();
    })();
</script>

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
    // Modal único y reutilizable para "Ver foto actual" de cada bien. Solo presentación: NO regenera,
    // copia, mueve, renombra, borra ni reemplaza la imagen guardada, y NO escribe en BD. El
    // <input type="file"> de cada bien sigue funcionando igual para elegir una imagen nueva.
    // Delegación en document porque las tarjetas de bien se generan dinámicamente por JS.
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
