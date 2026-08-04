<!DOCTYPE html>
<html lang="es">
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
        <dd><?= $mostrar($bien['fecha_ingreso'] ?? null) ?></dd>

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
        <dd><?= $mostrar($bien['created_at'] ?? null) ?></dd>

        <dt>Fecha de actualización</dt>
        <dd><?= $mostrar($bien['updated_at'] ?? null) ?></dd>
    </dl>

    <h2>Código QR</h2>

    <?php if (!empty($bien['ruta_qr'])): ?>
        <p>
            <img src="<?= htmlspecialchars($bien['ruta_qr'], ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
        </p>

        <p><?= htmlspecialchars($bien['codigo_qr'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

        <form method="POST" action="index.php?modulo=bienes&accion=generar_qr&id=<?= (int) $bien['id_bien'] ?>">
            <button type="submit">Regenerar QR</button>
        </form>

        <p>
            <a href="<?= htmlspecialchars($bien['ruta_qr'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                Ver QR para imprimir
            </a>
        </p>
    <?php else: ?>
        <p>Este bien aún no tiene un código QR generado.</p>

        <form method="POST" action="index.php?modulo=bienes&accion=generar_qr&id=<?= (int) $bien['id_bien'] ?>">
            <button type="submit">Generar QR</button>
        </form>
    <?php endif; ?>

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
    <p><a href="index.php?modulo=bienes">Volver</a></p>
</body>
</html>
