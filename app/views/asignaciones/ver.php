<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de asignación</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $asignacion = $asignacion ?? [];

        $nombreUbicacion = $asignacion['nombre_ubicacion'] ?? null;
        $tipoUbicacion = $asignacion['tipo_ubicacion'] ?? null;

        if ($nombreUbicacion !== null && $nombreUbicacion !== '') {
            $ubicacionTexto = $nombreUbicacion . ($tipoUbicacion !== null && $tipoUbicacion !== '' ? ' - ' . $tipoUbicacion : '');
        } else {
            $ubicacionTexto = null;
        }
    ?>

    <h1>Detalle de asignación</h1>

    <dl>
        <dt>Número</dt>
        <dd><?= $mostrar($asignacion['numero_asignacion'] ?? null) ?></dd>

        <dt>Responsable</dt>
        <dd><?= $mostrar($asignacion['responsable_nombre'] ?? null) ?></dd>

        <dt>Ubicación</dt>
        <dd><?= $mostrar($ubicacionTexto) ?></dd>

        <dt>Fecha de asignación</dt>
        <dd><?= $mostrar(formatDate($asignacion['fecha_asignacion'] ?? null)) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($asignacion['estado_asignacion'] ?? null) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($asignacion['usuario_registra_nombre'] ?? null) ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($asignacion['observaciones'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($asignacion['created_at'] ?? null)) ?></dd>

        <dt>Última actualización</dt>
        <dd><?= $mostrar(formatDateTime($asignacion['updated_at'] ?? null)) ?></dd>
    </dl>

    <h2>Bienes de la asignación</h2>

    <?php $bienesAsignacion = $bienesAsignacion ?? []; ?>

    <?php if (empty($bienesAsignacion)): ?>
        <p>Esta asignación no contiene bienes.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Condición</th>
                    <th>Fecha agregado</th>
                    <th>Fecha retirado</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bienesAsignacion as $detalle): ?>
                    <tr>
                        <td><?= $mostrar($detalle['codigo_interno'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['codigo_sicoin'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['descripcion'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['marca'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['modelo'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['condicion_bien'] ?? null) ?></td>
                        <td><?= $mostrar(formatDate($detalle['fecha_agregado'] ?? null)) ?></td>
                        <td><?= $mostrar(formatDate($detalle['fecha_retirado'] ?? null)) ?></td>
                        <td><?= $mostrar($detalle['estado_detalle'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['observaciones_detalle'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=asignaciones">Volver al listado</a></p>
</body>
</html>
