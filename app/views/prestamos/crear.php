<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar préstamo</title>
    <link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
    <style>
        .campo-fecha {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-calendario {
            cursor: pointer;
            line-height: 1;
        }

        .campo-condicion {
            width: 10em;
        }
    </style>
</head>
<body>
    <?php
        $responsablesOrigen = $responsablesOrigen ?? [];
        $responsablesDestino = $responsablesDestino ?? [];
        $bienesPorResponsable = $bienesPorResponsable ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];

        $tipoPrestamoSeleccionado = $datosFormulario['tipo_prestamo'] ?? '';
        $idResponsableOrigenSeleccionado = (int) ($datosFormulario['id_responsable_origen'] ?? 0);
        $idResponsableDestinoSeleccionado = (int) ($datosFormulario['id_responsable_destino'] ?? 0);
        $bienesSeleccionados = array_map('intval', $datosFormulario['bienes'] ?? []);
        $numeroOficioValor = $datosFormulario['numero_oficio'] ?? '';
        $justificacionValor = $datosFormulario['motivo'] ?? '';
        $observacionesValor = $datosFormulario['observaciones'] ?? '';
        $fechaPrestamoValor = $datosFormulario['fecha_prestamo'] ?? '';
        $fechaDevolucionValor = $datosFormulario['fecha_devolucion_estimada'] ?? '';
        $condicionesEntregaValor = $datosFormulario['condicion_entrega'] ?? [];
        $inventariosDisponible = (bool) ($inventariosDisponible ?? false);
        $origenInventarios = $origenInventarios ?? null;
    ?>

    <?php if (!empty($error)): ?>
        <p><strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>

    <h1>Registrar préstamo</h1>

    <form method="POST" id="form-prestamo">
        <?= csrfField() ?>

        <h2>Tipo de préstamo</h2>

        <div>
            <label for="tipo_prestamo">Tipo de préstamo *</label>
            <select id="tipo_prestamo" name="tipo_prestamo" required>
                <option value="">Seleccione</option>
                <option value="entre_servicios" <?= $tipoPrestamoSeleccionado === 'entre_servicios' ? ' selected' : '' ?>>Entre servicios</option>
                <option value="desde_inventarios"<?= $inventariosDisponible ? '' : ' disabled' ?> <?= $tipoPrestamoSeleccionado === 'desde_inventarios' ? ' selected' : '' ?>>Desde Inventarios<?= $inventariosDisponible ? '' : ' (no disponible)' ?></option>
            </select>
        </div>

        <div>
            <label for="numero_oficio">Oficio No. *</label>
            <input
                type="text"
                id="numero_oficio"
                name="numero_oficio"
                maxlength="50"
                value="<?= htmlspecialchars($numeroOficioValor, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <h2>Responsable origen</h2>

        <div id="bloque-origen-select">
            <label for="id_responsable_origen">Responsable origen *</label>
            <select id="id_responsable_origen" name="id_responsable_origen">
                <option value="">Seleccione</option>
                <?php foreach ($responsablesOrigen as $responsable): ?>
                    <?php
                        $etiqueta = $responsable['nombre_completo']
                            . ' — ' . ($responsable['nombre_ubicacion'] ?? '-');
                    ?>
                    <option
                        value="<?= (int) $responsable['id_responsable'] ?>"
                        <?= ($idResponsableOrigenSeleccionado === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="bloque-origen-inventarios" style="display:none;">
            <label for="origen_inventarios_texto">Responsable origen</label>
            <input
                type="text"
                id="origen_inventarios_texto"
                value="<?= $origenInventarios ? htmlspecialchars($origenInventarios['nombre_completo'] . ' — ' . $origenInventarios['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') : '' ?>"
                readonly
            >
            <input
                type="hidden"
                id="id_responsable_origen_inventarios"
                name="id_responsable_origen"
                value="<?= $origenInventarios ? (int) $origenInventarios['id_responsable'] : '' ?>"
                disabled
            >
        </div>

        <h2>Bienes disponibles</h2>

        <div id="contenedor-bienes">
            <p>Seleccione primero un responsable origen para ver sus bienes actualmente asignados.</p>
        </div>

        <h2>Responsable temporal</h2>

        <div>
            <label for="id_responsable_destino">Responsable temporal *</label>
            <select id="id_responsable_destino" name="id_responsable_destino" required>
                <option value="">Seleccione</option>
                <?php foreach ($responsablesDestino as $responsable): ?>
                    <option
                        value="<?= (int) $responsable['id_responsable'] ?>"
                        data-ubicacion="<?= htmlspecialchars($responsable['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-tipo-ubicacion="<?= htmlspecialchars($responsable['tipo_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($idResponsableDestinoSeleccionado === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="area_destino_derivada">Área/Servicio destino</label>
            <input type="text" id="area_destino_derivada" readonly>
        </div>

        <h2>Fechas</h2>

        <div>
            <label for="fecha_prestamo">Fecha del préstamo *</label>
            <div class="campo-fecha">
                <input
                    type="text"
                    id="fecha_prestamo"
                    name="fecha_prestamo"
                    value="<?= htmlspecialchars($fechaPrestamoValor, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="DD/MM/AAAA"
                    required
                >
                <button type="button" class="btn-calendario" data-flatpickr-target="fecha_prestamo" aria-label="Abrir calendario">📅</button>
            </div>
        </div>

        <div>
            <label for="fecha_devolucion_estimada">Fecha estimada de devolución *</label>
            <div class="campo-fecha">
                <input
                    type="text"
                    id="fecha_devolucion_estimada"
                    name="fecha_devolucion_estimada"
                    value="<?= htmlspecialchars($fechaDevolucionValor, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="DD/MM/AAAA"
                    required
                >
                <button type="button" class="btn-calendario" data-flatpickr-target="fecha_devolucion_estimada" aria-label="Abrir calendario">📅</button>
            </div>
        </div>

        <h2>Justificación del préstamo</h2>

        <div>
            <label for="motivo">Justificación del préstamo *</label>
            <textarea id="motivo" name="motivo" required><?= htmlspecialchars($justificacionValor, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <h2>Observaciones</h2>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Registrar préstamo</button>
            <a href="index.php?modulo=prestamos">Cancelar</a>
        </div>
    </form>

    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        var bienesPorResponsable = <?= json_encode($bienesPorResponsable, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var bienesSeleccionadosPrevios = <?= json_encode($bienesSeleccionados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var condicionesEntregaPrevias = <?= json_encode($condicionesEntregaValor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var condicionesValidas = <?= json_encode(Bien::CONDICIONES_VALIDAS, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

        (function () {
            var selectTipo = document.getElementById('tipo_prestamo');
            var selectOrigen = document.getElementById('id_responsable_origen');
            var bloqueOrigenSelect = document.getElementById('bloque-origen-select');
            var bloqueOrigenInventarios = document.getElementById('bloque-origen-inventarios');
            var inputOrigenInventarios = document.getElementById('id_responsable_origen_inventarios');
            var contenedorBienes = document.getElementById('contenedor-bienes');
            var selectResponsableDestino = document.getElementById('id_responsable_destino');
            var campoAreaDestino = document.getElementById('area_destino_derivada');

            function idResponsableOrigenActivo() {
                return selectTipo.value === 'desde_inventarios'
                    ? inputOrigenInventarios.value
                    : selectOrigen.value;
            }

            function escapeHtml(texto) {
                var div = document.createElement('div');
                div.textContent = texto === null || texto === undefined ? '' : String(texto);
                return div.innerHTML;
            }

            function construirSelectCondicion(nombreCampo, valorSeleccionado) {
                var html = '<select class="campo-condicion" name="' + nombreCampo + '">'
                    + '<option value="">Seleccione...</option>';

                condicionesValidas.forEach(function (condicion) {
                    var seleccionada = condicion === valorSeleccionado ? ' selected' : '';
                    html += '<option value="' + escapeHtml(condicion) + '"' + seleccionada + '>' + escapeHtml(condicion) + '</option>';
                });

                html += '</select>';
                return html;
            }

            function renderizarBienes() {
                var idResponsable = idResponsableOrigenActivo();
                var bienes = idResponsable ? (bienesPorResponsable[idResponsable] || []) : [];

                if (!idResponsable) {
                    contenedorBienes.innerHTML = '<p>Seleccione primero un responsable origen para ver sus bienes actualmente asignados.</p>';
                    return;
                }

                if (bienes.length === 0) {
                    contenedorBienes.innerHTML = '<p>Este responsable no tiene bienes disponibles para prestar.</p>';
                    return;
                }

                var html = '<table border="1" cellpadding="5" cellspacing="0"><thead><tr>'
                    + '<th></th><th>No. Interno</th><th>No. SICOIN</th><th>Descripción</th><th>Marca/Modelo</th>'
                    + '<th>Serie</th><th>Condición actual</th><th>Condición al entregar</th><th>Valor</th><th>Ubicación actual</th>'
                    + '</tr></thead><tbody>';

                bienes.forEach(function (bien) {
                    var marcado = bienesSeleccionadosPrevios.indexOf(bien.id_bien) !== -1 ? ' checked' : '';
                    var marcaModelo = [bien.marca, bien.modelo].filter(Boolean).join(' / ') || '-';
                    var condicionPrevia = condicionesEntregaPrevias && condicionesEntregaPrevias[bien.id_bien] !== undefined
                        ? condicionesEntregaPrevias[bien.id_bien]
                        : (bien.condicion_bien || '');

                    html += '<tr>'
                        + '<td><input type="checkbox" name="bienes[]" value="' + bien.id_bien + '"' + marcado + '></td>'
                        + '<td>' + escapeHtml(bien.codigo_interno || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.codigo_sicoin || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.descripcion || '-') + '</td>'
                        + '<td>' + escapeHtml(marcaModelo) + '</td>'
                        + '<td>' + escapeHtml(bien.serie || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.condicion_bien || '-') + '</td>'
                        + '<td>' + construirSelectCondicion('condicion_entrega[' + bien.id_bien + ']', condicionPrevia) + '</td>'
                        + '<td>' + escapeHtml(bien.valor !== null ? bien.valor : '-') + '</td>'
                        + '<td>' + escapeHtml(bien.ubicacion_actual || '-') + '</td>'
                        + '</tr>';
                });

                html += '</tbody></table>';
                contenedorBienes.innerHTML = html;
            }

            function actualizarModoOrigen() {
                // "Desde Inventarios" fija el origen a la encargada institucional y no permite
                // cambiarlo: se oculta el <select> normal y se muestra el bloque de solo lectura. Solo
                // un bloque queda habilitado a la vez para que "id_responsable_origen" se envíe una
                // única vez en el POST (el backend igual vuelve a resolverlo por su cuenta, nunca
                // confía en este valor para "Desde Inventarios").
                var esDesdeInventarios = selectTipo.value === 'desde_inventarios';

                bloqueOrigenSelect.style.display = esDesdeInventarios ? 'none' : '';
                bloqueOrigenInventarios.style.display = esDesdeInventarios ? '' : 'none';
                selectOrigen.disabled = esDesdeInventarios;
                selectOrigen.required = !esDesdeInventarios;
                inputOrigenInventarios.disabled = !esDesdeInventarios;
            }

            function actualizarOpcionesDestino() {
                var idResponsableOrigen = idResponsableOrigenActivo();

                Array.prototype.forEach.call(selectResponsableDestino.options, function (opcion) {
                    if (!opcion.value) {
                        return;
                    }

                    var esOrigen = idResponsableOrigen !== '' && opcion.value === idResponsableOrigen;
                    opcion.disabled = esOrigen;

                    if (esOrigen && opcion.selected) {
                        selectResponsableDestino.value = '';
                    }
                });
            }

            function actualizarAreaDestino() {
                var opcion = selectResponsableDestino.options[selectResponsableDestino.selectedIndex];

                if (!opcion || !opcion.value) {
                    campoAreaDestino.value = '';
                    return;
                }

                var nombreUbicacion = opcion.getAttribute('data-ubicacion') || '';
                var tipoUbicacion = opcion.getAttribute('data-tipo-ubicacion') || '';

                if (nombreUbicacion === '') {
                    campoAreaDestino.value = 'Ubicación no disponible';
                    return;
                }

                campoAreaDestino.value = tipoUbicacion !== ''
                    ? nombreUbicacion + ' — ' + tipoUbicacion
                    : nombreUbicacion;
            }

            selectTipo.addEventListener('change', function () {
                actualizarModoOrigen();
                renderizarBienes();
                actualizarOpcionesDestino();
            });

            selectOrigen.addEventListener('change', function () {
                renderizarBienes();
                actualizarOpcionesDestino();
            });

            selectResponsableDestino.addEventListener('change', actualizarAreaDestino);

            actualizarModoOrigen();
            renderizarBienes();
            actualizarOpcionesDestino();
            actualizarAreaDestino();

            inicializarSelectoresFecha(['fecha_prestamo', 'fecha_devolucion_estimada']);
        })();
    </script>
</body>
</html>
