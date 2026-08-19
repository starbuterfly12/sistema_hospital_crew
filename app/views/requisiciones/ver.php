<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de requisición</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $requisicion = $requisicion ?? [];
        $numeros = $numeros ?? [];
        $detalles = $detalles ?? [];

        $idRequisicion = (int) ($requisicion['id_requisicion'] ?? 0);
        $estado = $requisicion['estado_requisicion'] ?? '';

        $etiquetasEstadoDetalle = [
            'pendiente' => 'Pendiente',
            'reservado' => 'Reservado',
            'entregado' => 'Entregado',
            'anulado' => 'Anulado',
        ];
    ?>

    <h1>Detalle de requisición</h1>

    <?php if (in_array($estado, ['Autorizada', 'Entregada'], true)): ?>
        <p><a href="index.php?modulo=requisiciones&accion=descargar_constancia&id=<?= $idRequisicion ?>">Descargar constancia</a></p>
    <?php endif; ?>

    <h2>Datos generales</h2>
    <dl>
        <dt>No. sistema</dt>
        <dd><?= $mostrar($requisicion['numero_requisicion_sistema'] ?? null) ?></dd>

        <dt>Oficio No.</dt>
        <dd><?= $mostrar($requisicion['numero_oficio'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($estado) ?></dd>

        <dt>Responsable solicitante</dt>
        <dd><?= $mostrar($requisicion['responsable_solicitante_mostrado'] ?? null) ?></dd>

        <dt>Área/Servicio</dt>
        <dd><?= $mostrar($requisicion['ubicacion_solicitante_mostrada'] ?? null) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($requisicion['usuario_registra_nombre'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($requisicion['created_at'] ?? null)) ?></dd>

        <?php if (!empty($requisicion['fecha_autorizacion'])): ?>
            <dt>Autorizado por</dt>
            <dd><?= $mostrar($requisicion['usuario_autoriza_nombre'] ?? null) ?></dd>

            <dt>Fecha de autorización</dt>
            <dd><?= $mostrar(formatDateTime($requisicion['fecha_autorizacion'] ?? null)) ?></dd>
        <?php endif; ?>

        <?php if (!empty($requisicion['fecha_entrega'])): ?>
            <dt>Entrega confirmada por</dt>
            <dd><?= $mostrar($requisicion['usuario_entrega_nombre'] ?? null) ?></dd>

            <dt>Fecha de entrega</dt>
            <dd><?= $mostrar(formatDateTime($requisicion['fecha_entrega'] ?? null)) ?></dd>
        <?php endif; ?>

        <?php if (!empty($requisicion['motivo_anulacion'])): ?>
            <dt>Motivo de anulación</dt>
            <dd><?= $mostrar($requisicion['motivo_anulacion']) ?></dd>
        <?php endif; ?>
    </dl>

    <h2>Requisiciones institucionales</h2>

    <?php if (empty($numeros)): ?>
        <p>No hay números de requisición registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($numeros as $numero): ?>
                    <tr>
                        <td><?= $mostrar($numero['numero_requisicion'] ?? null) ?></td>
                        <td><?= $mostrar(formatDate($numero['fecha_requisicion'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Bienes</h2>

    <?php if (empty($detalles)): ?>
        <p>Esta requisición no tiene bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Interno</th>
                    <th>No. SICOIN</th>
                    <th>Descripción</th>
                    <th>Serie</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; ?>
                <?php foreach ($detalles as $detalle): ?>
                    <?php $total += (float) ($detalle['valor_mostrado'] ?? 0); ?>
                    <tr>
                        <td><?= $mostrar($detalle['codigo_interno_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['codigo_sicoin_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['descripcion_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['valor_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($etiquetasEstadoDetalle[$detalle['estado_detalle'] ?? ''] ?? ($detalle['estado_detalle'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td><strong>Total: <?= number_format($total, 2) ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if (!empty($requisicion['observaciones'])): ?>
        <h2>Observaciones</h2>
        <p><?= $mostrar($requisicion['observaciones']) ?></p>
    <?php endif; ?>

    <h2>Acciones</h2>

    <?php if ($estado === 'Pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=requisiciones&accion=editar&id=<?= $idRequisicion ?>">Editar</a></p>

        <?php if (tieneRol(['Administrador'])): ?>
            <form method="POST" action="index.php?modulo=requisiciones&accion=autorizar&id=<?= $idRequisicion ?>">
                <?= csrfField() ?>
                <button type="submit">Autorizar</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($estado === 'Autorizada' && tieneRol(['Administrador', 'Operativo'])): ?>
        <form method="POST" action="index.php?modulo=requisiciones&accion=confirmar_entrega&id=<?= $idRequisicion ?>">
            <?= csrfField() ?>
            <button type="submit">Confirmar entrega</button>
        </form>
    <?php endif; ?>

    <?php if (in_array($estado, ['Pendiente', 'Autorizada'], true) && tieneRol(['Administrador', 'Operativo'])): ?>
        <form method="POST" action="index.php?modulo=requisiciones&accion=anular&id=<?= $idRequisicion ?>">
            <?= csrfField() ?>
            <div>
                <label for="motivo_anulacion">Motivo de anulación *</label>
                <textarea id="motivo_anulacion" name="motivo_anulacion" required></textarea>
            </div>
            <button type="submit">Anular</button>
        </form>
    <?php endif; ?>

    <p><a href="index.php?modulo=requisiciones">Volver al listado</a></p>
</body>
</html>
