<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver VerificacionesController::index()).
// Esta misma vista sirve dos modos reales, sin inventar ninguno: listado general de verificaciones
// (accedido desde Movimientos) y, cuando llega ?id_bien=X (ej. "Ver historial de verificaciones"
// desde Detalle del bien), el historial filtrado de ese bien puntual. El título/subtítulo y el
// destino de "Volver" cambian según el modo; el resto (filtros, tabla, columnas) es el mismo.
$verificaciones = $verificaciones ?? [];
$responsables = $responsables ?? [];
$ubicaciones = $ubicaciones ?? [];
$filtros = $filtros ?? [];
$bienFiltro = $bienFiltro ?? null;

$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);
$modoHistorialBien = !empty($filtros['id_bien']);

$mostrar = static function (?string $valor): string {
    return ($valor !== null && trim($valor) !== '') ? htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeResultado = static function (int $tieneDiferencias): string {
    return $tieneDiferencias === 1 ? 'badge badge-pendiente' : 'badge badge-exito';
};

$claseBadgeLocalizado = static function (int $localizado): string {
    return $localizado === 1 ? 'badge badge-exito' : 'badge badge-error';
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title"><?= $modoHistorialBien ? 'Historial de verificaciones' : 'Verificaciones físicas' ?></h1>
            <p class="page-subtitle">
                <?= $modoHistorialBien
                    ? 'Consulta de las verificaciones físicas realizadas al bien.'
                    : 'Consulta de todas las verificaciones físicas registradas en el sistema.' ?>
            </p>
        </div>

        <div class="page-actions">
            <?php if ($modoHistorialBien): ?>
                <a href="index.php?modulo=bienes&accion=ver&id=<?= (int) $filtros['id_bien'] ?>" class="btn btn-secondary">Volver</a>
            <?php else: ?>
                <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
            <?php endif; ?>

            <?php if ($puedeRegistrar): ?>
                <a href="index.php?modulo=verificaciones&accion=crear<?= $modoHistorialBien ? '&id_bien=' . (int) $filtros['id_bien'] : '' ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Nueva verificación
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($modoHistorialBien && $bienFiltro !== null): ?>
    <div class="detail-identidad">
        <p class="detail-identidad-codigo"><?= $mostrar($bienFiltro['codigo_interno'] ?? null) ?></p>
        <p class="detail-identidad-descripcion"><?= $mostrar($bienFiltro['descripcion'] ?? null) ?></p>
    </div>
<?php endif; ?>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="verificaciones">
    <?php if ($modoHistorialBien): ?>
        <input type="hidden" name="id_bien" value="<?= (int) $filtros['id_bien'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label class="form-label" for="busqueda">Buscar</label>
        <input type="text" id="busqueda" name="busqueda" class="form-control" value="<?= $valorInput($filtros['busqueda'] ?? '') ?>" placeholder="Código interno, SICOIN, descripción...">
    </div>

    <div class="form-group">
        <label class="form-label" for="id_responsable">Responsable registrado</label>
        <select id="id_responsable" name="id_responsable" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($responsables as $responsable): ?>
                <option value="<?= (int) $responsable['id_responsable'] ?>" <?= ((int) ($filtros['id_responsable_registrado'] ?? 0) === (int) $responsable['id_responsable']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_ubicacion">Ubicación registrada</label>
        <select id="id_ubicacion" name="id_ubicacion" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($ubicaciones as $ubicacion): ?>
                <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_registrada'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ubicacion['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="localizado">Localizado</label>
        <select id="localizado" name="localizado" class="form-control">
            <option value="">Todos</option>
            <option value="1" <?= (($filtros['localizado'] ?? '') === '1') ? 'selected' : '' ?>>Sí</option>
            <option value="0" <?= (($filtros['localizado'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="con_diferencias">Resultado</label>
        <select id="con_diferencias" name="con_diferencias" class="form-control">
            <option value="">Todos</option>
            <option value="0" <?= (($filtros['con_diferencias'] ?? '') === '0') ? 'selected' : '' ?>>Sin diferencias</option>
            <option value="1" <?= (($filtros['con_diferencias'] ?? '') === '1') ? 'selected' : '' ?>>Con diferencias</option>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php?modulo=verificaciones<?= $modoHistorialBien ? '&id_bien=' . (int) $filtros['id_bien'] : '' ?>" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($verificaciones)): ?>
        <p class="estado-vacio">No hay verificaciones registradas<?= $modoHistorialBien ? ' para este bien' : '' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-resizable table-verificaciones">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Código interno</th>
                        <th>SICOIN</th>
                        <th>Descripción</th>
                        <th>Responsable registrado</th>
                        <th>Ubicación registrada</th>
                        <th>Localizado</th>
                        <th>Resultado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verificaciones as $verificacion): ?>
                        <?php
                            $localizado = (int) $verificacion['bien_localizado'];
                            $tieneDiferencias = (int) $verificacion['tiene_diferencias'];
                        ?>
                        <tr>
                            <td><?= $mostrar(formatDateTime($verificacion['fecha_hora'] ?? null)) ?></td>
                            <td><?= $mostrar($verificacion['codigo_interno'] ?? null) ?></td>
                            <td><?= $mostrar($verificacion['codigo_sicoin'] ?? null) ?></td>
                            <td><?= $mostrar($verificacion['descripcion'] ?? null) ?></td>
                            <td><?= $mostrar($verificacion['responsable_registrado_nombre'] ?? null) ?></td>
                            <td><?= $mostrar($verificacion['ubicacion_registrada_nombre'] ?? null) ?></td>
                            <td><span class="<?= $claseBadgeLocalizado($localizado) ?>"><?= $localizado === 1 ? 'Sí' : 'No' ?></span></td>
                            <td><span class="<?= $claseBadgeResultado($tieneDiferencias) ?>"><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=verificaciones&accion=ver&id=<?= (int) $verificacion['id_verificacion'] ?>">
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
