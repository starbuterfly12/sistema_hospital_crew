<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos</title>
</head>
<body>
    <h1>Préstamos</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=prestamos&accion=crear">Registrar préstamo</a>
        </p>
    <?php endif; ?>

    <?php
        $prestamos = $prestamos ?? [];

        $etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

        $etiquetasEstado = [
            'activo' => 'Activo',
            'parcial' => 'Parcialmente devuelto',
            'finalizado' => 'Finalizado',
            'anulado' => 'Anulado',
        ];
    ?>

    <?php if (empty($prestamos)): ?>
        <p>No hay préstamos registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. interno</th>
                    <th>Tipo</th>
                    <th>Oficio No.</th>
                    <th>Responsable origen</th>
                    <th>Responsable temporal</th>
                    <th>Fecha préstamo</th>
                    <th>Devolución estimada</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $prestamo): ?>
                    <?php $vencido = (bool) ($prestamo['vencido'] ?? false); ?>
                    <tr>
                        <td><?= htmlspecialchars($prestamo['numero_prestamo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($etiquetasTipo[$prestamo['tipo_prestamo'] ?? ''] ?? ($prestamo['tipo_prestamo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($prestamo['numero_oficio'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($prestamo['responsable_origen_mostrado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($prestamo['responsable_destino_mostrado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDate($prestamo['fecha_prestamo'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDate($prestamo['fecha_devolucion_estimada'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($etiquetasEstado[$prestamo['estado_prestamo'] ?? ''] ?? ($prestamo['estado_prestamo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($vencido): ?>
                                <strong>— Vencido</strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?modulo=prestamos&accion=ver&id=<?= (int) $prestamo['id_prestamo'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
