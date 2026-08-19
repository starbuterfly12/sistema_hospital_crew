<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de tarjeta de responsabilidad</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $tarjeta = $tarjeta ?? [];
        $operaciones = $operaciones ?? [];

        // Misma regla de composición que RequisicionesController/PrestamosController::
        // construirDescripcionCompleta(). detalle_tarjeta_responsabilidad no tiene todavía
        // modelo_mostrado/serie_mostrada, así que hoy equivale a mostrar solo la descripción — queda
        // listo para cuando esas columnas existan.
        $descripcionCompleta = static function (array $operacion): string {
            $partes = [trim((string) ($operacion['descripcion_mostrada'] ?? ''))];

            $modelo = trim((string) ($operacion['modelo_mostrado'] ?? ''));
            if ($modelo !== '') {
                $partes[] = 'modelo: ' . $modelo;
            }

            $serie = trim((string) ($operacion['serie_mostrada'] ?? ''));
            if ($serie !== '') {
                $partes[] = 'serie: ' . $serie;
            }

            return implode(', ', $partes);
        };
    ?>

    <h1>Detalle de tarjeta de responsabilidad</h1>

    <dl>
        <dt>Número de tarjeta</dt>
        <dd><?= $mostrar($tarjeta['numero_tarjeta'] ?? null) ?></dd>

        <dt>Fecha de emisión</dt>
        <dd><?= $mostrar(formatDate($tarjeta['fecha_emision'] ?? null)) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($tarjeta['estado_tarjeta'] ?? null) ?></dd>

        <dt>Asignación de referencia</dt>
        <dd><?= $mostrar($tarjeta['numero_asignacion'] ?? null) ?></dd>

        <dt>Responsable</dt>
        <dd><?= $mostrar($tarjeta['responsable_nombre'] ?? null) ?></dd>

        <dt>NIT</dt>
        <dd><?= $mostrar($tarjeta['responsable_nit'] ?? null) ?></dd>

        <dt>Cargo</dt>
        <dd><?= $mostrar($tarjeta['responsable_cargo'] ?? null) ?></dd>

        <dt>Ubicación</dt>
        <dd><?= $mostrar($tarjeta['ubicacion_nombre'] ?? null) ?></dd>

        <dt>Generado por</dt>
        <dd><?= $mostrar($tarjeta['usuario_genera_nombre'] ?? null) ?></dd>
    </dl>

    <p><a href="index.php?modulo=tarjetas&accion=descargar_excel&id=<?= (int) ($tarjeta['id_tarjeta_responsabilidad'] ?? 0) ?>">Descargar Excel</a></p>

    <h2>Operaciones</h2>

    <?php if (empty($operaciones)): ?>
        <p>Esta tarjeta no tiene operaciones registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Fecha</th>
                    <th>SICOIN</th>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($operaciones as $operacion): ?>
                    <?php
                        $codigoMostrado = $operacion['codigo_mostrado'] ?? null;
                        $codigoTexto = ($codigoMostrado !== null && $codigoMostrado !== '')
                            ? $mostrar($codigoMostrado)
                            : 'Sin SICOIN';
                    ?>
                    <tr>
                        <td><?= (int) $operacion['orden_linea'] ?></td>
                        <td><?= $mostrar(formatDate($operacion['fecha_operacion'] ?? null)) ?></td>
                        <td><?= $codigoTexto ?></td>
                        <td><?= (int) $operacion['cantidad'] ?></td>
                        <td><?= $mostrar($descripcionCompleta($operacion)) ?></td>
                        <td><?= $mostrar($operacion['debe'] ?? null) ?></td>
                        <td><?= $mostrar($operacion['haber'] ?? null) ?></td>
                        <td><?= $mostrar($operacion['saldo_resultante'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=tarjetas">Volver al listado</a></p>
</body>
</html>
