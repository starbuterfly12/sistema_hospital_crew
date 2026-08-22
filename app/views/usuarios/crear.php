<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar usuario</title>
</head>
<body>
    <?php
        $roles = $roles ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Registrar usuario</h1>

    <form method="POST" action="index.php?modulo=usuarios&accion=guardar">
        <?= csrfField() ?>

        <div>
            <label for="nombre_completo">Nombre completo *</label>
            <input
                type="text"
                id="nombre_completo"
                name="nombre_completo"
                maxlength="150"
                value="<?= htmlspecialchars($datosFormulario['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="usuario">Usuario *</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                minlength="3"
                maxlength="50"
                value="<?= htmlspecialchars($datosFormulario['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="correo">Correo</label>
            <input
                type="text"
                id="correo"
                name="correo"
                maxlength="100"
                value="<?= htmlspecialchars($datosFormulario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="telefono">Teléfono</label>
            <input
                type="text"
                id="telefono"
                name="telefono"
                maxlength="20"
                value="<?= htmlspecialchars($datosFormulario['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="id_rol">Rol *</label>
            <select id="id_rol" name="id_rol" required>
                <option value="">Seleccione</option>
                <?php foreach ($roles as $rol): ?>
                    <option
                        value="<?= (int) $rol['id_rol'] ?>"
                        <?= ((int) ($datosFormulario['id_rol'] ?? 0) === (int) $rol['id_rol']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="password">Contraseña *</label>
            <input type="password" id="password" name="password" minlength="8" required>
        </div>

        <div>
            <label for="password_confirmacion">Confirmar contraseña *</label>
            <input type="password" id="password_confirmacion" name="password_confirmacion" minlength="8" required>
        </div>

        <div>
            <label for="estado_usuario">Estado</label>
            <select id="estado_usuario" name="estado_usuario">
                <option value="activo" <?= ($datosFormulario['estado_usuario'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($datosFormulario['estado_usuario'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div>
            <button type="submit">Guardar usuario</button>
            <a href="index.php?modulo=usuarios">Cancelar</a>
        </div>
    </form>
</body>
</html>
