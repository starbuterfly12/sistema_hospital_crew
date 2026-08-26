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
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=bienes&accion=ver&id=<?= (int) $bien['id_bien'] ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <?php if ($puedeRegistrar): ?>
                                        <a class="table-action-btn table-action-editar" href="index.php?modulo=bienes&accion=editar&id=<?= (int) $bien['id_bien'] ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Editar
                                        </a>
                                    <?php endif; ?>
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
