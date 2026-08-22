<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de bitácora</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $registro = $registro ?? [];

        if ($registro['id_usuario'] !== null) {
            if (($registro['nombre_completo'] ?? null) !== null && ($registro['usuario'] ?? null) !== null) {
                $usuarioMostrado = $mostrar($registro['nombre_completo']) . ' (' . $mostrar($registro['usuario']) . ')';
            } else {
                $usuarioMostrado = 'Usuario #' . (int) $registro['id_usuario'];
            }
        } elseif (!empty($registro['usuario_intentado'])) {
            $usuarioMostrado = 'No autenticado (intento: ' . $mostrar($registro['usuario_intentado']) . ')';
        } else {
            $usuarioMostrado = 'Sistema';
        }

        $resultadoMostrado = ($registro['resultado'] ?? '') === 'exitoso' ? 'Exitoso' : 'Fallido';
    ?>

    <h1>Detalle de bitácora</h1>

    <dl>
        <dt>ID de bitácora</dt>
        <dd><?= (int) ($registro['id_bitacora'] ?? 0) ?></dd>

        <dt>Fecha/hora</dt>
        <dd><?= $mostrar(formatDateTimeSeconds($registro['fecha_hora'] ?? null)) ?></dd>

        <dt>Usuario</dt>
        <dd><?= $usuarioMostrado ?></dd>

        <dt>Usuario intentado</dt>
        <dd><?= $mostrar($registro['usuario_intentado'] ?? null) ?></dd>

        <dt>Acción</dt>
        <dd><?= $mostrar($registro['accion'] ?? null) ?></dd>

        <dt>Módulo</dt>
        <dd><?= $mostrar($registro['modulo'] ?? null) ?></dd>

        <dt>Resultado</dt>
        <dd><?= $resultadoMostrado ?></dd>

        <dt>Descripción completa</dt>
        <dd><?= $mostrar($registro['descripcion'] ?? null) ?></dd>

        <dt>Tabla afectada</dt>
        <dd><?= $mostrar($registro['tabla_afectada'] ?? null) ?></dd>

        <dt>ID de registro afectado</dt>
        <dd><?= $mostrar($registro['id_registro_afectado'] ?? null) ?></dd>

        <dt>IP origen</dt>
        <dd><?= $mostrar($registro['ip_origen'] ?? null) ?></dd>
    </dl>

    <p><a href="index.php?modulo=bitacora">Volver al listado</a></p>
</body>
</html>
