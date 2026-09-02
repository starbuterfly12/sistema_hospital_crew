<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TrasladosController::ver()).
// Ficha de solo lectura — mismos datos que ya recibía la vista anterior ($movimiento / $detalles) y
// mismo endpoint de constancia (descargar_constancia). Solo cambió el marcado visual.
$movimiento = $movimiento ?? [];
$detalles = $detalles ?? [];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

// Misma regla que TrasladosController::construirDescripcionCompleta(): "Descripción, marca: X,
// modelo: Y". La serie NO se incluye aquí porque la tabla ya tiene su propia columna Serie.
$construirDescripcion = static function (array $detalle): string {
    $partes = [trim((string) ($detalle['descripcion'] ?? ''))];

    $marca = trim((string) ($detalle['marca'] ?? ''));
    if ($marca !== '') {
        $partes[] = 'marca: ' . $marca;
    }

    $modelo = trim((string) ($detalle['modelo'] ?? ''));
    if ($modelo !== '') {
        $partes[] = 'modelo: ' . $modelo;
    }

    return implode(', ', $partes);
};

$idMovimiento = (int) ($movimiento['id_movimiento'] ?? 0);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle del traslado</h1>
            <p class="page-subtitle">Consulta de la información registrada del traslado.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=traslados" class="btn btn-secondary">Volver</a>
            <a href="index.php?modulo=traslados&accion=descargar_constancia&id=<?= $idMovimiento ?>" class="btn btn-azul-suave">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                Descargar constancia
            </a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($movimiento['numero_movimiento'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($movimiento['responsable_origen_nombre'] ?? null) ?> &rarr; <?= $mostrar($movimiento['responsable_destino_nombre'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número</span>
                <span class="detail-value"><?= $mostrar($movimiento['numero_movimiento'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($movimiento['fecha_movimiento'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $mostrar($movimiento['usuario_registra_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($movimiento['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Motivo</span>
                <span class="detail-value"><?= $mostrar($movimiento['motivo'] ?? null) ?></span>
            </div>

            <div class="detail-item detail-full">
                <span class="detail-label">Observaciones</span>
                <span class="detail-value"><?= $mostrar($movimiento['observaciones'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Origen y destino</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Responsable origen</span>
                <span class="detail-value"><?= $mostrar($movimiento['responsable_origen_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación origen</span>
                <span class="detail-value"><?= $mostrar($movimiento['ubicacion_origen_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable destino</span>
                <span class="detail-value"><?= $mostrar($movimiento['responsable_destino_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación destino</span>
                <span class="detail-value"><?= $mostrar($movimiento['ubicacion_destino_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Asignación destino</span>
                <span class="detail-value"><?= $mostrar($movimiento['numero_asignacion_destino'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Bienes trasladados</h2>
        <?php if (empty($detalles)): ?>
            <p class="estado-vacio">Este traslado no tiene bienes registrados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Serie</th>
                            <th>Condición</th>
                            <th>Valor histórico</th>
                            <th>Asignación origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?= $mostrar($detalle['codigo_mostrado'] ?? null) ?></td>
                                <td>
                                    <div class="celda-bien-foto">
                                        <?= fotoBienThumb((int) ($detalle['id_bien'] ?? 0), $detalle['imagen_bien'] ?? null, $detalle['codigo_mostrado'] ?? null, $construirDescripcion($detalle), 'sm', 'raya') ?>
                                        <span><?= $mostrar($construirDescripcion($detalle)) ?></span>
                                    </div>
                                </td>
                                <td><?= $mostrar($detalle['serie'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['condicion_nueva'] ?? null) ?></td>
                                <td><?= formatearQuetzales($detalle['valor_movimiento'] ?? null) ?></td>
                                <td><?= $mostrar($detalle['numero_asignacion_origen'] ?? null) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/modal_foto_bien.php'; ?>
