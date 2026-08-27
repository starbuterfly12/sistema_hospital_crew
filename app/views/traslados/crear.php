<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TrasladosController::crear()).
// CRÍTICO: los dos <script> del final se conservan íntegros salvo cambios puramente presentacionales
// (clases CSS en el <table> y los <p> que genera el JS). No se renombró ningún id/name/data-attribute:
// #id_responsable_origen, #id_responsable_destino, #ubicacion_destino_derivada, #contenedor-bienes,
// #fecha_movimiento, #motivo, #observaciones, name="bienes[]", data-ubicacion, data-tipo-ubicacion,
// data-flatpickr-target — todos intactos. La tabla de bienes elegibles la construye el JS a partir
// de $bienesPorResponsable (misma consulta de elegibilidad del controlador, sin tocar).
$responsablesOrigen = $responsablesOrigen ?? [];
$responsablesDestino = $responsablesDestino ?? [];
$bienesPorResponsable = $bienesPorResponsable ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$bienesSeleccionados = array_map('intval', $datosFormulario['bienes'] ?? []);
$idResponsableOrigenSeleccionado = (int) ($datosFormulario['id_responsable_origen'] ?? 0);
$idResponsableDestinoSeleccionado = (int) ($datosFormulario['id_responsable_destino'] ?? 0);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar traslado</h1>
            <p class="page-subtitle">Registre el traslado de bienes institucionales.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=traslados" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Responsable origen</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="id_responsable_origen">Responsable origen <span class="required-mark">*</span></label>
                <select id="id_responsable_origen" name="id_responsable_origen" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($responsablesOrigen as $responsable): ?>
                        <?php $etiqueta = $responsable['nombre_completo'] . ' — ' . ($responsable['nombre_ubicacion'] ?? '-'); ?>
                        <option
                            value="<?= (int) $responsable['id_responsable'] ?>"
                            <?= ($idResponsableOrigenSeleccionado === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                        ><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Bienes a trasladar</h2>
        <div id="contenedor-bienes">
            <p class="form-hint">Seleccione primero un responsable origen para ver sus bienes actuales.</p>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Destino</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="id_responsable_destino">Responsable destino <span class="required-mark">*</span></label>
                <select id="id_responsable_destino" name="id_responsable_destino" class="form-control" required>
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

            <div class="form-group">
                <label class="form-label" for="ubicacion_destino_derivada">Ubicación destino</label>
                <input type="text" id="ubicacion_destino_derivada" class="form-control" readonly>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Datos del traslado</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="fecha_movimiento">Fecha del traslado <span class="required-mark">*</span></label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_movimiento"
                        name="fecha_movimiento"
                        class="form-control"
                        value="<?= htmlspecialchars($datosFormulario['fecha_movimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                        required
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_movimiento" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="motivo">Motivo <span class="required-mark">*</span></label>
                <textarea id="motivo" name="motivo" class="form-control" rows="3" required><?= htmlspecialchars($datosFormulario['motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($datosFormulario['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Registrar traslado</button>
        <a href="index.php?modulo=traslados" class="btn btn-secondary">Cancelar</a>
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

        // Solo presentación: NO altera bien.valor (el objeto conserva el número original).
        function formatearQ(valor) {
            if (valor === null || valor === undefined || valor === '') {
                return '—';
            }
            var numero = Number(valor);
            if (isNaN(numero)) {
                return '—';
            }
            return 'Q ' + numero.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderizarBienes() {
            var idResponsable = selectOrigen.value;
            var bienes = idResponsable ? (bienesPorResponsable[idResponsable] || []) : [];

            if (!idResponsable) {
                contenedorBienes.innerHTML = '<p class="form-hint">Seleccione primero un responsable origen para ver sus bienes actuales.</p>';
                return;
            }

            if (bienes.length === 0) {
                contenedorBienes.innerHTML = '<p class="estado-vacio">Este responsable no tiene bienes disponibles para trasladar.</p>';
                return;
            }

            var html = '<div class="table-responsive"><table class="table-app table-detail-centered"><thead><tr>'
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
                    + '<td>' + escapeHtml(formatearQ(bien.valor)) + '</td>'
                    + '<td>' + escapeHtml(bien.ubicacion_actual || '-') + '</td>'
                    + '<td>' + escapeHtml(bien.numero_asignacion || '-') + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table></div>';
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

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        inicializarSelectoresFecha(['fecha_movimiento']);
    });
</script>
