<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php
// (ver VerificacionesController::verAsignacion()). Detalle de solo lectura de una jornada de
// verificación física por asignación: cabecera + porcentajes + tabla de todos los bienes revisados.
$cabecera = $cabecera ?? [];
$bienesRevisados = $bienesRevisados ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

// Porcentaje sin ceros de relleno (90, 87.5, 33.33). Entra como string DECIMAL(5,2) desde BD.
$pct = static function ($valor): string {
    $texto = number_format((float) $valor, 2, '.', '');
    if (str_contains($texto, '.')) {
        $texto = rtrim(rtrim($texto, '0'), '.');
    }
    return $texto . ' %';
};

$totalEsperado = (int) ($cabecera['total_esperado'] ?? 0);
$totalRevisado = (int) ($cabecera['total_revisado'] ?? 0);
$totalLocalizados = (int) ($cabecera['total_localizados'] ?? 0);
$totalNoLocalizados = (int) ($cabecera['total_no_localizados'] ?? 0);
$totalConDiferencias = (int) ($cabecera['total_con_diferencias'] ?? 0);
$totalSinDiferencias = (int) ($cabecera['total_sin_diferencias'] ?? 0);

$estadoActual = $cabecera['asignacion_estado_actual'] ?? null;
$mostrarAvisoCambio = $estadoActual !== null && $estadoActual !== 'Asignada';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de verificación por asignación</h1>
            <p class="page-subtitle">Resumen de la jornada y resultado de cada bien revisado.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=verificaciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($cabecera['numero_asignacion'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($cabecera['responsable_registrado_nombre'] ?? null) ?></p>
</div>

<?php if ($mostrarAvisoCambio): ?>
    <div class="alert alert-warning">
        La asignación revisada cambió de estado después de esta jornada (estado actual: <?= $mostrar($estadoActual) ?>). El resumen y los snapshots de abajo conservan lo que era cierto al momento de verificar.
    </div>
<?php endif; ?>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos de la jornada</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número de asignación</span>
                <span class="detail-value"><?= $mostrar($cabecera['numero_asignacion'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Responsable (al verificar)</span>
                <span class="detail-value"><?= $mostrar($cabecera['responsable_registrado_nombre'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Ubicación (al verificar)</span>
                <span class="detail-value"><?= $mostrar($cabecera['ubicacion_registrada_nombre'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Fecha y hora</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($cabecera['fecha_hora'] ?? null)) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Verificado por</span>
                <span class="detail-value"><?= $mostrar($cabecera['usuario_verifica_nombre'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Asignación (actual)</span>
                <span class="detail-value">
                    <a href="index.php?modulo=asignaciones&accion=ver&id=<?= (int) ($cabecera['id_asignacion'] ?? 0) ?>">Ver asignación</a>
                </span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Resumen</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Total esperado</span>
                <span class="detail-value"><?= $totalEsperado ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Total revisado</span>
                <span class="detail-value"><?= $totalRevisado ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bienes localizados</span>
                <span class="detail-value"><?= $totalLocalizados ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bienes no localizados</span>
                <span class="detail-value"><?= $totalNoLocalizados ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bienes con diferencias</span>
                <span class="detail-value"><?= $totalConDiferencias ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bienes sin diferencias</span>
                <span class="detail-value"><?= $totalSinDiferencias ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Porcentaje de localización</span>
                <span class="detail-value"><span class="badge badge-info"><?= $pct($cabecera['porcentaje_localizacion'] ?? 0) ?></span></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Porcentaje sin diferencias</span>
                <span class="detail-value"><span class="badge badge-info"><?= $pct($cabecera['porcentaje_sin_diferencias'] ?? 0) ?></span></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes revisados</h2>
        <?php if (empty($bienesRevisados)): ?>
            <p class="estado-vacio">Esta jornada no registró bienes.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>Código interno</th>
                            <th>Descripción</th>
                            <th>Localizado</th>
                            <th>Resp. correcto</th>
                            <th>Ubic. correcta</th>
                            <th>Condición reg. / obs.</th>
                            <th>Resultado</th>
                            <th>Observaciones</th>
                            <th>Verificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bienesRevisados as $bien): ?>
                            <?php
                            $localizado = (int) $bien['bien_localizado'];
                            $tieneDiferencias = (int) $bien['tiene_diferencias'];
                            $respCorrecto = $bien['responsable_correcto'];
                            $ubicCorrecta = $bien['ubicacion_correcta'];
                            $siNo = static function ($v): string {
                                if ($v === null) { return 'No aplica'; }
                                return (int) $v === 1 ? 'Sí' : 'No';
                            };
                            $condRegistrada = (string) ($bien['condicion_registrada'] ?? '');
                            $condObservada = $bien['condicion_observada'];
                            ?>
                            <tr>
                                <td><?= $mostrar($bien['codigo_interno'] ?? null) ?></td>
                                <td><?= $mostrar($bien['descripcion'] ?? null) ?></td>
                                <td><span class="badge <?= $localizado === 1 ? 'badge-exito' : 'badge-error' ?>"><?= $localizado === 1 ? 'Sí' : 'No' ?></span></td>
                                <td><?= $siNo($respCorrecto) ?></td>
                                <td><?= $siNo($ubicCorrecta) ?></td>
                                <td>
                                    <?= $mostrar($condRegistrada ?: null) ?>
                                    <?= $condObservada !== null ? ' / ' . $mostrar($condObservada) : '' ?>
                                </td>
                                <td><span class="badge <?= $tieneDiferencias === 1 ? 'badge-pendiente' : 'badge-exito' ?>"><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></span></td>
                                <td><?= $mostrar($bien['observaciones'] ?? null) ?></td>
                                <td>
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=verificaciones&accion=ver&id=<?= (int) $bien['id_verificacion'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
