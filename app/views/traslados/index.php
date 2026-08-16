<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados</title>
</head>
<body>
    <h1>Traslados</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=traslados&accion=crear">Registrar traslado</a>
        </p>
    <?php endif; ?>

    <?php $movimientos = $movimientos ?? []; ?>

    <?php if (empty($movimientos)): ?>
        <p>No hay traslados registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Responsable origen</th>
                    <th>Ubicación origen</th>
                    <th>Responsable destino</th>
                    <th>Ubicación destino</th>
                    <th>Cantidad de bienes</th>
                    <th>Registrado por</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimientos as $movimiento): ?>
                    <tr>
                        <td><?= htmlspecialchars($movimiento['numero_movimiento'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDateTime($movimiento['fecha_movimiento'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($movimiento['responsable_origen_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($movimiento['ubicacion_origen_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($movimiento['responsable_destino_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($movimiento['ubicacion_destino_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($movimiento['cantidad_bienes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($movimiento['usuario_registra_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=traslados&accion=ver&id=<?= (int) $movimiento['id_movimiento'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
