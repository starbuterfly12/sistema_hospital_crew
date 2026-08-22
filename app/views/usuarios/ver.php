<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del usuario</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $usuario = $usuario ?? [];
        $esUsuarioActual = $esUsuarioActual ?? false;

        $mensajeExito = $_SESSION['mensaje_exito'] ?? null;
        $mensajeError = $_SESSION['mensaje_error'] ?? null;
        unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

        $idUsuario = (int) ($usuario['id_usuario'] ?? 0);
    ?>

    <?php if ($mensajeExito !== null): ?>
        <p><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($mensajeError !== null): ?>
        <p><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Detalle del usuario</h1>

    <dl>
        <dt>Nombre</dt>
        <dd><?= $mostrar($usuario['nombre_completo'] ?? null) ?></dd>

        <dt>Usuario</dt>
        <dd><?= $mostrar($usuario['usuario'] ?? null) ?></dd>

        <dt>Rol</dt>
        <dd><?= $mostrar($usuario['nombre_rol'] ?? null) ?><?= (($usuario['estado_rol'] ?? 'activo') !== 'activo') ? ' (rol inactivo)' : '' ?></dd>

        <dt>Correo</dt>
        <dd><?= $mostrar($usuario['correo'] ?? null) ?></dd>

        <dt>Teléfono</dt>
        <dd><?= $mostrar($usuario['telefono'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= ($usuario['estado_usuario'] ?? '') === 'activo' ? 'Activo' : 'Inactivo' ?></dd>

        <dt>Último acceso</dt>
        <dd><?= ($usuario['ultimo_acceso'] ?? null) !== null ? $mostrar(formatDateTime($usuario['ultimo_acceso'])) : 'Nunca' ?></dd>

        <dt>Fecha de creación</dt>
        <dd><?= $mostrar(formatDateTime($usuario['created_at'] ?? null)) ?></dd>

        <dt>Última actualización</dt>
        <dd><?= ($usuario['updated_at'] ?? null) !== null ? $mostrar(formatDateTime($usuario['updated_at'])) : 'Nunca' ?></dd>
    </dl>

    <p><a href="index.php?modulo=usuarios&accion=editar&id=<?= $idUsuario ?>">Editar</a></p>

    <?php if (!$esUsuarioActual): ?>
        <?php if (($usuario['estado_usuario'] ?? null) === 'activo'): ?>
            <form method="POST" action="index.php?modulo=usuarios&accion=cambiarEstado&id=<?= $idUsuario ?>">
                <?= csrfField() ?>
                <button type="submit">Inactivar usuario</button>
            </form>
        <?php elseif (($usuario['estado_usuario'] ?? null) === 'inactivo'): ?>
            <form method="POST" action="index.php?modulo=usuarios&accion=cambiarEstado&id=<?= $idUsuario ?>">
                <?= csrfField() ?>
                <button type="submit">Activar usuario</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <h2>Cambiar contraseña</h2>

    <?php if ($esUsuarioActual): ?>
        <form method="POST" action="index.php?modulo=usuarios&accion=cambiarPassword&id=<?= $idUsuario ?>">
            <?= csrfField() ?>

            <div>
                <label for="password_actual">Contraseña actual *</label>
                <input type="password" id="password_actual" name="password_actual" required>
            </div>

            <div>
                <label for="password_nueva">Nueva contraseña *</label>
                <input type="password" id="password_nueva" name="password_nueva" minlength="8" required>
            </div>

            <div>
                <label for="password_confirmacion">Confirmar nueva contraseña *</label>
                <input type="password" id="password_confirmacion" name="password_confirmacion" minlength="8" required>
            </div>

            <div>
                <button type="submit">Actualizar contraseña</button>
            </div>
        </form>
    <?php else: ?>
        <form method="POST" action="index.php?modulo=usuarios&accion=cambiarPassword&id=<?= $idUsuario ?>">
            <?= csrfField() ?>

            <div>
                <label for="password_nueva">Nueva contraseña *</label>
                <input type="password" id="password_nueva" name="password_nueva" minlength="8" required>
            </div>

            <div>
                <label for="password_confirmacion">Confirmar nueva contraseña *</label>
                <input type="password" id="password_confirmacion" name="password_confirmacion" minlength="8" required>
            </div>

            <div>
                <button type="submit">Actualizar contraseña</button>
            </div>
        </form>
    <?php endif; ?>

    <p><a href="index.php?modulo=usuarios">Volver al listado</a></p>
</body>
</html>
