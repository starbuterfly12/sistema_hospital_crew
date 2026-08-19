<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de devolución</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $devolucion = $devolucion ?? [];
        $detalles = $detalles ?? [];

        $etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

        $etiquetasEstadoDevolucion = DevolucionesController::ETIQUETAS_ESTADO;

        $etiquetasEstadoPrestamo = [
            'activo' => 'Activo',
            'parcial' => 'Parcialmente devuelto',
            'finalizado' => 'Finalizado',
            'anulado' => 'Anulado',
        ];

        $tipoPrestamo = $devolucion['tipo_prestamo'] ?? '';
        $estadoPrestamo = $devolucion['estado_prestamo'] ?? '';
    ?>

    <h1>Detalle de devolución</h1>

    <h2>Datos generales</h2>
    <dl>
        <dt>No. devolución</dt>
        <dd><?= $mostrar($devolucion['numero_devolucion'] ?? null) ?></dd>

        <dt>Resultado de esta devolución</dt>
        <dd><?= $mostrar($etiquetasEstadoDevolucion[$devolucion['estado_devolucion'] ?? ''] ?? ($devolucion['estado_devolucion'] ?? null)) ?></dd>

        <dt>Fecha de devolución</dt>
        <dd><?= $mostrar(formatDate($devolucion['fecha_devolucion'] ?? null)) ?></dd>

        <dt>Registrada por</dt>
        <dd><?= $mostrar($devolucion['usuario_recibe_nombre'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($devolucion['created_at'] ?? null)) ?></dd>
    </dl>

    <h2>Préstamo relacionado</h2>
    <dl>
        <dt>No. préstamo</dt>
        <dd>
            <a href="index.php?modulo=prestamos&accion=ver&id=<?= (int) ($devolucion['id_prestamo'] ?? 0) ?>">
                <?= $mostrar($devolucion['numero_prestamo'] ?? null) ?>
            </a>
        </dd>

        <dt>Tipo</dt>
        <dd><?= $mostrar($etiquetasTipo[$tipoPrestamo] ?? $tipoPrestamo) ?></dd>

        <dt>Responsable permanente (origen)</dt>
        <dd><?= $mostrar($devolucion['responsable_origen_mostrado'] ?? null) ?></dd>

        <dt>Área origen</dt>
        <dd><?= $mostrar($devolucion['ubicacion_origen_mostrada'] ?? null) ?></dd>

        <dt>Responsable temporal</dt>
        <dd><?= $mostrar($devolucion['responsable_destino_mostrado'] ?? null) ?></dd>

        <dt>Área temporal</dt>
        <dd><?= $mostrar($devolucion['ubicacion_destino_mostrada'] ?? null) ?></dd>

        <dt>Estado actual del préstamo</dt>
        <dd><?= $mostrar($etiquetasEstadoPrestamo[$estadoPrestamo] ?? $estadoPrestamo) ?></dd>
    </dl>

    <?php if (!empty($devolucion['motivo'])): ?>
        <h2>Motivo</h2>
        <p><?= $mostrar($devolucion['motivo']) ?></p>
    <?php endif; ?>

    <h2>Bienes devueltos</h2>

    <?php if (empty($detalles)): ?>
        <p>Esta devolución no tiene bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Interno</th>
                    <th>No. SICOIN</th>
                    <th>Descripción</th>
                    <th>Serie</th>
                    <th>Condición al prestar</th>
                    <th>Condición al devolver</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['condicion_entrega'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['condicion_devolucion'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['observaciones'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($devolucion['observaciones'])): ?>
        <h2>Observaciones</h2>
        <p><?= $mostrar($devolucion['observaciones']) ?></p>
    <?php endif; ?>

    <p><a href="index.php?modulo=devoluciones">Volver al listado</a></p>
</body>
</html>
