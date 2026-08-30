<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php
// (ver VerificacionesController::porAsignacion() y ::guardarAsignacion()).
//
// Una sola vista, dos modos ($modo):
//   'seleccion' -> buscador + tabla de asignaciones elegibles, cada fila con "Seleccionar".
//   'jornada'   -> cabecera de la asignación + una tarjeta por bien vigente con los mismos
//                  controles de la verificación individual. Al finalizar hace POST a
//                  guardar_asignacion (atómico, revalidado en backend).
//
// La verificación por asignación NO modifica el bien: solo registra el snapshot observado.
$modo = $modo ?? 'seleccion';
$asignacion = $asignacion ?? null;
$bienes = $bienes ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];
$asignacionesElegibles = $asignacionesElegibles ?? [];
$q = $q ?? '';

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$ubicacionTexto = static function (?string $nombre, ?string $tipo): string {
    if ($nombre === null || $nombre === '') {
        return '—';
    }

    return htmlspecialchars($nombre . ($tipo !== null && $tipo !== '' ? ' - ' . $tipo : ''), ENT_QUOTES, 'UTF-8');
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Verificación física por asignación</h1>
            <p class="page-subtitle">
                <?= $modo === 'jornada'
                    ? 'Revise todos los bienes vigentes cargados a la asignación y registre el resultado de la jornada.'
                    : 'Seleccione una asignación activa para revisar en una sola jornada todos sus bienes vigentes.' ?>
            </p>
        </div>

        <div class="page-actions">
            <?php if ($modo === 'jornada'): ?>
                <a href="index.php?modulo=verificaciones&accion=por_asignacion" class="btn btn-secondary">Cambiar asignación</a>
            <?php else: ?>
                <a href="index.php?modulo=verificaciones" class="btn btn-secondary">Volver</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($modo === 'seleccion'): ?>

    <form method="GET" action="index.php" class="filters">
        <input type="hidden" name="modulo" value="verificaciones">
        <input type="hidden" name="accion" value="por_asignacion">

        <div class="form-group">
            <label class="form-label" for="q">Buscar asignación</label>
            <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, responsable o ubicación">
        </div>

        <div class="form-actions-inline">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="index.php?modulo=verificaciones&accion=por_asignacion" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>

    <div class="card">
        <?php if (empty($asignacionesElegibles)): ?>
            <p class="estado-vacio">No hay asignaciones activas con bienes vigentes para verificar<?= $q !== '' ? ' con ese criterio' : '' ?>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Responsable</th>
                            <th>Ubicación</th>
                            <th>Bienes vigentes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignacionesElegibles as $item): ?>
                            <tr>
                                <td><?= $mostrar($item['numero_asignacion'] ?? null) ?></td>
                                <td><?= $mostrar($item['responsable_nombre'] ?? null) ?></td>
                                <td><?= $ubicacionTexto($item['nombre_ubicacion'] ?? null, $item['tipo_ubicacion'] ?? null) ?></td>
                                <td><?= (int) ($item['cantidad_bienes'] ?? 0) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="table-action-btn table-action-verificar" href="index.php?modulo=verificaciones&accion=por_asignacion&id_asignacion=<?= (int) $item['id_asignacion'] ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            Verificar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: /* modo jornada */ ?>

    <?php
    $asignacion = $asignacion ?? [];
    $idsBienes = array_map(static fn (array $b): int => (int) $b['id_bien'], $bienes);
    $totalEsperado = count($bienes);
    $condiciones = class_exists('Bien') ? Bien::CONDICIONES_VALIDAS : ['Bueno', 'Regular', 'Malo'];
    ?>

    <div class="detail-identidad">
        <p class="detail-identidad-codigo"><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></p>
        <p class="detail-identidad-descripcion"><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></p>
    </div>

    <div class="detail-card">
        <div class="detail-section">
            <h2 class="form-section-title">Datos de la jornada</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Número de asignación</span>
                    <span class="detail-value"><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Responsable</span>
                    <span class="detail-value"><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Ubicación</span>
                    <span class="detail-value"><?= $ubicacionTexto($asignacion['nombre_ubicacion'] ?? null, $asignacion['tipo_ubicacion'] ?? null) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Bienes esperados</span>
                    <span class="detail-value"><?= (int) $totalEsperado ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha de verificación</span>
                    <span class="detail-value">Automática (al finalizar)</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Usuario que verifica</span>
                    <span class="detail-value"><?= $mostrar($_SESSION['nombre_completo'] ?? '') ?></span>
                </div>
            </div>
            <p class="form-hint">La verificación por asignación no modifica el bien: solo registra lo observado en el historial de cada bien.</p>
        </div>
    </div>

    <?php if ($totalEsperado === 0): ?>
        <div class="card">
            <p class="estado-vacio">La asignación ya no tiene bienes vigentes para verificar.</p>
        </div>
    <?php else: ?>

        <div class="card" id="jornada-progreso-card">
            <p class="form-hint" id="jornada-progreso">Bienes completados: 0 de <?= (int) $totalEsperado ?></p>
        </div>

        <form method="POST" action="index.php?modulo=verificaciones&accion=guardar_asignacion" id="form-jornada" class="form-card">
            <?= csrfField() ?>
            <input type="hidden" name="id_asignacion" value="<?= (int) ($asignacion['id_asignacion'] ?? 0) ?>">
            <?php foreach ($idsBienes as $idBien): ?>
                <input type="hidden" name="bienes_esperados[]" value="<?= (int) $idBien ?>">
            <?php endforeach; ?>

            <?php foreach ($bienes as $indice => $bien): ?>
                <?php
                $idBien = (int) $bien['id_bien'];
                $datos = $datosFormulario[$idBien] ?? [];
                $condicionRegistrada = (string) ($bien['condicion_bien'] ?? '');
                $valLocalizado = (string) ($datos['bien_localizado'] ?? '');
                $valResponsable = (string) ($datos['responsable_correcto'] ?? '');
                $valUbicacion = (string) ($datos['ubicacion_correcta'] ?? '');
                $valCondicion = (string) ($datos['condicion_observada'] ?? '');
                $valObservaciones = (string) ($datos['observaciones'] ?? '');
                ?>
                <fieldset class="form-section-fieldset jornada-bien" data-jornada-bien
                    data-condicion-registrada="<?= htmlspecialchars($condicionRegistrada, ENT_QUOTES, 'UTF-8') ?>">
                    <legend>
                        <?= (int) $indice + 1 ?>. <?= $mostrar($bien['codigo_interno'] ?? null) ?>
                        <span class="jornada-bien-estado" data-jornada-bien-estado></span>
                    </legend>

                    <div class="detail-grid">
                        <div class="detail-item detail-full">
                            <span class="detail-label">Descripción</span>
                            <span class="detail-value"><?= $mostrar($bien['descripcion'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Código SICOIN</span>
                            <span class="detail-value"><?= $mostrar($bien['codigo_sicoin'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Marca / modelo</span>
                            <span class="detail-value"><?= $mostrar(trim(($bien['marca'] ?? '') . ' ' . ($bien['modelo'] ?? '')) ?: null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Serie</span>
                            <span class="detail-value"><?= $mostrar($bien['serie'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Condición registrada</span>
                            <span class="detail-value"><?= $mostrar($condicionRegistrada ?: null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Responsable registrado</span>
                            <span class="detail-value"><?= $mostrar($bien['responsable_actual'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ubicación registrada</span>
                            <span class="detail-value"><?= $mostrar($bien['ubicacion_actual'] ?? null) ?></span>
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <span class="form-label">¿El bien fue localizado físicamente?</span>
                        <div class="radio-opciones">
                            <label>
                                <input type="radio" name="bien_localizado[<?= $idBien ?>]" value="1" data-jornada-localizado required <?= $valLocalizado === '1' ? 'checked' : '' ?>>
                                Sí
                            </label>
                            <label>
                                <input type="radio" name="bien_localizado[<?= $idBien ?>]" value="0" data-jornada-localizado required <?= $valLocalizado === '0' ? 'checked' : '' ?>>
                                No
                            </label>
                        </div>
                    </div>

                    <div class="jornada-bloque-localizado" data-jornada-bloque-localizado>
                        <div class="form-grid">
                            <div class="form-group form-grid-full">
                                <span class="form-label">¿El responsable coincide con el registrado?</span>
                                <div class="radio-opciones">
                                    <label>
                                        <input type="radio" name="responsable_correcto[<?= $idBien ?>]" value="1" data-jornada-difcheck <?= $valResponsable === '1' ? 'checked' : '' ?>>
                                        Sí
                                    </label>
                                    <label>
                                        <input type="radio" name="responsable_correcto[<?= $idBien ?>]" value="0" data-jornada-difcheck <?= $valResponsable === '0' ? 'checked' : '' ?>>
                                        No
                                    </label>
                                </div>
                            </div>

                            <div class="form-group form-grid-full">
                                <span class="form-label">¿La ubicación coincide con la registrada?</span>
                                <div class="radio-opciones">
                                    <label>
                                        <input type="radio" name="ubicacion_correcta[<?= $idBien ?>]" value="1" data-jornada-difcheck <?= $valUbicacion === '1' ? 'checked' : '' ?>>
                                        Sí
                                    </label>
                                    <label>
                                        <input type="radio" name="ubicacion_correcta[<?= $idBien ?>]" value="0" data-jornada-difcheck <?= $valUbicacion === '0' ? 'checked' : '' ?>>
                                        No
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Condición física observada</label>
                                <select name="condicion_observada[<?= $idBien ?>]" class="form-control" data-jornada-condicion>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($condiciones as $condicionValida): ?>
                                        <option value="<?= htmlspecialchars($condicionValida, ENT_QUOTES, 'UTF-8') ?>" <?= $valCondicion === $condicionValida ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($condicionValida, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones[<?= $idBien ?>]" class="form-control" rows="2" maxlength="2000" data-jornada-observaciones><?= htmlspecialchars($valObservaciones, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="button" class="btn btn-primary"
                    data-confirm
                    data-confirm-form="form-jornada"
                    data-confirm-validate-form
                    data-confirm-icon="doc" data-confirm-variant="menta"
                    data-confirm-title="Finalizar verificación por asignación"
                    data-confirm-text="Se registrará una verificación por cada bien de la asignación en su historial."
                    data-confirm-subtext="¿Desea finalizar la jornada?"
                    data-confirm-ok="Finalizar jornada"
                    data-confirm-btnclass="btn-primary">
                    Finalizar jornada
                </button>
                <a href="index.php?modulo=verificaciones&accion=por_asignacion" class="btn btn-secondary">Cancelar</a>
            </div>

            <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Finalizar jornada</button>
        </form>

        <script>
            (function () {
                var form = document.getElementById('form-jornada');
                if (!form) { return; }

                var tarjetas = Array.prototype.slice.call(form.querySelectorAll('[data-jornada-bien]'));
                var progreso = document.getElementById('jornada-progreso');
                var total = tarjetas.length;

                function estadoTarjeta(tarjeta) {
                    var localizado = tarjeta.querySelector('input[data-jornada-localizado]:checked');
                    if (!localizado) { return { completo: false, texto: 'Pendiente' }; }

                    if (localizado.value === '0') {
                        var obsNo = tarjeta.querySelector('[data-jornada-observaciones]');
                        return {
                            completo: obsNo.value.trim() !== '',
                            texto: 'No localizado'
                        };
                    }

                    var resp = tarjeta.querySelector('input[name^="responsable_correcto"]:checked');
                    var ubic = tarjeta.querySelector('input[name^="ubicacion_correcta"]:checked');
                    var cond = tarjeta.querySelector('[data-jornada-condicion]');
                    var obs = tarjeta.querySelector('[data-jornada-observaciones]');
                    var condRegistrada = tarjeta.getAttribute('data-condicion-registrada') || '';

                    if (!resp || !ubic || cond.value === '') {
                        return { completo: false, texto: 'Localizado — incompleto' };
                    }

                    var hayDiferencia = resp.value === '0' || ubic.value === '0' || (cond.value !== condRegistrada);
                    var completo = !hayDiferencia || obs.value.trim() !== '';

                    return {
                        completo: completo,
                        texto: hayDiferencia ? 'Localizado — con diferencias' : 'Localizado — sin diferencias'
                    };
                }

                function aplicar(tarjeta) {
                    var localizado = tarjeta.querySelector('input[data-jornada-localizado]:checked');
                    var bloque = tarjeta.querySelector('[data-jornada-bloque-localizado]');
                    var esLocalizado = localizado !== null && localizado.value === '1';

                    bloque.style.display = esLocalizado ? '' : 'none';

                    tarjeta.querySelectorAll('input[data-jornada-difcheck]').forEach(function (input) {
                        input.disabled = !esLocalizado;
                        input.required = esLocalizado;
                        if (!esLocalizado) { input.checked = false; }
                    });
                    var cond = tarjeta.querySelector('[data-jornada-condicion]');
                    cond.disabled = !esLocalizado;
                    cond.required = esLocalizado;
                    if (!esLocalizado) { cond.value = ''; }

                    var obs = tarjeta.querySelector('[data-jornada-observaciones]');
                    var st = estadoTarjeta(tarjeta);
                    obs.required = !st.completo && (localizado !== null);

                    var etiqueta = tarjeta.querySelector('[data-jornada-bien-estado]');
                    etiqueta.textContent = localizado !== null ? (st.texto + (st.completo ? ' ✓' : '')) : '';
                    etiqueta.className = 'jornada-bien-estado' + (st.completo ? ' jornada-bien-estado--ok' : (localizado !== null ? ' jornada-bien-estado--pend' : ''));

                    return st.completo;
                }

                function refrescar() {
                    var completos = 0;
                    tarjetas.forEach(function (t) { if (aplicar(t)) { completos++; } });
                    if (progreso) {
                        progreso.textContent = 'Bienes completados: ' + completos + ' de ' + total;
                    }
                }

                form.addEventListener('change', refrescar);
                form.addEventListener('input', function (e) {
                    if (e.target && e.target.matches('[data-jornada-observaciones]')) { refrescar(); }
                });

                refrescar();
            })();
        </script>

    <?php endif; ?>

<?php endif; ?>
