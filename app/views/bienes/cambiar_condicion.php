<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar condición del bien</title>
</head>
<body>
    <?php
        $bien = $bien ?? [];
        $error = $error ?? null;
        $mostrar = static function ($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
    ?>

    <h1>Cambiar condición del bien</h1>

    <p><strong>Código interno:</strong> <?= $mostrar($bien['codigo_interno'] ?? '') ?></p>
    <p><strong>Descripción:</strong> <?= $mostrar($bien['descripcion'] ?? '') ?></p>
    <p><strong>Condición actual:</strong> <?= $mostrar($bien['condicion_bien'] ?? '') ?></p>

    <?php if (!empty($error)): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="condicion_bien">Condición del bien</label>
            <select id="condicion_bien" name="condicion_bien" required>
                <option value="Bueno"<?= (isset($bien['condicion_bien']) && $bien['condicion_bien'] === 'Bueno') ? ' selected' : '' ?>>Bueno</option>
                <option value="Regular"<?= (isset($bien['condicion_bien']) && $bien['condicion_bien'] === 'Regular') ? ' selected' : '' ?>>Regular</option>
                <option value="Malo"<?= (isset($bien['condicion_bien']) && $bien['condicion_bien'] === 'Malo') ? ' selected' : '' ?>>Malo</option>
            </select>
        </div>

        <button type="submit">Guardar cambio</button>
    </form>

    <p><a href="index.php?modulo=bienes&accion=ver&id=<?= (int) $bien['id_bien'] ?>">Volver al detalle</a></p>
</body>
</html>
