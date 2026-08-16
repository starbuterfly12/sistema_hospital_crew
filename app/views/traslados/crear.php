<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar traslado</title>
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
    </style>
</head>
<body>
    <?php
        $responsablesOrigen = $responsablesOrigen ?? [];
        $responsablesDestino = $responsablesDestino ?? [];
        $bienesPorResponsable = $bienesPorResponsable ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];

        $bienesSeleccionados = array_map('intval', $datosFormulario['bienes'] ?? []);
        $idResponsableOrigenSeleccionado = (int) ($datosFormulario['id_responsable_origen'] ?? 0);
        $idResponsableDestinoSeleccionado = (int) ($datosFormulario['id_responsable_destino'] ?? 0);
    ?>

    <?php if (!empty($error)): ?>
        <p><strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>

    <h1>Registrar traslado</h1>

    <form method="POST">
        <?= csrfField() ?>

        <h2>Responsable origen</h2>

        <div>
            <label for="id_responsable_origen">Responsable origen *</label>
            <select id="id_responsable_origen" name="id_responsable_origen" required>
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

        <h2>Bienes disponibles</h2>

        <div id="contenedor-bienes">
            <p>Seleccione primero un responsable origen para ver sus bienes actuales.</p>
        </div>

        <h2>Destino</h2>

        <div>
            <label for="id_responsable_destino">Responsable destino *</label>
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
            <label for="ubicacion_destino_derivada">Ubicación destino</label>
            <input type="text" id="ubicacion_destino_derivada" readonly>
        </div>

        <h2>Datos del traslado</h2>

        <div>
            <label for="fecha_movimiento">Fecha del traslado *</label>
            <div class="campo-fecha">
                <input
                    type="text"
                    id="fecha_movimiento"
                    name="fecha_movimiento"
                    value="<?= htmlspecialchars($datosFormulario['fecha_movimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="DD/MM/AAAA"
                    required
                >
                <button type="button" class="btn-calendario" data-flatpickr-target="fecha_movimiento" aria-label="Abrir calendario">📅</button>
            </div>
        </div>

        <div>
            <label for="motivo">Motivo *</label>
            <textarea id="motivo" name="motivo" required><?= htmlspecialchars($datosFormulario['motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($datosFormulario['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Registrar traslado</button>
            <a href="index.php?modulo=traslados">Cancelar</a>
        </div>
    </form>

    <script>
        var bienesPorResponsable = <?= json_encode($bienesPorResponsable, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        var bienesSeleccionadosPrevios = <?= json_encode($bienesSeleccionados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        (function () {
            var selectOrigen = document.getElementById('id_responsable_origen');
            var contenedorBienes = document.getElementById('contenedor-bienes');
            var selectResponsableDestino = document.getElementById('id_responsable_destino');
            var campoUbicacionDestino = document.getElementById('ubicacion_destino_derivada');

            function escapeHtml(texto) {
                var div = document.createElement('div');
                div.textContent = texto === null || texto === undefined ? '' : String(texto);
                return div.innerHTML;
            }

            function renderizarBienes() {
                var idResponsable = selectOrigen.value;
                var bienes = idResponsable ? (bienesPorResponsable[idResponsable] || []) : [];

                if (!idResponsable) {
                    contenedorBienes.innerHTML = '<p>Seleccione primero un responsable origen para ver sus bienes actuales.</p>';
                    return;
                }

                if (bienes.length === 0) {
                    contenedorBienes.innerHTML = '<p>Este responsable no tiene bienes disponibles para trasladar.</p>';
                    return;
                }

                var html = '<table border="1" cellpadding="5" cellspacing="0"><thead><tr>'
                    + '<th></th><th>Código</th><th>Descripción</th><th>Marca/Modelo</th>'
                    + '<th>Serie</th><th>Condición</th><th>Valor</th><th>Ubicación actual</th><th>Asignación</th>'
                    + '</tr></thead><tbody>';

                bienes.forEach(function (bien) {
                    var marcado = bienesSeleccionadosPrevios.indexOf(bien.id_bien) !== -1 ? ' checked' : '';
                    var marcaModelo = [bien.marca, bien.modelo].filter(Boolean).join(' / ') || '-';

                    html += '<tr>'
                        + '<td><input type="checkbox" name="bienes[]" value="' + bien.id_bien + '"' + marcado + '></td>'
                        + '<td>' + escapeHtml(bien.codigo_mostrado || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.descripcion || '-') + '</td>'
                        + '<td>' + escapeHtml(marcaModelo) + '</td>'
                        + '<td>' + escapeHtml(bien.serie || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.condicion_bien || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.valor !== null ? bien.valor : '-') + '</td>'
                        + '<td>' + escapeHtml(bien.ubicacion_actual || '-') + '</td>'
                        + '<td>' + escapeHtml(bien.numero_asignacion || '-') + '</td>'
                        + '</tr>';
                });

                html += '</tbody></table>';
                contenedorBienes.innerHTML = html;
            }

            function actualizarOpcionesDestino() {
                var idResponsableOrigen = selectOrigen.value;

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

            function actualizarUbicacionDestino() {
                var opcion = selectResponsableDestino.options[selectResponsableDestino.selectedIndex];

                if (!opcion || !opcion.value) {
                    campoUbicacionDestino.value = '';
                    return;
                }

                var nombreUbicacion = opcion.getAttribute('data-ubicacion') || '';
                var tipoUbicacion = opcion.getAttribute('data-tipo-ubicacion') || '';

                if (nombreUbicacion === '') {
                    campoUbicacionDestino.value = 'Ubicación no disponible';
                    return;
                }

                campoUbicacionDestino.value = tipoUbicacion !== ''
                    ? nombreUbicacion + ' — ' + tipoUbicacion
                    : nombreUbicacion;
            }

            selectOrigen.addEventListener('change', function () {
                renderizarBienes();
                actualizarOpcionesDestino();
            });

            selectResponsableDestino.addEventListener('change', actualizarUbicacionDestino);

            renderizarBienes();
            actualizarOpcionesDestino();
            actualizarUbicacionDestino();
        })();
    </script>

    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            inicializarSelectoresFecha(['fecha_movimiento']);
        });
    </script>
</body>
</html>
