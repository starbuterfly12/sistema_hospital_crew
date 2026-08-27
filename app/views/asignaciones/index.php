<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver AsignacionesController::index()).
// Módulo de SOLO CONSULTA: las asignaciones se generan/actualizan automáticamente desde Ingreso de
// bienes, Requisiciones y Traslados. NO hay botón "Nueva asignación" ni acción Editar (ambas rutas
// del controlador solo redirigen con aviso). Filtro real existente: búsqueda por número / responsable
// / ubicación / estado (?q=).

$asignaciones = $asignaciones ?? [];
$q = $q ?? '';

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Pendiente' => 'badge badge-pendiente',
        'Asignada' => 'badge badge-exito',
        default => 'badge',
    };
};

$ubicacionTexto = static function (array $asignacion): ?string {
    $nombre = $asignacion['nombre_ubicacion'] ?? null;
    $tipo = $asignacion['tipo_ubicacion'] ?? null;

    if ($nombre === null || $nombre === '') {
        return null;
    }

    return $nombre . ($tipo !== null && $tipo !== '' ? ' - ' . $tipo : '');
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Asignaciones</h1>
            <p class="page-subtitle">Consulta de las asignaciones de bienes institucionales.</p>
        </div>
    </div>
</div>

<p class="form-hint">Las asignaciones se generan y actualizan automáticamente a partir de Ingreso de bienes, Requisiciones y Traslados.</p>

<?php if ($mensajeExito !== null): ?>
    <div class="alert alert-exito"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($mensajeError !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="asignaciones">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, responsable, ubicación o estado">
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=asignaciones" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($asignaciones)): ?>
        <p class="estado-vacio">No se encontraron asignaciones<?= $q !== '' ? ' con ese criterio' : '' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-asignaciones">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Responsable</th>
                        <th>Ubicación</th>
                        <th>Bienes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asignaciones as $asignacion): ?>
                        <tr>
                            <td><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></td>
                            <td><?= $mostrar(formatDate($asignacion['fecha_asignacion'] ?? null)) ?></td>
                            <td><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($ubicacionTexto($asignacion)) ?></td>
                            <td><?= (int) ($asignacion['cantidad_bienes'] ?? 0) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($asignacion['estado_asignacion'] ?? null) ?>"><?= $mostrar($asignacion['estado_asignacion'] ?? null) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=asignaciones&accion=ver&id=<?= (int) $asignacion['id_asignacion'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
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

<script src="<?= url('public/js/app.js') ?>"></script>
