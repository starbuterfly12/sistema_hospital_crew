<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del bien</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $bien = $bien ?? [];
        $formaNombre = $formaNombre ?? '';
        $datosIngreso = $datosIngreso ?? [];

        $mensajeExito = $_SESSION['mensaje_exito'] ?? null;
        $mensajeError = $_SESSION['mensaje_error'] ?? null;
        unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
    ?>

    <?php if ($mensajeExito !== null): ?>
        <p><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($mensajeError !== null): ?>
        <p><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Detalle del bien</h1>

    <dl>
        <dt>Código interno</dt>
        <dd><?= $mostrar($bien['codigo_interno'] ?? null) ?></dd>

        <dt>Código SICOIN</dt>
        <dd><?= $mostrar($bien['codigo_sicoin'] ?? null) ?></dd>

        <dt>Descripción</dt>
        <dd><?= $mostrar($bien['descripcion'] ?? null) ?></dd>

        <dt>Marca</dt>
        <dd><?= $mostrar($bien['marca'] ?? null) ?></dd>

        <dt>Modelo</dt>
        <dd><?= $mostrar($bien['modelo'] ?? null) ?></dd>

        <dt>Serie</dt>
        <dd><?= $mostrar($bien['serie'] ?? null) ?></dd>

        <dt>Categoría</dt>
        <dd><?= $mostrar($bien['nombre_categoria'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($bien['nombre_estado'] ?? null) ?></dd>

        <dt>Condición</dt>
        <dd><?= $mostrar($bien['condicion_bien'] ?? null) ?></dd>

        <dt>Forma de ingreso</dt>
        <dd><?= $mostrar($bien['nombre_forma'] ?? null) ?></dd>

        <dt>Fecha de ingreso</dt>
        <dd><?= $mostrar(formatDate($bien['fecha_ingreso'] ?? null)) ?></dd>

        <dt>Costo</dt>
        <dd><?= $mostrar($bien['costo'] ?? null) ?></dd>

        <dt>Valor estimado</dt>
        <dd><?= $mostrar($bien['valor_estimado'] ?? null) ?></dd>

        <dt>Responsable actual</dt>
        <dd><?= $mostrar($bien['responsable_actual'] ?? null) ?></dd>

        <dt>Ubicación actual</dt>
        <dd><?= $mostrar($bien['ubicacion_actual'] ?? null) ?></dd>

        <dt>Tipo de ubicación</dt>
        <dd><?= $mostrar($bien['tipo_ubicacion'] ?? null) ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($bien['observaciones'] ?? null) ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?= $mostrar(formatDateTime($bien['created_at'] ?? null)) ?></dd>

        <dt>Fecha de actualización</dt>
        <dd><?= $mostrar(formatDateTime($bien['updated_at'] ?? null)) ?></dd>
    </dl>

    <?php if ($formaNombre === 'compra'): ?>
        <h2>Datos de compra</h2>

        <dl>
            <dt>Proveedor</dt>
            <dd><?= $mostrar($datosIngreso['proveedor'] ?? null) ?></dd>

            <dt>Número de factura</dt>
            <dd><?= $mostrar($datosIngreso['numero_factura'] ?? null) ?></dd>

            <dt>Serie de factura</dt>
            <dd><?= $mostrar($datosIngreso['serie_factura'] ?? null) ?></dd>

            <dt>Fecha de factura</dt>
            <dd><?= $mostrar(formatDate($datosIngreso['fecha_factura'] ?? null)) ?></dd>

            <dt>Número de liquidación</dt>
            <dd><?= $mostrar($datosIngreso['numero_liquidacion'] ?? null) ?></dd>

            <dt>¿Tiene garantía?</dt>
            <dd><?= ((int) ($datosIngreso['tiene_garantia'] ?? 0) === 1) ? 'Sí' : 'No' ?></dd>

            <?php if ((int) ($datosIngreso['tiene_garantia'] ?? 0) === 1): ?>
                <?php
                    $mesesGarantia = (int) ($datosIngreso['tiempo_garantia'] ?? 0);
                    $finGarantia = calcularFinGarantia($datosIngreso['fecha_factura'] ?? null, $mesesGarantia);
                ?>
                <dt>Tiempo de garantía</dt>
                <dd><?= $mostrar($mesesGarantia > 0 ? $mesesGarantia . ($mesesGarantia === 1 ? ' mes' : ' meses') : null) ?></dd>

                <dt>Fecha estimada de fin de garantía</dt>
                <dd><?= $mostrar(formatDate($finGarantia)) ?></dd>
            <?php endif; ?>

            <dt>Documento de respaldo</dt>
            <dd>
                <?php if (!empty($datosIngreso['documento_respaldo'])): ?>
                    <a href="<?= htmlspecialchars($datosIngreso['documento_respaldo'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ver documento</a>
                <?php else: ?>
                    Sin documento de respaldo
                <?php endif; ?>
            </dd>
        </dl>
    <?php elseif ($formaNombre === 'donacion'): ?>
        <h2>Datos de donación</h2>

        <dl>
            <dt>Procedencia</dt>
            <dd><?= $mostrar($datosIngreso['procedencia'] ?? null) ?></dd>

            <dt>Entidad donante</dt>
            <dd><?= $mostrar($datosIngreso['entidad_donante'] ?? null) ?></dd>

            <dt>Número de acta</dt>
            <dd><?= $mostrar($datosIngreso['numero_acta'] ?? null) ?></dd>

            <dt>Fecha de acta</dt>
            <dd><?= $mostrar(formatDate($datosIngreso['fecha_acta'] ?? null)) ?></dd>

            <dt>Documento de respaldo</dt>
            <dd>
                <?php if (!empty($datosIngreso['documento_respaldo'])): ?>
                    <a href="<?= htmlspecialchars($datosIngreso['documento_respaldo'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ver documento</a>
                <?php else: ?>
                    Sin documento de respaldo
                <?php endif; ?>
            </dd>
        </dl>
    <?php elseif ($formaNombre === 'traslado'): ?>
        <h2>Datos de traslado</h2>

        <dl>
            <dt>Procedencia</dt>
            <dd><?= $mostrar($datosIngreso['procedencia'] ?? null) ?></dd>

            <dt>Unidad ejecutora de origen</dt>
            <dd><?= $mostrar($datosIngreso['unidad_ejecutora_origen'] ?? null) ?></dd>

            <dt>Código de unidad de origen</dt>
            <dd><?= $mostrar($datosIngreso['codigo_unidad_origen'] ?? null) ?></dd>

            <dt>Número de acta</dt>
            <dd><?= $mostrar($datosIngreso['numero_acta'] ?? null) ?></dd>

            <dt>Fecha de acta</dt>
            <dd><?= $mostrar(formatDate($datosIngreso['fecha_acta'] ?? null)) ?></dd>

            <dt>Documento de respaldo</dt>
            <dd>
                <?php if (!empty($datosIngreso['documento_respaldo'])): ?>
                    <a href="<?= htmlspecialchars($datosIngreso['documento_respaldo'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ver documento</a>
                <?php else: ?>
                    Sin documento de respaldo
                <?php endif; ?>
            </dd>
        </dl>
    <?php endif; ?>

    <h2>Código QR</h2>

    <?php if (!empty($bien['ruta_qr'])): ?>
        <?php
            // Cache busting: el nombre de archivo es estable (bien_{id}.png), así que sin este
            // parámetro el navegador puede seguir mostrando el PNG anterior tras regenerar el QR.
            $versionQr = !empty($bien['updated_at']) ? strtotime((string) $bien['updated_at']) : time();
            $rutaQrConVersion = $bien['ruta_qr'] . '?v=' . $versionQr;
        ?>
        <p>
            <img src="<?= htmlspecialchars($rutaQrConVersion, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
        </p>

        <?php // codigo_qr (la URL codificada dentro del QR) se conserva en BD y en el PNG, pero ya
              // no se muestra como texto en pantalla — la usuaria no quiere ver rutas/URLs técnicas. ?>

        <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
            <form method="POST" action="index.php?modulo=bienes&accion=generar_qr&id=<?= (int) $bien['id_bien'] ?>">
                <?= csrfField() ?>
                <button type="submit">Regenerar QR</button>
            </form>
        <?php endif; ?>

        <p>
            <a href="index.php?modulo=bienes&accion=imprimir_qr&id=<?= (int) $bien['id_bien'] ?>">
                Ver QR para imprimir
            </a>
        </p>
    <?php else: ?>
        <p>Este bien aún no tiene un código QR generado.</p>

        <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
            <form method="POST" action="index.php?modulo=bienes&accion=generar_qr&id=<?= (int) $bien['id_bien'] ?>">
                <?= csrfField() ?>
                <button type="submit">Generar QR</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <p>
        <a href="index.php?modulo=verificaciones&id_bien=<?= (int) $bien['id_bien'] ?>">
            Ver historial de verificaciones
        </a>
    </p>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=verificaciones&accion=crear&id_bien=<?= (int) $bien['id_bien'] ?>">
                Registrar verificación física
            </a>
        </p>
        <p>
            <a href="index.php?modulo=bienes&accion=editar&id=<?= (int) $bien['id_bien'] ?>">
                Editar
            </a>
        </p>
        <p>
            <a href="index.php?modulo=bienes&accion=cambiar_condicion&id=<?= (int) $bien['id_bien'] ?>">
                Cambiar condición
            </a>
        </p>
    <?php endif; ?>
    <p><a href="index.php?modulo=bienes">Volver</a></p>
</body>
</html>
