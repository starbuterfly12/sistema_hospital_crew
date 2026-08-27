<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BajasController::solicitudes()).
// Bandeja administrativa de Solicitudes de baja — SOLO Administrador. Pertenece a Movimientos, por lo
// que el sidebar resalta "Movimientos" (sgbModuloActivo() ya mapea 'bajas' -> 'movimientos').
// Filtros por GET (búsqueda + estado + tipo de baja): el controlador los lee de $_GET y los pasa a
// Baja::getAll() (la misma consulta que usa el listado normal), y luego reordena para que las
// solicitudes 'pendiente' aparezcan primero. No hay acción "Registrar baja" aquí: esta pantalla es
// exclusivamente de revisión/decisión. La única acción por fila es "Revisar" -> accion=revisar, que
// abre bajas/ver.php en modo administrativo de solo lectura con los botones Aceptar / Rechazar.
$bajas = $bajas ?? [];
$tiposBaja = $tiposBaja ?? [];
$q = $q ?? '';
$estado = $estado ?? '';
$idTipoBaja = (int) ($idTipoBaja ?? 0);

$hayFiltros = ($q !== '' || $estado !== '' || $idTipoBaja > 0);

$etiquetasEstado = [
    'pendiente' => 'Pendiente',
    'autorizada' => 'Autorizada',
    'rechazada' => 'Rechazada',
    'finalizada' => 'Finalizada',
];

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'pendiente' => 'badge badge-pendiente',
        'autorizada' => 'badge badge-info',
        'rechazada' => 'badge badge-error',
        'finalizada' => 'badge badge-exito',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Solicitudes de baja</h1>
            <p class="page-subtitle">Consulta y gestión de las solicitudes de baja registradas en el sistema.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=movimientos" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="bajas">
    <input type="hidden" name="accion" value="solicitudes">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Número, bien o responsable">
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($etiquetasEstado as $valorEstado => $etiquetaEstado): ?>
                <option value="<?= $valorEstado ?>" <?= ($estado === $valorEstado) ? 'selected' : '' ?>><?= $etiquetaEstado ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="id_tipo_baja">Tipo de baja</label>
        <select id="id_tipo_baja" name="id_tipo_baja" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($tiposBaja as $tipo): ?>
                <option value="<?= (int) $tipo['id_tipo_baja'] ?>" <?= ($idTipoBaja === (int) $tipo['id_tipo_baja']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tipo['nombre_tipo_baja'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=bajas&accion=solicitudes" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($bajas)): ?>
        <p class="estado-vacio">No se encontraron solicitudes de baja<?= $hayFiltros ? ' con esos filtros' : ' registradas' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-solicitudes-baja">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha de preparación</th>
                        <th>Responsable del área</th>
                        <th>Servicio</th>
                        <th>Bienes</th>
                        <th>Bodega destino</th>
                        <th>Auxiliar de Inventarios</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bajas as $baja): ?>
                        <?php $estadoBaja = $baja['estado_baja'] ?? ''; ?>
                        <tr>
                            <td><?= $mostrar($baja['numero_baja'] ?? null) ?></td>
                            <td><?= $mostrar(formatDateTime($baja['fecha_preparacion'] ?? null)) ?></td>
                            <td><?= $mostrar($baja['responsable_descarga'] ?? null) ?></td>
                            <td><?= $mostrar($baja['ubicacion_responsable_descarga'] ?? null) ?></td>
                            <td><?= (int) ($baja['total_bienes'] ?? 0) ?></td>
                            <td><?= $mostrar($baja['ubicacion_bodega_destino'] ?? null) ?></td>
                            <td><?= $mostrar($baja['auxiliar_encargado'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($estadoBaja) ?>"><?= $mostrar($etiquetasEstado[$estadoBaja] ?? ($estadoBaja ?: null)) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=bajas&accion=revisar&id=<?= (int) $baja['id_baja'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6l4 4v14H5V3h4Z"/><path d="M9 12l2 2 4-4"/></svg>
                                        Revisar
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
