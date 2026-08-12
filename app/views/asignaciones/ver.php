<!DOCTYPE html>
<html lang="es">
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
        <dd><?= $mostrar($asignacion['fecha_asignacion'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($asignacion['estado_asignacion'] ?? null) ?></dd>

        <dt>Registrado por</dt>
        <dd><?= $mostrar($asignacion['usuario_registra_nombre'] ?? null) ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($asignacion['observaciones'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar($asignacion['created_at'] ?? null) ?></dd>

        <dt>Última actualización</dt>
        <dd><?= $mostrar($asignacion['updated_at'] ?? null) ?></dd>
    </dl>

    <?php if (($asignacion['estado_asignacion'] ?? null) === 'Pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
        <p><a href="index.php?modulo=asignaciones&accion=editar&id=<?= (int) $asignacion['id_asignacion'] ?>">Editar asignación</a></p>
    <?php endif; ?>

    <h2>Bienes de la asignación</h2>

    <?php $bienesAsignacion = $bienesAsignacion ?? []; ?>

    <?php if (empty($bienesAsignacion)): ?>
        <p>Todavía no se han agregado bienes a esta asignación.</p>
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
                    <th>Acción</th>
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
                        <td><?= $mostrar($detalle['fecha_agregado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['fecha_retirado'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['estado_detalle'] ?? null) ?></td>
                        <td><?= $mostrar($detalle['observaciones_detalle'] ?? null) ?></td>
                        <td>
                            <?php if (($detalle['estado_detalle'] ?? null) === 'activo' && ($asignacion['estado_asignacion'] ?? null) === 'Pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
                                <form method="POST" action="index.php?modulo=asignaciones&accion=retirar_bien&id=<?= (int) $asignacion['id_asignacion'] ?>">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id_detalle_asignacion" value="<?= (int) $detalle['id_detalle_asignacion'] ?>">
                                    <button type="submit">Retirar</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (($asignacion['estado_asignacion'] ?? null) === 'Pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
        <h2>Agregar bien</h2>

        <?php $bienesDisponibles = $bienesDisponibles ?? []; ?>

        <?php if (empty($bienesDisponibles)): ?>
            <p>No hay bienes disponibles para agregar.</p>
        <?php else: ?>
            <form method="POST" action="index.php?modulo=asignaciones&accion=agregar_bien&id=<?= (int) $asignacion['id_asignacion'] ?>">
                <?= csrfField() ?>

                <div>
                    <label for="id_bien">Bien *</label>
                    <select id="id_bien" name="id_bien" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($bienesDisponibles as $bien): ?>
                            <?php
                                $etiqueta = ($bien['codigo_interno'] ?? '-')
                                    . ' - ' . ($bien['descripcion'] ?? '-')
                                    . ' - ' . ($bien['serie'] ?: '-');
                            ?>
                            <option value="<?= (int) $bien['id_bien'] ?>"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="observaciones_detalle">Observaciones del detalle</label>
                    <textarea id="observaciones_detalle" name="observaciones_detalle"></textarea>
                </div>

                <div>
                    <button type="submit">Agregar bien</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($asignacion['estado_asignacion'] ?? null) === 'Pendiente' && tieneRol(['Administrador', 'Operativo'])): ?>
        <?php
            $tieneDetalleActivo = false;
            foreach ($bienesAsignacion as $detalle) {
                if (($detalle['estado_detalle'] ?? null) === 'activo') {
                    $tieneDetalleActivo = true;
                    break;
                }
            }
        ?>

        <?php if ($tieneDetalleActivo): ?>
            <form method="POST" action="index.php?modulo=asignaciones&accion=confirmar&id=<?= (int) $asignacion['id_asignacion'] ?>">
                <?= csrfField() ?>
                <button type="submit">Confirmar asignación</button>
            </form>
        <?php else: ?>
            <p>Agregue al menos un bien antes de confirmar la asignación.</p>
        <?php endif; ?>
    <?php endif; ?>

    <p><a href="index.php?modulo=asignaciones">Volver al listado</a></p>
</body>
</html>
