<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áreas y ubicaciones</title>
</head>
<body>
    <h1>Áreas y ubicaciones</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=ubicaciones&accion=crear">Registrar ubicación</a></p>
    <?php endif; ?>

    <?php $q = $q ?? ''; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="ubicaciones">
        <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nombre o tipo">
        <button type="submit">Buscar</button>
        <?php if ($q !== ''): ?>
            <a href="index.php?modulo=ubicaciones">Limpiar búsqueda</a>
        <?php endif; ?>
    </form>

    <?php if (empty($ubicaciones)): ?>
        <p>No se encontraron ubicaciones.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <tr>
                        <td><?= htmlspecialchars($ubicacion['nombre_ubicacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ubicacion['tipo_ubicacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ubicacion['descripcion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ubicacion['estado_ubicacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=ubicaciones&accion=ver&id=<?= (int) $ubicacion['id_ubicacion'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
