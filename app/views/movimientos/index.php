<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos</title>
</head>
<body>
    <?php $totalBajasPendientes = (int) ($totalBajasPendientes ?? 0); ?>

    <h1>Movimientos</h1>

    <ul>
        <li>
            <a href="index.php?modulo=traslados">Traslado</a>
            <p>Movilización permanente de uno o varios bienes entre asignaciones.</p>
        </li>

        <li>
            <a href="index.php?modulo=prestamos">Préstamo</a>
            <p>Entrega temporal de uno o varios bienes sin modificar su asignación original.</p>
        </li>

        <li>
            <a href="index.php?modulo=devoluciones">Devolución</a>
            <p>Registro de devolución total o parcial de bienes prestados.</p>
        </li>

        <li>
            <a href="index.php?modulo=bajas">Baja</a>
            <p>Preparación y registro de baja de uno o varios bienes institucionales.</p>
        </li>

        <li>
            <a href="index.php?modulo=verificaciones">Verificación física</a>
            <p>Comprobación física de ubicación, responsable y condición de los bienes.</p>
        </li>

        <?php if (tieneRol(['Administrador'])): ?>
            <li>
                <a href="index.php?modulo=bajas&accion=solicitudes">Solicitudes de baja</a>
                <?php if ($totalBajasPendientes > 0): ?>
                    (<?= $totalBajasPendientes ?> <?= $totalBajasPendientes === 1 ? 'pendiente' : 'pendientes' ?>)
                <?php endif; ?>
                <p>Bandeja administrativa: consulta de solicitudes de baja enviadas.</p>
            </li>
        <?php endif; ?>
    </ul>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
