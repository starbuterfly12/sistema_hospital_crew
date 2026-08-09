<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de ubicación</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $ubicacion = $ubicacion ?? [];
    ?>

    <h1>Detalle de ubicación</h1>

    <dl>
        <dt>Nombre</dt>
        <dd><?= $mostrar($ubicacion['nombre_ubicacion'] ?? null) ?></dd>

        <dt>Tipo</dt>
        <dd><?= $mostrar($ubicacion['tipo_ubicacion'] ?? null) ?></dd>

        <dt>Descripción</dt>
        <dd><?= $mostrar($ubicacion['descripcion'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($ubicacion['estado_ubicacion'] ?? null) ?></dd>
    </dl>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=ubicaciones&accion=editar&id=<?= (int) ($ubicacion['id_ubicacion'] ?? 0) ?>">Editar</a>
        </p>

        <?php if (($ubicacion['estado_ubicacion'] ?? null) === 'activa'): ?>
            <form method="POST" action="index.php?modulo=ubicaciones&accion=cambiar_estado&id=<?= (int) ($ubicacion['id_ubicacion'] ?? 0) ?>">
                <?= csrfField() ?>
                <button type="submit">Inactivar</button>
            </form>
        <?php elseif (($ubicacion['estado_ubicacion'] ?? null) === 'inactiva'): ?>
            <form method="POST" action="index.php?modulo=ubicaciones&accion=cambiar_estado&id=<?= (int) ($ubicacion['id_ubicacion'] ?? 0) ?>">
                <?= csrfField() ?>
                <button type="submit">Activar</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <p><a href="index.php?modulo=ubicaciones">Volver al listado</a></p>
</body>
</html>
