<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del responsable</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $responsable = $responsable ?? [];
    ?>

    <h1>Detalle del responsable</h1>

    <dl>
        <dt>Nombre completo</dt>
        <dd><?= $mostrar($responsable['nombre_completo'] ?? null) ?></dd>

        <dt>NIT</dt>
        <dd><?= $mostrar($responsable['nit'] ?? null) ?></dd>

        <dt>Cargo</dt>
        <dd><?= $mostrar($responsable['cargo'] ?? null) ?></dd>

        <dt>Profesión</dt>
        <dd><?= $mostrar($responsable['profesion'] ?? null) ?></dd>

        <dt>Teléfono</dt>
        <dd><?= $mostrar($responsable['telefono'] ?? null) ?></dd>

        <dt>Ubicación</dt>
        <dd><?= $mostrar($responsable['nombre_ubicacion'] ?? null) ?></dd>

        <dt>Tipo de ubicación</dt>
        <dd><?= $mostrar($responsable['tipo_ubicacion'] ?? null) ?></dd>

        <dt>Estado</dt>
        <dd><?= $mostrar($responsable['estado_responsable'] ?? null) ?></dd>
    </dl>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=responsables&accion=editar&id=<?= (int) ($responsable['id_responsable'] ?? 0) ?>">Editar</a>
        </p>

        <?php if (($responsable['estado_responsable'] ?? null) === 'activo'): ?>
            <form method="POST" action="index.php?modulo=responsables&accion=cambiar_estado&id=<?= (int) ($responsable['id_responsable'] ?? 0) ?>">
                <?= csrfField() ?>
                <button type="submit">Inactivar responsable</button>
            </form>
        <?php elseif (($responsable['estado_responsable'] ?? null) === 'inactivo'): ?>
            <form method="POST" action="index.php?modulo=responsables&accion=cambiar_estado&id=<?= (int) ($responsable['id_responsable'] ?? 0) ?>">
                <?= csrfField() ?>
                <button type="submit">Activar responsable</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <p><a href="index.php?modulo=responsables">Volver al listado</a></p>
</body>
</html>
