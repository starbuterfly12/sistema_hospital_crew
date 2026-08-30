<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver VerificacionesController::ver()).
// Ficha de solo lectura de una verificación ya registrada — mismos datos que ya recibía la vista
// anterior ($verificacion), solo cambió el marcado visual.
$verificacion = $verificacion ?? [];

$mostrar = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$mostrarSiNo = static function (?int $valor): string {
    if ($valor === null) {
        return 'No aplica';
    }

    return $valor === 1 ? 'Sí' : 'No';
};

$localizado = (int) ($verificacion['bien_localizado'] ?? 0);
$tieneDiferencias = (int) ($verificacion['tiene_diferencias'] ?? 0);

$idVerifAsignacion = (int) ($verificacion['id_verificacion_asignacion'] ?? 0);
$numeroAsignacion = $verificacion['verificacion_asignacion_numero'] ?? null;

$responsableCorrecto = $verificacion['responsable_correcto'] ?? null;
$responsableCorrecto = $responsableCorrecto === null ? null : (int) $responsableCorrecto;

$ubicacionCorrecta = $verificacion['ubicacion_correcta'] ?? null;
$ubicacionCorrecta = $ubicacionCorrecta === null ? null : (int) $ubicacionCorrecta;

$condicionObservada = $verificacion['condicion_observada'] ?? null;

$claseBadgeResultado = $tieneDiferencias === 1 ? 'badge badge-pendiente' : 'badge badge-exito';
$claseBadgeLocalizado = $localizado === 1 ? 'badge badge-exito' : 'badge badge-error';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de verificación física</h1>
            <p class="page-subtitle">Consulta el resultado registrado de esta verificación física.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=verificaciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($verificacion['codigo_interno'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($verificacion['descripcion'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Fecha y hora</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($verificacion['fecha_hora'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Verificado por</span>
                <span class="detail-value"><?= $mostrar($verificacion['usuario_verifica_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo de verificación</span>
                <span class="detail-value">
                    <?php if ($idVerifAsignacion > 0): ?>
                        <span class="badge badge-info">Por asignación</span>
                    <?php else: ?>
                        <span class="badge">Individual</span>
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($idVerifAsignacion > 0): ?>
                <div class="detail-item">
                    <span class="detail-label">Jornada / asignación</span>
                    <span class="detail-value">
                        <a href="index.php?modulo=verificaciones&accion=ver_asignacion&id=<?= $idVerifAsignacion ?>">
                            <?= $numeroAsignacion !== null ? $mostrar($numeroAsignacion) : 'Ver jornada' ?>
                        </a>
                    </span>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Resultado</span>
                <span class="detail-value"><span class="<?= $claseBadgeResultado ?>"><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></span></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bien</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Código interno</span>
                <span class="detail-value"><?= $mostrar($verificacion['codigo_interno'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Código SICOIN</span>
                <span class="detail-value"><?= $mostrar($verificacion['codigo_sicoin'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Marca</span>
                <span class="detail-value"><?= $mostrar($verificacion['marca'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Modelo</span>
                <span class="detail-value"><?= $mostrar($verificacion['modelo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Serie</span>
                <span class="detail-value"><?= $mostrar($verificacion['serie'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Descripción</span>
                <span class="detail-value"><?= $mostrar($verificacion['descripcion'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <a href="index.php?modulo=bienes&accion=ver&id=<?= (int) ($verificacion['id_bien'] ?? 0) ?>" class="btn btn-secondary">Ver ficha actual del bien</a>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Datos registrados al momento de verificar</h2>
        <p class="form-hint">Información que el sistema tenía registrada al momento de realizar la verificación.</p>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Responsable registrado</span>
                <span class="detail-value"><?= $mostrar($verificacion['responsable_registrado_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación registrada</span>
                <span class="detail-value"><?= $mostrar($verificacion['ubicacion_registrada_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Condición registrada</span>
                <span class="detail-value"><?= $mostrar($verificacion['condicion_registrada'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Resultado físico</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Bien localizado</span>
                <span class="detail-value"><span class="<?= $claseBadgeLocalizado ?>"><?= $localizado === 1 ? 'Sí' : 'No' ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable correcto</span>
                <span class="detail-value"><?= $mostrarSiNo($responsableCorrecto) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación correcta</span>
                <span class="detail-value"><?= $mostrarSiNo($ubicacionCorrecta) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Condición observada</span>
                <span class="detail-value"><?= $condicionObservada !== null ? $mostrar($condicionObservada) : 'No aplica' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Resultado</span>
                <span class="detail-value"><span class="<?= $claseBadgeResultado ?>"><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></span></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($verificacion['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>
</div>
