<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver VerificacionesController::crear()).
// Todos los id/name se mantienen exactamente iguales al HTML anterior — el JS de más abajo depende
// de ellos por getElementById()/querySelectorAll()/name y no fue tocado. Solo cambió el marcado
// visual y las clases CSS aplicadas a cada campo.
$mostrar = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Para el value="" del input de búsqueda (editable): nunca debe convertir vacío/null en "-"/"—",
// o el campo quedaría con ese literal como valor y, al reenviar sin tocarlo, se buscaría por él en
// vez de limpiar la búsqueda.
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$busqueda = $busqueda ?? '';
$resultadosBusqueda = $resultadosBusqueda ?? [];
$bienSeleccionado = $bienSeleccionado ?? null;
$errorBien = $errorBien ?? null;
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$condicionRegistrada = $bienSeleccionado['condicion_bien'] ?? null;

// Cancelar/Volver: si ya hay un bien en contexto, el destino natural es su ficha; si todavía no se
// ha seleccionado ninguno (entrada genérica "Nueva verificación" sin bien precargado), se conserva
// el destino original (listado de verificaciones).
$rutaVolver = $bienSeleccionado !== null
    ? 'index.php?modulo=bienes&accion=ver&id=' . (int) $bienSeleccionado['id_bien']
    : 'index.php?modulo=verificaciones';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar verificación física</h1>
            <p class="page-subtitle">Registre el resultado de la verificación física del bien.</p>
        </div>

        <div class="page-actions">
            <a href="<?= $rutaVolver ?>" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= $mostrar($error) ?></div>
<?php endif; ?>

<?php if ($errorBien !== null): ?>
    <div class="alert alert-error"><?= $mostrar($errorBien) ?></div>
<?php endif; ?>

<div class="card">
    <h2 class="card-titulo">A. Buscar bien</h2>

    <form method="GET" action="index.php" class="filters">
        <input type="hidden" name="modulo" value="verificaciones">
        <input type="hidden" name="accion" value="crear">

        <div class="form-group">
            <label class="form-label" for="busqueda_bien">Código interno / SICOIN / descripción</label>
            <input type="text" id="busqueda_bien" name="busqueda" class="form-control" value="<?= $valorInput($busqueda) ?>">
        </div>

        <div class="form-actions-inline">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <?php if ($busqueda !== ''): ?>
        <?php if (empty($resultadosBusqueda)): ?>
            <p class="estado-vacio">No se encontraron bienes con ese criterio.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app">
                    <thead>
                        <tr>
                            <th>Código interno</th>
                            <th>SICOIN</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Responsable actual</th>
                            <th>Ubicación actual</th>
                            <th>Acciones</th>
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
                                    <a class="btn btn-secondary" href="index.php?modulo=verificaciones&accion=crear&id_bien=<?= (int) $resultado['id_bien'] ?>">Seleccionar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($bienSeleccionado !== null): ?>
    <div class="detail-identidad">
        <p class="detail-identidad-codigo"><?= $mostrar($bienSeleccionado['codigo_interno']) ?></p>
        <p class="detail-identidad-descripcion"><?= $mostrar($bienSeleccionado['descripcion']) ?></p>
    </div>

    <div class="detail-card">
        <div class="detail-section">
            <h2 class="form-section-title">B. Datos del bien</h2>
            <p class="form-hint">Esto es lo que el sistema tiene registrado antes de realizar la verificación.</p>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Código SICOIN</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['codigo_sicoin']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Marca</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['marca']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Modelo</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['modelo']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Serie</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['serie']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Categoría</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['nombre_categoria']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Estado administrativo</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['nombre_estado']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Condición registrada</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['condicion_bien']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Responsable actual</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['responsable_actual']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Área / ubicación actual</span>
                    <span class="detail-value"><?= $mostrar($bienSeleccionado['ubicacion_actual']) ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h2 class="form-section-title">C. Datos de la verificación</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Fecha y hora de verificación</span>
                    <span class="detail-value">Automática</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Usuario que verifica</span>
                    <span class="detail-value"><?= $mostrar($_SESSION['nombre_completo'] ?? '') ?></span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="index.php?modulo=verificaciones&accion=crear" id="form-verificacion" class="form-card" onsubmit="return confirm('¿Está seguro de guardar esta verificación física?');">
        <?= csrfField() ?>
        <input type="hidden" name="id_bien" value="<?= (int) $bienSeleccionado['id_bien'] ?>">

        <fieldset class="form-section-fieldset">
            <legend>¿El bien fue localizado físicamente?</legend>

            <div class="radio-opciones">
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
            </div>
        </fieldset>

        <fieldset id="bloque-localizado" class="form-section-fieldset">
            <legend>Resultado físico</legend>

            <div class="form-grid">
                <div class="form-group form-grid-full">
                    <span class="form-label">¿El responsable coincide con el registrado?</span>
                    <div class="radio-opciones">
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
                    </div>
                </div>

                <div class="form-group form-grid-full">
                    <span class="form-label">¿La ubicación coincide con la registrada?</span>
                    <div class="radio-opciones">
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
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="condicion_observada">Condición física observada</label>
                    <select name="condicion_observada" id="condicion_observada" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php foreach (Bien::CONDICIONES_VALIDAS as $condicionValida): ?>
                            <option value="<?= $mostrar($condicionValida) ?>"
                                <?= (($datosFormulario['condicion_observada'] ?? '') === $condicionValida) ? 'selected' : '' ?>>
                                <?= $mostrar($condicionValida) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <p id="aviso-no-localizado" class="form-hint" style="display:none;">
            No aplica: al no localizarse el bien, responsable, ubicación y condición no se registran.
        </p>

        <div class="form-section">
            <div class="form-group">
                <label class="form-label" for="observaciones">Observaciones</label>
                <?php
                    // NUNCA usar $mostrar() aquí: convierte '' en "—" para lectura, y ese texto quedaría
                    // dentro del <textarea> como si el usuario lo hubiera escrito. Si el usuario reenvía
                    // el formulario tras un error sin tocar el campo, ese texto viaja de vuelta en el POST
                    // como contenido no vacío y burla la validación de "obligatorio" (causa real de un
                    // registro con observaciones = "-" detectado en prueba manual sobre la vista anterior).
                    // El valor debe reaparecer EXACTAMENTE como se envió, vacío incluido.
                ?>
                <textarea name="observaciones" id="observaciones" class="form-control" rows="4"><?= htmlspecialchars($datosFormulario['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <p class="form-hint">Esta verificación registra un historial: no modifica el responsable, la ubicación, el estado ni la asignación actual del bien.</p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar verificación</button>
            <a href="<?= $rutaVolver ?>" class="btn btn-secondary">Cancelar</a>
        </div>
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
