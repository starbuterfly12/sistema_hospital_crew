<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        // Para el value="" del input de búsqueda (editable): a diferencia de $mostrar() (pensado para
        // texto de solo lectura), esto NUNCA debe convertir vacío/null en "-" — si lo hiciera, el
        // campo de búsqueda quedaría con el literal "-" como valor, y al reenviar el formulario sin
        // tocarlo se buscaría por "-" en vez de mostrar todos los usuarios.
        $valorInput = static function ($value): string {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        $usuarios = $usuarios ?? [];
        $roles = $roles ?? [];
        $q = $q ?? '';
        $idRol = $idRol ?? 0;
        $estado = $estado ?? '';
    ?>

    <h1>Usuarios</h1>

    <p><a href="index.php?modulo=usuarios&accion=crear">Registrar usuario</a></p>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="usuarios">
        <input type="text" name="q" value="<?= $valorInput($q) ?>" placeholder="Buscar por nombre o usuario">

        <select name="id_rol">
            <option value="">Todos los roles</option>
            <?php foreach ($roles as $rol): ?>
                <option value="<?= (int) $rol['id_rol'] ?>" <?= $idRol === (int) $rol['id_rol'] ? 'selected' : '' ?>><?= $mostrar($rol['nombre_rol']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
        </select>

        <button type="submit">Buscar</button>
        <?php if ($q !== '' || $idRol > 0 || $estado !== ''): ?>
            <a href="index.php?modulo=usuarios">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($usuarios)): ?>
        <p>No se encontraron usuarios.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nombre</th>
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
                    <tr>
                        <td><?= $mostrar($usuario['nombre_completo']) ?></td>
                        <td><?= $mostrar($usuario['usuario']) ?></td>
                        <td><?= $mostrar($usuario['nombre_rol']) ?></td>
                        <td><?= $mostrar($usuario['correo']) ?></td>
                        <td><?= $usuario['estado_usuario'] === 'activo' ? 'Activo' : 'Inactivo' ?></td>
                        <td><?= $usuario['ultimo_acceso'] !== null ? $mostrar(formatDateTime($usuario['ultimo_acceso'])) : 'Nunca' ?></td>
                        <td>
                            <a href="index.php?modulo=usuarios&accion=ver&id=<?= (int) $usuario['id_usuario'] ?>">Ver</a>
                            |
                            <a href="index.php?modulo=usuarios&accion=editar&id=<?= (int) $usuario['id_usuario'] ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
