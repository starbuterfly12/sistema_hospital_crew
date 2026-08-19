<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisiciones</title>
</head>
<body>
    <h1>Requisiciones</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=requisiciones&accion=crear">Registrar requisición</a>
        </p>
    <?php endif; ?>

    <?php $requisiciones = $requisiciones ?? []; ?>

    <?php if (empty($requisiciones)): ?>
        <p>No hay requisiciones registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. sistema</th>
                    <th>Requisiciones institucionales</th>
                    <th>Oficio No.</th>
                    <th>Responsable solicitante</th>
                    <th>Área/Servicio</th>
                    <th>Bienes</th>
                    <th>Estado</th>
                    <th>Fecha de creación</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requisiciones as $requisicion): ?>
                    <tr>
                        <td><?= htmlspecialchars($requisicion['numero_requisicion_sistema'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($requisicion['numeros_institucionales'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($requisicion['numero_oficio'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($requisicion['responsable_solicitante_mostrado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($requisicion['ubicacion_solicitante_mostrada'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($requisicion['total_bienes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($requisicion['estado_requisicion'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(formatDateTime($requisicion['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?modulo=requisiciones&accion=ver&id=<?= (int) $requisicion['id_requisicion'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
