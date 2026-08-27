<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BitacoraController::ver()).
// Solo lectura: ninguna acción modificable. Mismos datos ($registro) que devuelve Bitacora::findById().
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};

$registro = $registro ?? [];
$idBitacora = (int) ($registro['id_bitacora'] ?? 0);
$exito = ($registro['resultado'] ?? '') === 'exitoso';

if (($registro['id_usuario'] ?? null) !== null) {
    if (($registro['nombre_completo'] ?? null) !== null && ($registro['usuario'] ?? null) !== null) {
        $usuarioMostrado = $mostrar($registro['nombre_completo']) . ' (' . $mostrar($registro['usuario']) . ')';
    } else {
        $usuarioMostrado = 'Usuario #' . (int) $registro['id_usuario'];
    }
} elseif (!empty($registro['usuario_intentado'])) {
    $usuarioMostrado = 'No autenticado (intento: ' . $mostrar($registro['usuario_intentado']) . ')';
} else {
    $usuarioMostrado = 'Sistema';
}
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de bitácora</h1>
            <p class="page-subtitle">Consulta del registro de bitácora.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bitacora" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo">Registro #<?= $idBitacora ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($registro['accion'] ?? null) ?> · <?= $mostrar($registro['modulo'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos del registro</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">ID de bitácora</span>
                <span class="detail-value"><?= $idBitacora ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha y hora</span>
                <span class="detail-value"><?= $mostrar(formatDateTimeSeconds($registro['fecha_hora'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Usuario</span>
                <span class="detail-value"><?= $usuarioMostrado ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Usuario intentado</span>
                <span class="detail-value"><?= $mostrar($registro['usuario_intentado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Acción</span>
                <span class="detail-value"><?= $mostrar($registro['accion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Módulo</span>
                <span class="detail-value"><?= $mostrar($registro['modulo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Resultado</span>
                <span class="detail-value"><span class="badge <?= $exito ? 'badge-exito' : 'badge-error' ?>"><?= $exito ? 'Exitoso' : 'Fallido' ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tabla afectada</span>
                <span class="detail-value"><?= $mostrar($registro['tabla_afectada'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">ID de registro afectado</span>
                <span class="detail-value"><?= $mostrar($registro['id_registro_afectado'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">IP de origen</span>
                <span class="detail-value"><?= $mostrar($registro['ip_origen'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Descripción completa</span>
                <span class="detail-value"><?= $mostrar($registro['descripcion'] ?? null) ?></span>
            </div>
        </div>
    </div>
</div>
