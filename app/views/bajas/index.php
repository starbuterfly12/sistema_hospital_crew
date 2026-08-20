<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bajas</title>
</head>
<body>
    <h1>Bajas</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=bajas&accion=crear">Registrar baja</a>
        </p>
    <?php endif; ?>

    <?php
        $bajas = $bajas ?? [];
        $etiquetasEstado = [
            'pendiente' => 'Pendiente',
            'autorizada' => 'Autorizada',
            'rechazada' => 'Rechazada',
            'finalizada' => 'Finalizada',
        ];
    ?>

    <?php if (empty($bajas)): ?>
        <p>No hay bajas registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Baja</th>
                    <th>Fecha preparación</th>
                    <th>Responsable</th>
                    <th>Servicio</th>
                    <th>Bodega destino</th>
                    <th>Bienes</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bajas as $baja): ?>
                    <?php $estado = $baja['estado_baja'] ?? ''; ?>
                    <tr>
                        <td><?= htmlspecialchars($baja['numero_baja'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDateTime($baja['fecha_preparacion'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($baja['responsable_descarga'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($baja['ubicacion_responsable_descarga'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($baja['ubicacion_bodega_destino'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($baja['total_bienes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($etiquetasEstado[$estado] ?? $estado, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=bajas&accion=ver&id=<?= (int) $baja['id_baja'] ?>">Ver</a>
                            <?php if ($estado === 'pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
                                | <a href="index.php?modulo=bajas&accion=editar&id=<?= (int) $baja['id_baja'] ?>">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
