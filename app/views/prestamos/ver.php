<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de préstamo</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $prestamo = $prestamo ?? [];
        $detalles = $detalles ?? [];

        $etiquetasTipo = PrestamosController::ETIQUETAS_TIPO;

        $etiquetasEstado = [
            'activo' => 'Activo',
            'parcial' => 'Parcialmente devuelto',
            'finalizado' => 'Finalizado',
            'anulado' => 'Anulado',
        ];

        $etiquetasEstadoDetalle = [
            'prestado' => 'Prestado',
            'devuelto' => 'Devuelto',
        ];

        $tipoPrestamo = $prestamo['tipo_prestamo'] ?? '';
        $vencido = (bool) ($prestamo['vencido'] ?? false);
    ?>

    <h1>Detalle de préstamo</h1>

    <p><a href="index.php?modulo=prestamos&accion=descargar_constancia&id=<?= (int) ($prestamo['id_prestamo'] ?? 0) ?>">Descargar constancia</a></p>

    <h2>Datos generales</h2>
    <dl>
        <dt>No. interno</dt>
        <dd><?= $mostrar($prestamo['numero_prestamo'] ?? null) ?></dd>

        <dt>Tipo</dt>
        <dd><?= $mostrar($etiquetasTipo[$tipoPrestamo] ?? $tipoPrestamo) ?></dd>

        <dt>Oficio No.</dt>
        <dd><?= $mostrar($prestamo['numero_oficio'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd>
            <?= $mostrar($etiquetasEstado[$prestamo['estado_prestamo'] ?? ''] ?? ($prestamo['estado_prestamo'] ?? null)) ?>
            <?php if ($vencido): ?>
                <strong>— Vencido</strong>
            <?php endif; ?>
        </dd>

        <dt>Fecha del préstamo</dt>
        <dd><?= $mostrar(formatDate($prestamo['fecha_prestamo'] ?? null)) ?></dd>

        <dt>Fecha estimada de devolución</dt>
        <dd><?= $mostrar(formatDate($prestamo['fecha_devolucion_estimada'] ?? null)) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($prestamo['usuario_registra_nombre'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($prestamo['created_at'] ?? null)) ?></dd>
    </dl>

    <h2>Custodia</h2>
    <dl>
        <dt>Responsable permanente (origen)</dt>
        <dd><?= $mostrar($prestamo['responsable_origen_mostrado'] ?? null) ?></dd>

        <dt>Área origen</dt>
        <dd><?= $mostrar($prestamo['ubicacion_origen_mostrada'] ?? null) ?></dd>

        <dt>Responsable temporal</dt>
        <dd><?= $mostrar($prestamo['responsable_destino_mostrado'] ?? null) ?></dd>

        <dt>Área destino</dt>
        <dd><?= $mostrar($prestamo['ubicacion_destino_mostrada'] ?? null) ?></dd>
    </dl>

    <?php if (!empty($prestamo['motivo'])): ?>
        <h2>Justificación</h2>
        <p><?= $mostrar($prestamo['motivo']) ?></p>
    <?php endif; ?>

    <h2>Bienes</h2>

    <?php if (empty($detalles)): ?>
        <p>Este préstamo no tiene bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Interno</th>
                    <th>No. SICOIN</th>
                    <th>Descripción</th>
                    <th>Serie</th>
                    <th>Valor</th>
                    <th>Condición al entregar</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['valor_prestamo'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['condicion_entrega'] ?? null) ?></td>
                        <td><?= $mostrar($etiquetasEstadoDetalle[$detalle['estado_detalle'] ?? ''] ?? ($detalle['estado_detalle'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($prestamo['observaciones'])): ?>
        <h2>Observaciones</h2>
        <p><?= $mostrar($prestamo['observaciones']) ?></p>
    <?php endif; ?>

    <p><a href="index.php?modulo=prestamos">Volver al listado</a></p>
</body>
</html>
