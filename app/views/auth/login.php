<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital General de Chiquimula</title>
</head>
<body>
    <h1>Hospital General de Chiquimula</h1>
    <h2>Sistema de Gestión de Bienes</h2>

    <?php if (isset($error) && $error !== '') : ?>
        <p><?php echo htmlspecialchars((string) $error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>
        </div>

        <div>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">Ingresar</button>
    </form>
</body>
</html>
