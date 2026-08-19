<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar ubicación</title>
</head>
<body>
    <?php
        $ubicacion = $ubicacion ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Editar ubicación</h1>

    <form method="POST" action="index.php?modulo=ubicaciones&accion=editar&id=<?= (int) ($ubicacion['id_ubicacion'] ?? 0) ?>">
        <?= csrfField() ?>

        <div>
            <label for="nombre_ubicacion">Nombre *</label>
            <input
                type="text"
                id="nombre_ubicacion"
                name="nombre_ubicacion"
                maxlength="150"
                value="<?= htmlspecialchars($datosFormulario['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="tipo_ubicacion">Tipo *</label>
            <?php $tipoUbicacionValor = $datosFormulario['tipo_ubicacion'] ?? ''; ?>
            <select id="tipo_ubicacion" name="tipo_ubicacion" required>
                <option value="">Seleccione</option>
                <?php foreach (UbicacionesController::TIPOS_UBICACION_VALIDOS as $tipoOpcion): ?>
                    <option value="<?= htmlspecialchars($tipoOpcion, ENT_QUOTES, 'UTF-8') ?>"<?= $tipoUbicacionValor === $tipoOpcion ? ' selected' : '' ?>><?= htmlspecialchars($tipoOpcion, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($datosFormulario['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Guardar cambios</button>
            <a href="index.php?modulo=ubicaciones&accion=ver&id=<?= (int) ($ubicacion['id_ubicacion'] ?? 0) ?>">Cancelar</a>
        </div>
    </form>
</body>
</html>
