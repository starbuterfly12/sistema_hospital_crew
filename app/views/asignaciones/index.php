<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaciones</title>
</head>
<body>
    <h1>Asignaciones</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=asignaciones&accion=crear">Registrar asignación</a></p>
    <?php endif; ?>

    <?php $q = $q ?? ''; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="asignaciones">
        <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por número, responsable, ubicación o estado">
        <button type="submit">Buscar</button>
        <?php if ($q !== ''): ?>
            <a href="index.php?modulo=asignaciones">Limpiar búsqueda</a>
        <?php endif; ?>
    </form>

    <?php if (empty($asignaciones)): ?>
        <p>No se encontraron asignaciones.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Responsable</th>
                    <th>Ubicación</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Registrado por</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asignaciones as $asignacion): ?>
                    <?php
                        $nombreUbicacion = $asignacion['nombre_ubicacion'] ?? null;
                        $tipoUbicacion = $asignacion['tipo_ubicacion'] ?? null;

                        if ($nombreUbicacion !== null && $nombreUbicacion !== '') {
                            $ubicacionTexto = $nombreUbicacion . ($tipoUbicacion !== null && $tipoUbicacion !== '' ? ' - ' . $tipoUbicacion : '');
                        } else {
                            $ubicacionTexto = null;
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($asignacion['numero_asignacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($asignacion['responsable_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ubicacionTexto ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDate($asignacion['fecha_asignacion'] ?? null) ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($asignacion['estado_asignacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($asignacion['usuario_registra_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=asignaciones&accion=ver&id=<?= (int) $asignacion['id_asignacion'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
