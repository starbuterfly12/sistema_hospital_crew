<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver RequisicionesController::crear()).
// Todos los id/name se mantienen exactamente iguales al HTML anterior (no migrado) — el JS del final
// depende de ellos por getElementById()/name/className y NO fue tocado. Los <input> de las filas
// repetibles tampoco llevan class="form-control" a propósito: las filas que crea el JS no la tendrían
// y se verían distintas; el <style> local de más abajo las viste por selector para que server y JS
// produzcan filas idénticas.
$responsables = $responsables ?? [];
$bienesDisponibles = $bienesDisponibles ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$numeroOficioValor = $datosFormulario['numero_oficio'] ?? '';
$numerosValor = $datosFormulario['numero_requisicion'] ?? [''];
$fechasValor = $datosFormulario['fecha_requisicion'] ?? [''];

if (empty($numerosValor)) {
    $numerosValor = [''];
    $fechasValor = [''];
}

$idResponsableSeleccionado = (int) ($datosFormulario['id_responsable_solicitante'] ?? 0);
$bienesSeleccionados = array_map('intval', $datosFormulario['bienes'] ?? []);
$observacionesValor = $datosFormulario['observaciones'] ?? '';
?>
<style>
    /* Fila repetible "número de requisición + fecha + Quitar". Estilada por selector (no por
       class="form-control") para que las filas creadas por el JS se vean igual que las del servidor. */
    .fila-requisicion {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
        margin-bottom: 10px;
    }
    .fila-requisicion input[type="text"] {
        height: 40px;
        padding: 0 12px;
        border: 1px solid var(--sgb-borde);
        border-radius: var(--sgb-radio);
        background: var(--sgb-superficie);
        color: var(--sgb-texto);
        font-family: inherit;
        font-size: 14px;
    }
    .fila-requisicion > input[type="text"] {
        flex: 1 1 240px;
        min-width: 160px;
    }
    .fila-requisicion .campo-fecha {
        flex: 1 1 220px;
        min-width: 170px;
    }
    .fila-requisicion .campo-fecha input[type="text"] {
        flex: 1;
    }
    .fila-requisicion .btn-quitar-requisicion {
        height: 40px;
        padding: 0 14px;
        border: 1px solid var(--sgb-borde);
        border-radius: var(--sgb-radio);
        background: var(--sgb-superficie);
        color: var(--sgb-texto);
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .fila-requisicion .btn-quitar-requisicion:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    #btn-agregar-requisicion {
        margin-top: 2px;
    }
</style>

