<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsables</title>
</head>
<body>
    <h1>Responsables</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=responsables&accion=crear">Registrar responsable</a></p>
    <?php endif; ?>

    <?php $q = $q ?? ''; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="responsables">
        <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nombre o NIT">
        <button type="submit">Buscar</button>
        <?php if ($q !== ''): ?>
            <a href="index.php?modulo=responsables">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($responsables)): ?>
        <p>No se encontraron responsables.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nombre completo</th>
                    <th>NIT</th>
                    <th>Cargo</th>
                    <th>Profesión</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responsables as $responsable): ?>
                    <tr>
                        <td><?= htmlspecialchars($responsable['nombre_completo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($responsable['nit'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($responsable['cargo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($responsable['profesion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($responsable['nombre_ubicacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($responsable['estado_responsable'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=responsables&accion=ver&id=<?= (int) $responsable['id_responsable'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
