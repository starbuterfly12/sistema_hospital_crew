<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva verificación física</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $busqueda = $busqueda ?? '';
        $resultadosBusqueda = $resultadosBusqueda ?? [];
        $bienSeleccionado = $bienSeleccionado ?? null;
        $errorBien = $errorBien ?? null;
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];

        $condicionRegistrada = $bienSeleccionado['condicion_bien'] ?? null;
    ?>

    <h1>Nueva verificación física</h1>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <?php if ($errorBien !== null): ?>
        <p><?= $mostrar($errorBien) ?></p>
    <?php endif; ?>

    <h2>A. Buscar bien</h2>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="verificaciones">
        <input type="hidden" name="accion" value="crear">

        <label for="busqueda_bien">Código interno / SICOIN / descripción</label>
        <input type="text" id="busqueda_bien" name="busqueda" value="<?= $mostrar($busqueda) ?>">
        <button type="submit">Buscar</button>
    </form>

    <?php if ($busqueda !== ''): ?>
        <?php if (empty($resultadosBusqueda)): ?>
            <p>No se encontraron bienes con ese criterio.</p>
        <?php else: ?>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Código interno</th>
                        <th>SICOIN</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Responsable actual</th>
                        <th>Ubicación actual</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultadosBusqueda as $resultado): ?>
                        <tr>
                            <td><?= $mostrar($resultado['codigo_interno']) ?></td>
                            <td><?= $mostrar($resultado['codigo_sicoin']) ?></td>
                            <td><?= $mostrar($resultado['descripcion']) ?></td>
                            <td><?= $mostrar($resultado['nombre_estado']) ?></td>
                            <td><?= $mostrar($resultado['responsable_actual']) ?></td>
                            <td><?= $mostrar($resultado['ubicacion_actual']) ?></td>
                            <td>
                                <a href="index.php?modulo=verificaciones&accion=crear&id_bien=<?= (int) $resultado['id_bien'] ?>">Seleccionar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($bienSeleccionado !== null): ?>
        <h2>B. Datos del bien</h2>
        <p>Esto es lo que el sistema tiene registrado antes de realizar la verificación.</p>

        <dl>
            <dt>Código interno</dt>
            <dd><?= $mostrar($bienSeleccionado['codigo_interno']) ?></dd>

            <dt>Código SICOIN</dt>
            <dd><?= $mostrar($bienSeleccionado['codigo_sicoin']) ?></dd>

            <dt>Descripción</dt>
            <dd><?= $mostrar($bienSeleccionado['descripcion']) ?></dd>

            <dt>Marca</dt>
            <dd><?= $mostrar($bienSeleccionado['marca']) ?></dd>

            <dt>Modelo</dt>
            <dd><?= $mostrar($bienSeleccionado['modelo']) ?></dd>

            <dt>Serie</dt>
            <dd><?= $mostrar($bienSeleccionado['serie']) ?></dd>

            <dt>Categoría</dt>
            <dd><?= $mostrar($bienSeleccionado['nombre_categoria']) ?></dd>

            <dt>Estado administrativo</dt>
            <dd><?= $mostrar($bienSeleccionado['nombre_estado']) ?></dd>

            <dt>Condición registrada</dt>
            <dd><?= $mostrar($bienSeleccionado['condicion_bien']) ?></dd>

            <dt>Responsable actual</dt>
            <dd><?= $mostrar($bienSeleccionado['responsable_actual']) ?></dd>

            <dt>Área / ubicación actual</dt>
            <dd><?= $mostrar($bienSeleccionado['ubicacion_actual']) ?></dd>
        </dl>

        <h2>C. Datos de la verificación</h2>

        <dl>
            <dt>Fecha y hora de verificación</dt>
            <dd>Automática</dd>

            <dt>Usuario que verifica</dt>
            <dd><?= $mostrar($_SESSION['nombre_completo'] ?? '') ?></dd>
        </dl>

        <form method="POST" action="index.php?modulo=verificaciones&accion=crear" id="form-verificacion" onsubmit="return confirm('¿Está seguro de guardar esta verificación física?');">
            <?= csrfField() ?>
            <input type="hidden" name="id_bien" value="<?= (int) $bienSeleccionado['id_bien'] ?>">

            <fieldset>
                <legend>¿El bien fue localizado físicamente?</legend>

                <label>
                    <input type="radio" name="bien_localizado" value="1" id="localizado_si" required
                        <?= (($datosFormulario['bien_localizado'] ?? '') === '1') ? 'checked' : '' ?>>
                    Sí
                </label>
                <label>
                    <input type="radio" name="bien_localizado" value="0" id="localizado_no" required
                        <?= (($datosFormulario['bien_localizado'] ?? '') === '0') ? 'checked' : '' ?>>
                    No
                </label>
            </fieldset>

            <fieldset id="bloque-localizado">
                <legend>Resultado físico</legend>

                <p>
                    <strong>¿El responsable coincide con el registrado?</strong><br>
                    <label>
                        <input type="radio" name="responsable_correcto" value="1"
                            <?= (($datosFormulario['responsable_correcto'] ?? '') === '1') ? 'checked' : '' ?>>
                        Sí
                    </label>
                    <label>
                        <input type="radio" name="responsable_correcto" value="0"
                            <?= (($datosFormulario['responsable_correcto'] ?? '') === '0') ? 'checked' : '' ?>>
                        No
                    </label>
                </p>

                <p>
                    <strong>¿La ubicación coincide con la registrada?</strong><br>
                    <label>
                        <input type="radio" name="ubicacion_correcta" value="1"
                            <?= (($datosFormulario['ubicacion_correcta'] ?? '') === '1') ? 'checked' : '' ?>>
                        Sí
                    </label>
                    <label>
                        <input type="radio" name="ubicacion_correcta" value="0"
                            <?= (($datosFormulario['ubicacion_correcta'] ?? '') === '0') ? 'checked' : '' ?>>
                        No
                    </label>
                </p>

                <p>
                    <label for="condicion_observada"><strong>Condición física observada</strong></label><br>
                    <select name="condicion_observada" id="condicion_observada">
                        <option value="">Seleccione...</option>
                        <?php foreach (Bien::CONDICIONES_VALIDAS as $condicionValida): ?>
                            <option value="<?= $mostrar($condicionValida) ?>"
                                <?= (($datosFormulario['condicion_observada'] ?? '') === $condicionValida) ? 'selected' : '' ?>>
                                <?= $mostrar($condicionValida) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            </fieldset>

            <p id="aviso-no-localizado" style="display:none;">
                No aplica: al no localizarse el bien, responsable, ubicación y condición no se registran.
            </p>

            <p>
                <label for="observaciones"><strong>Observaciones</strong></label><br>
                <?php
                    // NUNCA usar $mostrar() aquí: convierte '' en el texto "-" para lectura, y ese
                    // "-" quedaría dentro del <textarea> como si el usuario lo hubiera escrito. Si el
                    // usuario reenvía el formulario tras un error sin tocar el campo, ese "-" viaja de
                    // vuelta en el POST como contenido no vacío y burla la validación de "obligatorio"
                    // (causa real de un registro con observaciones = "-" detectado en prueba manual).
                    // El valor debe reaparecer EXACTAMENTE como se envió, vacío incluido.
                ?>
                <textarea name="observaciones" id="observaciones" rows="4" cols="60"><?= htmlspecialchars($datosFormulario['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </p>

            <p>
                <button type="submit">Guardar verificación</button>
            </p>
        </form>

        <script>
            var condicionRegistrada = <?= json_encode($condicionRegistrada, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

            (function () {
                var radiosLocalizado = document.querySelectorAll('input[name="bien_localizado"]');
                var bloqueLocalizado = document.getElementById('bloque-localizado');
                var avisoNoLocalizado = document.getElementById('aviso-no-localizado');
                var observaciones = document.getElementById('observaciones');
                var responsableCorrecto = document.querySelectorAll('input[name="responsable_correcto"]');
                var ubicacionCorrecta = document.querySelectorAll('input[name="ubicacion_correcta"]');
                var condicionObservada = document.getElementById('condicion_observada');

                function actualizarObservacionesRequeridas() {
                    var localizado = document.querySelector('input[name="bien_localizado"]:checked');
                    var esLocalizado = localizado !== null && localizado.value === '1';

                    if (!esLocalizado) {
                        observaciones.required = true;
                        return;
                    }

                    var respuestaResponsable = document.querySelector('input[name="responsable_correcto"]:checked');
                    var respuestaUbicacion = document.querySelector('input[name="ubicacion_correcta"]:checked');

                    var hayDiferencia = (respuestaResponsable !== null && respuestaResponsable.value === '0')
                        || (respuestaUbicacion !== null && respuestaUbicacion.value === '0')
                        || (condicionObservada.value !== '' && condicionObservada.value !== condicionRegistrada);

                    observaciones.required = hayDiferencia;
                }

                function actualizarEstado() {
                    var localizado = document.querySelector('input[name="bien_localizado"]:checked');
                    var esLocalizado = localizado !== null && localizado.value === '1';

                    bloqueLocalizado.style.display = esLocalizado ? '' : 'none';
                    avisoNoLocalizado.style.display = esLocalizado ? 'none' : '';

                    responsableCorrecto.forEach(function (input) {
                        input.disabled = !esLocalizado;
                        input.required = esLocalizado;
                        if (!esLocalizado) { input.checked = false; }
                    });
                    ubicacionCorrecta.forEach(function (input) {
                        input.disabled = !esLocalizado;
                        input.required = esLocalizado;
                        if (!esLocalizado) { input.checked = false; }
                    });
                    condicionObservada.disabled = !esLocalizado;
                    condicionObservada.required = esLocalizado;
                    if (!esLocalizado) { condicionObservada.value = ''; }

                    actualizarObservacionesRequeridas();
                }

                radiosLocalizado.forEach(function (radio) {
                    radio.addEventListener('change', actualizarEstado);
                });
                responsableCorrecto.forEach(function (input) {
                    input.addEventListener('change', actualizarObservacionesRequeridas);
                });
                ubicacionCorrecta.forEach(function (input) {
                    input.addEventListener('change', actualizarObservacionesRequeridas);
                });
                condicionObservada.addEventListener('change', actualizarObservacionesRequeridas);

                actualizarEstado();
            })();
        </script>
    <?php endif; ?>

    <p><a href="index.php?modulo=verificaciones">Cancelar / Volver al listado</a></p>
</body>
</html>
