<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar devolución</title>
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
            width: 12em;
        }

        .campo-observacion-bien {
            width: 16em;
        }
    </style>
</head>
<body>
    <?php
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

    <?php if (!empty($error)): ?>
        <p><strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>

    <h1>Registrar devolución</h1>

    <?php if (empty($prestamosPendientes)): ?>
        <p>No hay préstamos con bienes pendientes de devolución.</p>
        <p><a href="index.php?modulo=devoluciones">Volver al listado</a></p>
    <?php else: ?>
        <form method="POST" id="form-devolucion">
            <?= csrfField() ?>

            <h2>Préstamo</h2>

            <div>
                <label for="id_prestamo">Préstamo *</label>
                <select id="id_prestamo" name="id_prestamo" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($prestamosPendientes as $prestamo): ?>
                        <?php
                            $etiqueta = $prestamo['numero_prestamo']
                                . ' — ' . ($etiquetasTipo[$prestamo['tipo_prestamo']] ?? $prestamo['tipo_prestamo'])
                                . ' — ' . $prestamo['responsable_destino_mostrado'];
                        ?>
                        <option
                            value="<?= (int) $prestamo['id_prestamo'] ?>"
                            <?= ($idPrestamoSeleccionado === (int) $prestamo['id_prestamo']) ? ' selected' : '' ?>
                        ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="contenedor-info-prestamo"></div>

            <h2>Bienes pendientes de devolución</h2>

            <div id="contenedor-bienes">
                <p>Seleccione primero un préstamo para ver sus bienes pendientes.</p>
            </div>

            <h2>Fecha de devolución</h2>

            <div>
                <label for="fecha_devolucion">Fecha de devolución *</label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_devolucion"
                        name="fecha_devolucion"
                        value="<?= htmlspecialchars($fechaDevolucionValor, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                        required
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_devolucion" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <h2>Motivo</h2>

            <div>
                <label for="motivo">Motivo (opcional)</label>
                <input
                    type="text"
                    id="motivo"
                    name="motivo"
                    maxlength="150"
                    value="<?= htmlspecialchars($motivoValor, ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <h2>Observaciones</h2>

            <div>
                <label for="observaciones">Observaciones (opcional)</label>
                <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div>
                <button type="submit">Registrar devolución</button>
                <a href="index.php?modulo=devoluciones">Cancelar</a>
            </div>
        </form>

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
                    var html = '<select class="campo-condicion" name="' + nombreCampo + '">'
                        + '<option value="">Seleccione...</option>';

                    condicionesValidas.forEach(function (condicion) {
                        var seleccionada = condicion === valorSeleccionado ? ' selected' : '';
                        html += '<option value="' + escapeHtml(condicion) + '"' + seleccionada + '>' + escapeHtml(condicion) + '</option>';
                    });

                    html += '</select>';
                    return html;
                }

                function renderizarInfoPrestamo() {
                    var idPrestamo = selectPrestamo.value;
                    var info = idPrestamo ? prestamosInfo[idPrestamo] : null;

                    if (!info) {
                        contenedorInfo.innerHTML = '';
                        return;
                    }

                    var html = '<dl>'
                        + '<dt>Tipo</dt><dd>' + escapeHtml(info.tipo) + '</dd>'
                        + '<dt>Oficio No.</dt><dd>' + escapeHtml(info.numero_oficio) + '</dd>'
                        + '<dt>Responsable origen</dt><dd>' + escapeHtml(info.responsable_origen) + '</dd>'
                        + '<dt>Área origen</dt><dd>' + escapeHtml(info.ubicacion_origen) + '</dd>'
                        + '<dt>Responsable temporal</dt><dd>' + escapeHtml(info.responsable_destino) + '</dd>'
                        + '<dt>Área temporal</dt><dd>' + escapeHtml(info.ubicacion_destino) + '</dd>'
                        + '<dt>Fecha del préstamo</dt><dd>' + escapeHtml(info.fecha_prestamo) + '</dd>'
                        + '<dt>Fecha estimada de devolución</dt><dd>' + escapeHtml(info.fecha_devolucion_estimada) + '</dd>';

                    if (info.motivo) {
                        html += '<dt>Justificación del préstamo</dt><dd>' + escapeHtml(info.motivo) + '</dd>';
                    }

                    html += '</dl>';
                    contenedorInfo.innerHTML = html;
                }

                function renderizarBienes() {
                    var idPrestamo = selectPrestamo.value;
                    var detalles = idPrestamo ? (detallesPorPrestamo[idPrestamo] || []) : [];

                    if (!idPrestamo) {
                        contenedorBienes.innerHTML = '<p>Seleccione primero un préstamo para ver sus bienes pendientes.</p>';
                        return;
                    }

                    if (detalles.length === 0) {
                        contenedorBienes.innerHTML = '<p>Este préstamo no tiene bienes pendientes de devolución.</p>';
                        return;
                    }

                    var html = '<p><label><input type="checkbox" id="seleccionar-todos"> Seleccionar todos</label></p>'
                        + '<table border="1" cellpadding="5" cellspacing="0"><thead><tr>'
                        + '<th></th><th>No. Interno</th><th>No. SICOIN</th><th>Descripción</th><th>Modelo</th>'
                        + '<th>Serie</th><th>Condición al prestar</th><th>Condición al devolver</th><th>Observación</th>'
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
                            + '<td>' + escapeHtml(detalle.modelo || '-') + '</td>'
                            + '<td>' + escapeHtml(detalle.serie || '-') + '</td>'
                            + '<td>' + escapeHtml(detalle.condicion_entrega || '-') + '</td>'
                            + '<td>' + construirSelectCondicion('condicion_devolucion[' + idDetalle + ']', condicionPrevia) + '</td>'
                            + '<td><input type="text" class="campo-observacion-bien" maxlength="255" name="observaciones_bien[' + idDetalle + ']" value="' + escapeHtml(observacionPrevia) + '"></td>'
                            + '</tr>';
                    });

                    html += '</tbody></table>';
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
</body>
</html>
