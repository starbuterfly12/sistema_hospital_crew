<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BienesController::historial()).
// Solo lectura. Línea de tiempo vertical con la trazabilidad funcional del bien (eventos ya
// normalizados y ordenados de más reciente a más antiguo por HistorialBien::getEventos()).
$bien = $bien ?? [];
$eventos = $eventos ?? [];
$tiposPresentes = $tiposPresentes ?? [];
$tipoFiltro = $tipoFiltro ?? '';

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$idBien = (int) ($bien['id_bien'] ?? 0);

$claseBadgeEstado = static function (?string $nombreEstado): string {
    return match ($nombreEstado) {
        'Activo' => 'badge badge-exito',
        'Baja' => 'badge badge-error',
        default => 'badge',
    };
};

// Un color de badge por tipo de evento, discreto y coherente con la paleta existente.
$claseBadgeTipo = static function (string $tipo): string {
    return match ($tipo) {
        'Ingreso' => 'badge badge-exito',
        'Requisición' => 'badge badge-info',
        'Traslado' => 'badge badge-info',
        'Préstamo' => 'badge badge-pendiente',
        'Devolución' => 'badge badge-pendiente',
        'Verificación física' => 'badge',
        'SICOIN' => 'badge',
        'Baja' => 'badge badge-error',
        'Modificación de información' => 'badge badge-info',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Historial del bien</h1>
            <p class="page-subtitle">Trazabilidad funcional del bien a lo largo del tiempo.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bienes&accion=ver&id=<?= $idBien ?>" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($bien['codigo_interno'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($bien['descripcion'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Código interno</span>
                <span class="detail-value"><?= $mostrar($bien['codigo_interno'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">SICOIN</span>
                <span class="detail-value"><?= $mostrar($bien['codigo_sicoin'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Responsable actual</span>
                <span class="detail-value"><?= $mostrar($bien['responsable_actual'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Ubicación actual</span>
                <span class="detail-value"><?= $mostrar($bien['ubicacion_actual'] ?? null) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Estado actual</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado($bien['nombre_estado'] ?? null) ?>"><?= $mostrar($bien['nombre_estado'] ?? null) ?></span></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Total de eventos</span>
                <span class="detail-value"><?= count($eventos) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (count($tiposPresentes) > 1): ?>
    <form method="GET" action="index.php" class="filters">
        <input type="hidden" name="modulo" value="bienes">
        <input type="hidden" name="accion" value="historial">
        <input type="hidden" name="id" value="<?= $idBien ?>">

        <div class="form-group">
            <label class="form-label" for="tipo">Tipo de evento</label>
            <select id="tipo" name="tipo" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($tiposPresentes as $tipoDisponible): ?>
                    <option value="<?= htmlspecialchars($tipoDisponible, ENT_QUOTES, 'UTF-8') ?>" <?= $tipoFiltro === $tipoDisponible ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipoDisponible, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions-inline">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="index.php?modulo=bienes&accion=historial&id=<?= $idBien ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
<?php endif; ?>

<div class="card">
    <?php if (empty($eventos)): ?>
        <p class="estado-vacio">Este bien todavía no tiene eventos registrados<?= $tipoFiltro !== '' ? ' de ese tipo' : '' ?>.</p>
    <?php else: ?>
        <ol class="historial-timeline">
            <?php foreach ($eventos as $evento): ?>
                <li class="historial-evento">
                    <span class="historial-evento-punto" aria-hidden="true"></span>
                    <div class="historial-evento-cuerpo">
                        <div class="historial-evento-cabecera">
                            <span class="historial-evento-fecha"><?= $mostrar(formatFechaSegunTipo($evento['fecha'], (bool) $evento['es_datetime'])) ?></span>
                            <span class="<?= $claseBadgeTipo($evento['tipo']) ?>"><?= $mostrar($evento['tipo']) ?></span>
                            <?php if (!empty($evento['referencia'])): ?>
                                <span class="historial-evento-ref"><?= $mostrar($evento['referencia']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="historial-evento-titulo"><?= $mostrar($evento['titulo']) ?></p>
                        <p class="historial-evento-desc"><?= $mostrar($evento['descripcion']) ?></p>
                        <?php if (!empty($evento['detalles'])): ?>
                            <ul class="historial-evento-cambios">
                                <?php foreach ($evento['detalles'] as $detalle): ?>
                                    <li>
                                        <span class="historial-evento-campo"><?= $mostrar($detalle['campo'] ?? null) ?></span>
                                        <span class="historial-evento-transicion"><?= $mostrar($detalle['valor_anterior'] ?? null) ?> &rarr; <?= $mostrar($detalle['valor_nuevo'] ?? null) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($evento['usuario'])): ?>
                                <p class="historial-evento-usuario">Usuario: <?= $mostrar($evento['usuario']) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($evento['url_detalle'])): ?>
                            <a class="historial-evento-link" href="<?= htmlspecialchars($evento['url_detalle'], ENT_QUOTES, 'UTF-8') ?>">Ver detalle</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
