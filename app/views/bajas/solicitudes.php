<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de baja</title>
</head>
<body>
    <h1>Solicitudes de baja</h1>

    <p>Bandeja administrativa de solicitudes de Baja. Las solicitudes <strong>Pendientes</strong> se muestran primero porque requieren revisión.</p>

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
        <p>No hay solicitudes de baja registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Baja</th>
                    <th>Fecha preparación</th>
                    <th>Responsable del área</th>
                    <th>Servicio</th>
                    <th>Bienes</th>
                    <th>Bodega destino</th>
                    <th>Auxiliar de Inventarios</th>
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
                        <td><?= (int) ($baja['total_bienes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($baja['ubicacion_bodega_destino'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($baja['auxiliar_encargado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($estado === 'pendiente'): ?>
                                <strong><?= htmlspecialchars($etiquetasEstado[$estado] ?? $estado, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php else: ?>
                                <?= htmlspecialchars($etiquetasEstado[$estado] ?? $estado, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?modulo=bajas&accion=revisar&id=<?= (int) $baja['id_baja'] ?>">Revisar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
