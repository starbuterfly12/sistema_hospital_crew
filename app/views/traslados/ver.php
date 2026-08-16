<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de traslado</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $movimiento = $movimiento ?? [];
        $detalles = $detalles ?? [];
    ?>

    <h1>Detalle de traslado</h1>

    <p><a href="index.php?modulo=traslados&accion=descargar_constancia&id=<?= (int) ($movimiento['id_movimiento'] ?? 0) ?>">Descargar constancia</a></p>

    <dl>
        <dt>Número</dt>
        <dd><?= $mostrar($movimiento['numero_movimiento'] ?? null) ?></dd>

        <dt>Fecha</dt>
        <dd><?= $mostrar(formatDateTime($movimiento['fecha_movimiento'] ?? null)) ?></dd>

        <dt>Responsable origen</dt>
        <dd><?= $mostrar($movimiento['responsable_origen_nombre'] ?? null) ?></dd>

        <dt>Ubicación origen</dt>
        <dd><?= $mostrar($movimiento['ubicacion_origen_nombre'] ?? null) ?></dd>

        <dt>Asignación destino</dt>
        <dd><?= $mostrar($movimiento['numero_asignacion_destino'] ?? null) ?></dd>

        <dt>Responsable destino</dt>
        <dd><?= $mostrar($movimiento['responsable_destino_nombre'] ?? null) ?></dd>

        <dt>Ubicación destino</dt>
        <dd><?= $mostrar($movimiento['ubicacion_destino_nombre'] ?? null) ?></dd>

        <dt>Motivo</dt>
        <dd><?= $mostrar($movimiento['motivo'] ?? null) ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($movimiento['observaciones'] ?? null) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($movimiento['usuario_registra_nombre'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($movimiento['created_at'] ?? null)) ?></dd>
    </dl>

    <h2>Bienes trasladados</h2>

    <?php if (empty($detalles)): ?>
        <p>Este traslado no tiene bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Serie</th>
                    <th>Condición</th>
                    <th>Valor histórico</th>
                    <th>Asignación origen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= $mostrar($detalle['codigo_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['descripcion'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['condicion_nueva'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['valor_movimiento'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['numero_asignacion_origen'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=traslados">Volver al listado</a></p>
</body>
</html>
