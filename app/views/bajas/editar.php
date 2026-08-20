<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar baja</title>
    <style>
        .bloque-bien {
            border: 1px solid #999;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .bloque-bien-datos {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
        }

        .bloque-bien-datos label {
            display: block;
            margin-top: 0.25rem;
        }

        .bloque-bien-datos textarea,
        .bloque-bien-datos select {
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <?php
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
                $imagenesActuales[(int) $detalle['id_bien']] = url($detalle['imagen_bien']);
            }
        }
    ?>

    <?php if (!empty($error)): ?>
        <p><strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>

    <h1>Editar baja <?= htmlspecialchars($baja['numero_baja'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>

    <form method="POST" enctype="multipart/form-data" id="form-baja">
        <?= csrfField() ?>

        <h2>Datos generales</h2>

        <div>
            <label for="id_ubicacion_bodega_destino">Bodega destino *</label>
            <select id="id_ubicacion_bodega_destino" name="id_ubicacion_bodega_destino" required>
                <option value="">Seleccione</option>
                <?php foreach ($bodegas as $bodega): ?>
                    <option
                        value="<?= (int) $bodega['id_ubicacion'] ?>"
                        <?= ($idBodegaSeleccionada === (int) $bodega['id_ubicacion']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($bodega['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <p>Auxiliar de Inventarios: <strong><?= htmlspecialchars($baja['auxiliar_encargado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong> (quien preparó la solicitud originalmente; no se puede cambiar al editar).</p>
        </div>

        <div>
            <label for="id_responsable_descarga">Responsable del área *</label>
            <select id="id_responsable_descarga" name="id_responsable_descarga" required>
                <option value="">Seleccione</option>
                <?php foreach ($responsables as $responsable): ?>
                    <?php
                        $etiqueta = $responsable['nombre_completo']
                            . ' — ' . ($responsable['nombre_ubicacion'] ?? '-');
                    ?>
                    <option
                        value="<?= (int) $responsable['id_responsable'] ?>"
                        data-ubicacion="<?= htmlspecialchars($responsable['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($idResponsableSeleccionado === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="servicio_derivado">Servicio/Área</label>
            <input type="text" id="servicio_derivado" readonly>
        </div>

        <h2>Bienes para solicitar baja</h2>

        <p>Si cambia el responsable, los bienes ya agregados que no pertenezcan al nuevo responsable se quitarán de la baja.</p>

        <div id="contenedor-bienes">
            <p>Cargando bienes del responsable seleccionado…</p>
        </div>

        <h2>Observaciones generales</h2>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Guardar cambios</button>
            <a href="index.php?modulo=bajas&accion=ver&id=<?= $idBaja ?>">Cancelar</a>
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

            function construirSelectTipo(idBien, tipoSeleccionado) {
                var html = '<select name="bienes[' + idBien + '][id_tipo_baja]">'
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
                    contenedorBienes.innerHTML = '<p>Seleccione primero un responsable del área para ver sus bienes cargados actualmente.</p>';
                    return;
                }

                if (bienes.length === 0) {
                    contenedorBienes.innerHTML = '<p>Este responsable no tiene bienes elegibles para baja.</p>';
                    return;
                }

                var html = '';

                bienes.forEach(function (bien) {
                    var previo = bienesSeleccionadosPrevios[bien.id_bien];
                    var marcado = previo ? ' checked' : '';
                    var marcaModelo = [bien.marca, bien.modelo].filter(Boolean).join(' / ') || '-';
                    var tipoPrevio = previo ? previo.id_tipo_baja : '';
                    var justificacionPrevia = previo ? previo.justificacion : '';
                    var imagenActual = imagenesActuales[bien.id_bien];
                    var lineaImagenActual = imagenActual ? '<a href="' + imagenActual + '" target="_blank" rel="noopener">Ver imagen actual</a><br>' : '';

                    html += '<div class="bloque-bien">'
                        + '<label><input type="checkbox" class="check-bien" data-id-bien="' + bien.id_bien + '" name="bienes[' + bien.id_bien + '][seleccionado]" value="1"' + marcado + '> '
                        + '<strong>' + escapeHtml(bien.codigo_interno || '-') + '</strong> — ' + escapeHtml(bien.descripcion || '-')
                        + (bien.codigo_sicoin ? ' (SICOIN: ' + escapeHtml(bien.codigo_sicoin) + ')' : '')
                        + '</label>'
                        + '<div>Marca/Modelo: ' + escapeHtml(marcaModelo) + ' — Serie: ' + escapeHtml(bien.serie || '-')
                        + ' — Condición: ' + escapeHtml(bien.condicion_bien || '-') + ' — Ubicación actual: ' + escapeHtml(bien.ubicacion_actual || '-') + '</div>'
                        + '<div class="bloque-bien-datos" data-datos-bien="' + bien.id_bien + '">'
                        + '<label>Tipo de baja *' + construirSelectTipo(bien.id_bien, tipoPrevio) + '</label>'
                        + '<label>Justificación *<textarea name="bienes[' + bien.id_bien + '][justificacion]" rows="2">' + escapeHtml(justificacionPrevia) + '</textarea></label>'
                        + '<label>Imagen (opcional, JPG o PNG)' + lineaImagenActual + '<input type="file" name="imagen_bien[' + bien.id_bien + ']" accept=".jpg,.jpeg,.png"></label>'
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

            selectResponsable.addEventListener('change', function () {
                renderizarBienes();
                actualizarServicio();
            });

            renderizarBienes();
            actualizarServicio();
        })();
    </script>
</body>
</html>
