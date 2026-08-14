<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjetas de responsabilidad</title>
</head>
<body>
    <h1>Tarjetas de responsabilidad</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=tarjetas&accion=generar">Generar tarjeta</a></p>
    <?php endif; ?>

    <?php $tarjetas = $tarjetas ?? []; ?>

    <?php if (empty($tarjetas)): ?>
        <p>No se han generado tarjetas de responsabilidad.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Número de tarjeta</th>
                    <th>Fecha de emisión</th>
                    <th>Responsable</th>
                    <th>Ubicación</th>
                    <th>Asignación</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tarjetas as $tarjeta): ?>
                    <tr>
                        <td><?= htmlspecialchars($tarjeta['numero_tarjeta'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($tarjeta['fecha_emision'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($tarjeta['responsable_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($tarjeta['ubicacion_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($tarjeta['numero_asignacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($tarjeta['estado_tarjeta'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=tarjetas&accion=ver&id=<?= (int) $tarjeta['id_tarjeta_responsabilidad'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
