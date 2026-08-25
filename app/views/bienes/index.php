<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BienesController::index()).
// Sin filtros/búsqueda/paginación: Bien::getAll() no los tiene hoy y esta fase es solo visual —
// no se inventa funcionalidad nueva, solo se re-viste la tabla existente.

$bienes = $bienes ?? [];
$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

$mostrar = static function (?string $valor): string {
    return ($valor !== null && trim($valor) !== '') ? htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') : '—';
};

$claseBadgeEstado = static function (?string $nombreEstado): string {
    return match ($nombreEstado) {
        'Activo' => 'badge badge-exito',
        'Baja' => 'badge badge-error',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Bienes institucionales</h1>
            <p class="page-subtitle">Gestión y consulta de los bienes registrados en el sistema.</p>
        </div>

        <?php if ($puedeRegistrar): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="index.php?modulo=bienes&accion=crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Registrar bien
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <?php if (empty($bienes)): ?>
        <p class="estado-vacio">No hay bienes registrados.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-resizable">
                <thead>
                    <tr>
                        <th>Código interno</th>
                        <th>Código SICOIN</th>
                        <th class="col-descripcion">Descripción</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bienes as $bien): ?>
                        <tr>
                            <td><?= $mostrar($bien['codigo_interno']) ?></td>
                            <td><?= $mostrar($bien['codigo_sicoin']) ?></td>
                            <td><?= $mostrar($bien['descripcion']) ?></td>
                            <td><?= $mostrar($bien['marca']) ?></td>
                            <td><?= $mostrar($bien['modelo']) ?></td>
                            <td><?= $mostrar($bien['nombre_categoria']) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($bien['nombre_estado']) ?>"><?= $mostrar($bien['nombre_estado']) ?></span>
                            </td>
                            <td>
                                <a class="btn btn-secondary" href="index.php?modulo=bienes&accion=ver&id=<?= (int) $bien['id_bien'] ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="<?= url('public/js/app.js') ?>"></script>