<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Nueva requisición</h1>
            <p class="page-subtitle">Registre la solicitud de bienes institucionales.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=requisiciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" id="form-requisicion" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos de la requisición</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="numero_oficio">Oficio No. <span class="required-mark">*</span></label>
                <input
                    type="text"
                    id="numero_oficio"
                    name="numero_oficio"
                    class="form-control"
                    maxlength="50"
                    value="<?= htmlspecialchars($numeroOficioValor, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="id_responsable_solicitante">Responsable solicitante <span class="required-mark">*</span></label>
                <select id="id_responsable_solicitante" name="id_responsable_solicitante" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($responsables as $responsable): ?>
                        <option
                            value="<?= (int) $responsable['id_responsable'] ?>"
                            data-ubicacion="<?= htmlspecialchars($responsable['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-tipo-ubicacion="<?= htmlspecialchars($responsable['tipo_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($idResponsableSeleccionado === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                        ><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="area_solicitante_derivada">Área / Servicio</label>
                <input type="text" id="area_solicitante_derivada" class="form-control" readonly>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Requisiciones institucionales</h2>

        <div id="contenedor-requisiciones">
            <?php foreach ($numerosValor as $indice => $numero): ?>
                <div class="fila-requisicion">
                    <input type="text" name="numero_requisicion[]" maxlength="50" placeholder="Número de requisición" value="<?= htmlspecialchars((string) $numero, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="campo-fecha">
                        <input
                            type="text"
                            id="fecha_requisicion_row_<?= (int) $indice ?>"
                            name="fecha_requisicion[]"
                            value="<?= htmlspecialchars((string) ($fechasValor[$indice] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="DD/MM/AAAA"
                        >
                        <button type="button" class="btn-calendario" data-flatpickr-target="fecha_requisicion_row_<?= (int) $indice ?>" aria-label="Abrir calendario">📅</button>
                    </div>
                    <button type="button" class="btn-quitar-requisicion">Quitar</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="btn-agregar-requisicion" class="btn btn-secondary">Agregar requisición</button>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Bienes solicitados</h2>
        <p class="form-hint">Seleccione los bienes disponibles en Bodega de Almacén que se entregarán con esta requisición.</p>

        <?php if (empty($bienesDisponibles)): ?>
            <p class="estado-vacio">No hay bienes disponibles actualmente en Bodega de Almacén.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app">
                    <thead>
                        <tr>
                            <th></th>
                            <th>No. Interno</th>
                            <th>No. SICOIN</th>
                            <th>Descripción</th>
                            <th>Marca / Modelo</th>
                            <th>Serie</th>
                            <th>Condición</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bienesDisponibles as $bien): ?>
                            <?php
                                $idBien = (int) $bien['id_bien'];
                                $marcado = in_array($idBien, $bienesSeleccionados, true);
                                $marcaModelo = trim(($bien['marca'] ?? '') . ' ' . ($bien['modelo'] ?? ''));
                                $valor = $bien['costo'] ?? $bien['valor_estimado'] ?? null;
                            ?>
                            <tr>
                                <td class="celda-centrada"><input type="checkbox" name="bienes[]" value="<?= $idBien ?>"<?= $marcado ? ' checked' : '' ?>></td>
                                <td><?= $mostrar($bien['codigo_interno'] ?? null) ?></td>
                                <td><?= $mostrar($bien['codigo_sicoin'] ?? null) ?></td>
                                <td><?= $mostrar($bien['descripcion'] ?? null) ?></td>
                                <td><?= $mostrar($marcaModelo !== '' ? $marcaModelo : null) ?></td>
                                <td><?= $mostrar($bien['serie'] ?? null) ?></td>
                                <td><?= $mostrar($bien['condicion_bien'] ?? null) ?></td>
                                <td><?= formatearQuetzales($valor) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Guardar pendiente</button>
        <a href="index.php?modulo=requisiciones" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script>
    (function () {
        var contenedor = document.getElementById('contenedor-requisiciones');
        var botonAgregar = document.getElementById('btn-agregar-requisicion');
        var selectResponsable = document.getElementById('id_responsable_solicitante');
        var campoArea = document.getElementById('area_solicitante_derivada');
        var contadorFilas = <?= count($numerosValor) ?>;

        function crearFila() {
            var idCampoFecha = 'fecha_requisicion_row_' + contadorFilas;
            contadorFilas++;

            var fila = document.createElement('div');
            fila.className = 'fila-requisicion';

            var inputNumero = document.createElement('input');
            inputNumero.type = 'text';
            inputNumero.name = 'numero_requisicion[]';
            inputNumero.maxLength = 50;
            inputNumero.placeholder = 'Número de requisición';

            var envoltorioFecha = document.createElement('div');
            envoltorioFecha.className = 'campo-fecha';

            var inputFecha = document.createElement('input');
            inputFecha.type = 'text';
            inputFecha.id = idCampoFecha;
            inputFecha.name = 'fecha_requisicion[]';
            inputFecha.placeholder = 'DD/MM/AAAA';

            var botonCalendario = document.createElement('button');
            botonCalendario.type = 'button';
            botonCalendario.className = 'btn-calendario';
            botonCalendario.setAttribute('data-flatpickr-target', idCampoFecha);
            botonCalendario.setAttribute('aria-label', 'Abrir calendario');
            botonCalendario.textContent = '📅';

            envoltorioFecha.appendChild(inputFecha);
            envoltorioFecha.appendChild(botonCalendario);

            var botonQuitar = document.createElement('button');
            botonQuitar.type = 'button';
            botonQuitar.className = 'btn-quitar-requisicion';
            botonQuitar.textContent = 'Quitar';

            fila.appendChild(inputNumero);
            fila.appendChild(envoltorioFecha);
            fila.appendChild(botonQuitar);

            return { fila: fila, idCampoFecha: idCampoFecha };
        }

        function actualizarBotonesQuitar() {
            var filas = contenedor.querySelectorAll('.fila-requisicion');
            var botones = contenedor.querySelectorAll('.btn-quitar-requisicion');

            botones.forEach(function (boton) {
                boton.disabled = filas.length <= 1;
            });
        }

        contenedor.addEventListener('click', function (evento) {
            if (evento.target.classList.contains('btn-quitar-requisicion')) {
                var filas = contenedor.querySelectorAll('.fila-requisicion');

                if (filas.length > 1) {
                    evento.target.closest('.fila-requisicion').remove();
                    actualizarBotonesQuitar();
                }
            }
        });

        botonAgregar.addEventListener('click', function () {
            var nuevaFila = crearFila();
            contenedor.appendChild(nuevaFila.fila);
            actualizarBotonesQuitar();

            if (typeof inicializarSelectoresFecha === 'function') {
                inicializarSelectoresFecha([nuevaFila.idCampoFecha]);
            }
        });

        function actualizarAreaSolicitante() {
            var opcion = selectResponsable.options[selectResponsable.selectedIndex];

            if (!opcion || !opcion.value) {
                campoArea.value = '';
                return;
            }

            var nombreUbicacion = opcion.getAttribute('data-ubicacion') || '';
            var tipoUbicacion = opcion.getAttribute('data-tipo-ubicacion') || '';

            if (nombreUbicacion === '') {
                campoArea.value = 'Ubicación no disponible';
                return;
            }

            campoArea.value = tipoUbicacion !== ''
                ? nombreUbicacion + ' — ' + tipoUbicacion
                : nombreUbicacion;
        }

        selectResponsable.addEventListener('change', actualizarAreaSolicitante);

        actualizarBotonesQuitar();
        actualizarAreaSolicitante();

        var idsFechasIniciales = [];
        contenedor.querySelectorAll('.campo-fecha input[type="text"]').forEach(function (campo) {
            idsFechasIniciales.push(campo.id);
        });

        if (typeof inicializarSelectoresFecha === 'function' && idsFechasIniciales.length > 0) {
            inicializarSelectoresFecha(idsFechasIniciales);
        }
    })();
</script>
