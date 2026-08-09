<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar responsable</title>
</head>
<body>
    <?php
        $responsable = $responsable ?? [];
        $ubicaciones = $ubicaciones ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Editar responsable</h1>

    <form method="POST" action="index.php?modulo=responsables&accion=editar&id=<?= (int) ($responsable['id_responsable'] ?? 0) ?>">
        <?= csrfField() ?>

        <div>
            <label for="nombre_completo">Nombre completo *</label>
            <input
                type="text"
                id="nombre_completo"
                name="nombre_completo"
                value="<?= htmlspecialchars($datosFormulario['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="nit">NIT *</label>
            <input
                type="text"
                id="nit"
                name="nit"
                maxlength="20"
                value="<?= htmlspecialchars($datosFormulario['nit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="cargo">Cargo *</label>
            <input
                type="text"
                id="cargo"
                name="cargo"
                value="<?= htmlspecialchars($datosFormulario['cargo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="profesion">Profesión</label>
            <input
                type="text"
                id="profesion"
                name="profesion"
                value="<?= htmlspecialchars($datosFormulario['profesion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
            <label for="id_ubicacion">Ubicación *</label>
            <select id="id_ubicacion" name="id_ubicacion" required>
                <option value="">Seleccione</option>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option
                        value="<?= (int) $ubicacion['id_ubicacion'] ?>"
                        <?= ((int) ($datosFormulario['id_ubicacion'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($ubicacion['nombre_ubicacion'] . ' - ' . $ubicacion['tipo_ubicacion'], ENT_QUOTES, 'UTF-8') ?><?= !empty($ubicacion['es_inactiva']) ? ' (Inactiva)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit">Guardar cambios</button>
            <a href="index.php?modulo=responsables&accion=ver&id=<?= (int) ($responsable['id_responsable'] ?? 0) ?>">Cancelar</a>
        </div>
    </form>
</body>
</html>
