<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluciones</title>
</head>
<body>
    <h1>Devoluciones</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=devoluciones&accion=crear">Registrar devolución</a>
        </p>
    <?php endif; ?>

    <?php
        $devoluciones = $devoluciones ?? [];

        $etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

        $etiquetasEstadoPrestamo = [
            'activo' => 'Activo',
            'parcial' => 'Parcialmente devuelto',
            'finalizado' => 'Finalizado',
            'anulado' => 'Anulado',
        ];
    ?>

    <?php if (empty($devoluciones)): ?>
        <p>No hay devoluciones registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. devolución</th>
                    <th>Préstamo</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Responsable temporal</th>
                    <th>Bienes devueltos</th>
                    <th>Registrada por</th>
                    <th>Préstamo queda</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devoluciones as $devolucion): ?>
                    <tr>
                        <td><?= htmlspecialchars($devolucion['numero_devolucion'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($devolucion['numero_prestamo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($etiquetasTipo[$devolucion['tipo_prestamo'] ?? ''] ?? ($devolucion['tipo_prestamo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDate($devolucion['fecha_devolucion'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($devolucion['responsable_destino_mostrado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($devolucion['cantidad_bienes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($devolucion['usuario_recibe_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($etiquetasEstadoPrestamo[$devolucion['estado_prestamo'] ?? ''] ?? ($devolucion['estado_prestamo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=devoluciones&accion=ver&id=<?= (int) $devolucion['id_devolucion'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
