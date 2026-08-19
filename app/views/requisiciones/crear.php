<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar requisición</title>
    <link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
    <style>
        .fila-requisicion {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.25rem;
        }

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
        $responsables = $responsables ?? [];
        $bienesDisponibles = $bienesDisponibles ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];

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

    <?php if (!empty($error)): ?>
        <p><strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>

    <h1>Registrar requisición</h1>

    <form method="POST" id="form-requisicion">
        <?= csrfField() ?>

        <h2>Oficio</h2>
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

        <h2>Requisiciones institucionales</h2>
        <p>Puede agregar una o varias requisiciones (número y fecha del papel elaborado por el servicio).</p>

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

        <button type="button" id="btn-agregar-requisicion">Agregar requisición</button>

        <h2>Responsable solicitante</h2>

        <div>
            <label for="id_responsable_solicitante">Responsable solicitante *</label>
            <select id="id_responsable_solicitante" name="id_responsable_solicitante" required>
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

        <div>
            <label for="area_solicitante_derivada">Área/Servicio</label>
            <input type="text" id="area_solicitante_derivada" readonly>
        </div>

        <h2>Bienes disponibles en Almacén</h2>

        <?php if (empty($bienesDisponibles)): ?>
            <p>No hay bienes disponibles actualmente en Bodega de Almacén.</p>
        <?php else: ?>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th></th>
                        <th>No. Interno</th>
                        <th>No. SICOIN</th>
                        <th>Descripción</th>
                        <th>Marca/Modelo</th>
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
                            <td><input type="checkbox" name="bienes[]" value="<?= $idBien ?>"<?= $marcado ? ' checked' : '' ?>></td>
                            <td><?= htmlspecialchars($bien['codigo_interno'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($bien['codigo_sicoin'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($bien['descripcion'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($marcaModelo !== '' ? $marcaModelo : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($bien['serie'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($bien['condicion_bien'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($valor !== null ? (string) $valor : '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Observaciones</h2>
        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($observacionesValor, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Guardar pendiente</button>
            <a href="index.php?modulo=requisiciones">Cancelar</a>
        </div>
    </form>

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
</body>
</html>
