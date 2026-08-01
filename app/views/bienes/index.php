<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienes institucionales</title>
</head>
<body>
    <h1>Bienes institucionales</h1>

    <?php if (empty($bienes)): ?>
        <p>No hay bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Código interno</th>
                    <th>Código SICOIN</th>
                    <th>Descripción</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Condición</th>
                    <th>Responsable actual</th>
                    <th>Ubicación actual</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bienes as $bien): ?>
                    <tr>
                        <td><?= htmlspecialchars($bien['codigo_interno'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['codigo_sicoin'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['descripcion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['marca'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['modelo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['serie'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['nombre_categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['nombre_estado'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['condicion_bien'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['responsable_actual'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($bien['ubicacion_actual'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
