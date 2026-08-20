<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de baja</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $baja = $baja ?? [];
        $detalles = $detalles ?? [];
        $idBaja = (int) ($baja['id_baja'] ?? 0);
        $estado = $baja['estado_baja'] ?? '';
        // true cuando el detalle se abre desde la bandeja administrativa (BajasController::revisar()):
        // ahí es exclusivamente de solo lectura, sin acceso a Editar, sin importar rol ni estado.
        $origenSolicitudes = $origenSolicitudes ?? false;

        $etiquetasEstado = [
            'pendiente' => 'Pendiente',
            'autorizada' => 'Autorizada',
            'rechazada' => 'Rechazada',
            'finalizada' => 'Finalizada',
        ];

        // Finalizar es exclusivo de quien registró la solicitud (id_usuario_registra), sin importar
        // su rol — ni siquiera la Administradora puede finalizar una Baja que no creó ella misma
        // (revisión funcional 2026-08-19).
        $idUsuarioActual = (int) ($_SESSION['id_usuario'] ?? 0);
        $idUsuarioRegistra = (int) ($baja['id_usuario_registra'] ?? 0);
        $esSolicitanteOriginal = $idUsuarioRegistra > 0 && $idUsuarioRegistra === $idUsuarioActual;
    ?>

    <h1>Detalle de baja</h1>

    <h2>Datos generales</h2>
    <dl>
        <dt>No. Baja</dt>
        <dd><?= $mostrar($baja['numero_baja'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($etiquetasEstado[$estado] ?? $estado) ?></dd>

        <dt>Responsable del área</dt>
        <dd><?= $mostrar($baja['responsable_descarga'] ?? null) ?></dd>

        <dt>Servicio</dt>
        <dd><?= $mostrar($baja['ubicacion_responsable_descarga'] ?? null) ?></dd>

        <dt>Auxiliar de Inventarios</dt>
        <dd><?= $mostrar($baja['auxiliar_encargado'] ?? null) ?></dd>

        <dt>Bodega destino</dt>
        <dd><?= $mostrar($baja['ubicacion_bodega_destino'] ?? null) ?></dd>

        <dt>Fecha de preparación</dt>
        <dd><?= $mostrar(formatDateTime($baja['fecha_preparacion'] ?? null)) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($baja['usuario_registra'] ?? null) ?></dd>

        <?php if (!empty($baja['fecha_autorizacion'])): ?>
            <dt>Autorizado por</dt>
            <dd><?= $mostrar($baja['usuario_autoriza'] ?? null) ?></dd>

            <dt>Fecha de autorización</dt>
            <dd><?= $mostrar(formatDateTime($baja['fecha_autorizacion'] ?? null)) ?></dd>
        <?php endif; ?>

        <?php if (!empty($baja['fecha_rechazo'])): ?>
            <dt>Rechazada por</dt>
            <dd><?= $mostrar($baja['usuario_rechaza'] ?? null) ?></dd>

            <dt>Fecha de rechazo</dt>
            <dd><?= $mostrar(formatDateTime($baja['fecha_rechazo'] ?? null)) ?></dd>
        <?php endif; ?>

        <?php if (!empty($baja['fecha_baja'])): ?>
            <dt>Fecha de baja (finalización)</dt>
            <dd><?= $mostrar(formatDate($baja['fecha_baja'] ?? null)) ?></dd>
        <?php endif; ?>

        <dt>Número de acta</dt>
        <dd><?= $mostrar($baja['numero_acta'] ?? null) ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($baja['observaciones'] ?? null) ?></dd>
    </dl>

    <h2>Bienes</h2>

    <?php if (empty($detalles)): ?>
        <p>Esta baja no tiene bienes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. de Bien</th>
                    <th>No. SICOIN</th>
                    <th>Descripción</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Valor</th>
                    <th>Tipo de baja</th>
                    <th>Justificación</th>
                    <th>Ubicación anterior</th>
                    <th>Estado anterior</th>
                    <th>Imagen</th>
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
                        <td><?= $mostrar($detalle['marca_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['modelo_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['serie_mostrada'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['valor_mostrado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['nombre_tipo_baja'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['justificacion'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['ubicacion_anterior'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['estado_bien_anterior'] ?? null) ?></td>
                        <td>
                            <?php if (!empty($detalle['imagen_bien'])): ?>
                                <a href="<?= url($detalle['imagen_bien']) ?>" target="_blank" rel="noopener">Ver</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"></td>
                    <td><strong>Total: <?= number_format($total, 2) ?></strong></td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <h2>Acciones</h2>

    <?php if ($origenSolicitudes): ?>
        <?php if ($estado === 'pendiente'): ?>
            <form method="POST" action="index.php?modulo=bajas&accion=autorizar&id=<?= $idBaja ?>" id="form-aceptar-baja">
                <?= csrfField() ?>
                <div>
                    <label for="password">Contraseña de Administrador *</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit">Aceptar solicitud</button>
            </form>

            <form method="POST" action="index.php?modulo=bajas&accion=rechazar&id=<?= $idBaja ?>" id="form-rechazar-baja">
                <?= csrfField() ?>
                <button type="submit">Rechazar solicitud</button>
            </form>
        <?php elseif ($estado === 'finalizada'): ?>
            <p><a href="index.php?modulo=bajas&accion=descargarComprobante&id=<?= $idBaja ?>">Descargar comprobante</a></p>
        <?php else: ?>
            <p>Esta solicitud ya no admite decisiones nuevas.</p>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($estado === 'pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
            <p><a href="index.php?modulo=bajas&accion=editar&id=<?= $idBaja ?>">Editar</a></p>
        <?php elseif ($estado === 'autorizada' && $esSolicitanteOriginal && tieneRol(['Administrador', 'Operativo'])): ?>
            <form method="POST" action="index.php?modulo=bajas&accion=finalizar&id=<?= $idBaja ?>" id="form-finalizar-baja">
                <?= csrfField() ?>
                <button type="submit">Finalizar baja</button>
            </form>
        <?php elseif ($estado === 'finalizada'): ?>
            <p><a href="index.php?modulo=bajas&accion=descargarComprobante&id=<?= $idBaja ?>">Descargar comprobante</a></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($origenSolicitudes): ?>
        <p><a href="index.php?modulo=bajas&accion=solicitudes">Volver a solicitudes</a></p>
    <?php else: ?>
        <p><a href="index.php?modulo=bajas">Volver al listado</a></p>
    <?php endif; ?>

    <?php if ($origenSolicitudes && $estado === 'pendiente'): ?>
        <script>
            document.getElementById('form-aceptar-baja').addEventListener('submit', function (evento) {
                if (!confirm('¿Está segura de aceptar esta solicitud de baja?')) {
                    evento.preventDefault();
                }
            });

            document.getElementById('form-rechazar-baja').addEventListener('submit', function (evento) {
                if (!confirm('¿Está seguro de rechazar esta solicitud de baja?')) {
                    evento.preventDefault();
                }
            });
        </script>
    <?php endif; ?>

    <?php if (!$origenSolicitudes && $estado === 'autorizada' && $esSolicitanteOriginal): ?>
        <script>
            document.getElementById('form-finalizar-baja').addEventListener('submit', function (evento) {
                if (!confirm('¿Está seguro de finalizar esta baja? Los bienes serán retirados de la responsabilidad actual y trasladados a la bodega destino.')) {
                    evento.preventDefault();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
