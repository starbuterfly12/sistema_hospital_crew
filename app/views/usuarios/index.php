<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UsuariosController::index()).
// Módulo exclusivo de Administrador (requireRole en cada acción del controlador). Filtros GET
// q/id_rol/estado ya los lee y valida el controlador y los pasa a Usuario::getAll() — no se toca
// ninguna consulta ni regla de negocio.
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$usuarios = $usuarios ?? [];
$roles = $roles ?? [];
$q = $q ?? '';
$idRol = (int) ($idRol ?? 0);
$estado = $estado ?? '';

$hayFiltros = ($q !== '' || $idRol > 0 || $estado !== '');

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-exito',
        'inactivo' => 'badge badge-error',
        default => 'badge',
    };
};

$svgOjo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
$svgLapiz = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Administración de usuarios y accesos al sistema.</p>
        </div>

        <div class="page-actions">
            <a class="btn btn-primary" href="index.php?modulo=usuarios&accion=crear">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Registrar usuario
            </a>
        </div>
    </div>
</div>

<form method="GET" action="index.php" class="filters">
    <input type="hidden" name="modulo" value="usuarios">

    <div class="form-group">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" value="<?= $valorInput($q) ?>" placeholder="Nombre o usuario">
    </div>

    <div class="form-group">
        <label class="form-label" for="id_rol">Rol</label>
        <select id="id_rol" name="id_rol" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($roles as $rol): ?>
                <option value="<?= (int) $rol['id_rol'] ?>" <?= $idRol === (int) $rol['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-control">
            <option value="">Todos</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
        </select>
    </div>

    <div class="form-actions-inline">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="index.php?modulo=usuarios" class="btn btn-secondary">Limpiar filtros</a>
    </div>
</form>

<div class="card">
    <?php if (empty($usuarios)): ?>
        <p class="estado-vacio">No se encontraron usuarios<?= $hayFiltros ? ' con esos filtros' : ' registrados' ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-resizable table-usuarios">
                <thead>
                    <tr>
                        <th>Nombre completo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Correo</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $estadoUsuario = $usuario['estado_usuario'] ?? ''; ?>
                        <tr>
                            <td><?= $mostrar($usuario['nombre_completo'] ?? null) ?></td>
                            <td><?= $mostrar($usuario['usuario'] ?? null) ?></td>
                            <td><?= $mostrar($usuario['nombre_rol'] ?? null) ?></td>
                            <td><?= $mostrar($usuario['correo'] ?? null) ?></td>
                            <td>
                                <span class="<?= $claseBadgeEstado($estadoUsuario) ?>"><?= $estadoUsuario === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </td>
                            <td><?= ($usuario['ultimo_acceso'] ?? null) !== null ? $mostrar(formatDateTime($usuario['ultimo_acceso'])) : 'Nunca' ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=usuarios&accion=ver&id=<?= (int) $usuario['id_usuario'] ?>">
                                        <?= $svgOjo ?>
                                        Ver
                                    </a>
                                    <a class="table-action-btn table-action-editar" href="index.php?modulo=usuarios&accion=editar&id=<?= (int) $usuario['id_usuario'] ?>">
                                        <?= $svgLapiz ?>
                                        Editar
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
