<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de verificación física</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $mostrarSiNo = static function (?int $valor): string {
            if ($valor === null) {
                return 'No aplica';
            }

            return $valor === 1 ? 'Sí' : 'No';
        };

        $verificacion = $verificacion ?? [];

        $localizado = (int) ($verificacion['bien_localizado'] ?? 0);
        $tieneDiferencias = (int) ($verificacion['tiene_diferencias'] ?? 0);

        $responsableCorrecto = $verificacion['responsable_correcto'] ?? null;
        $responsableCorrecto = $responsableCorrecto === null ? null : (int) $responsableCorrecto;

        $ubicacionCorrecta = $verificacion['ubicacion_correcta'] ?? null;
        $ubicacionCorrecta = $ubicacionCorrecta === null ? null : (int) $ubicacionCorrecta;

        $condicionObservada = $verificacion['condicion_observada'] ?? null;
    ?>

    <h1>Detalle de verificación física</h1>

    <h2>Datos generales</h2>
    <dl>
        <dt>Fecha y hora</dt>
        <dd><?= $mostrar(formatDateTime($verificacion['fecha_hora'] ?? null)) ?></dd>

        <dt>Verificado por</dt>
        <dd><?= $mostrar($verificacion['usuario_verifica_nombre'] ?? null) ?></dd>

        <dt>Resultado</dt>
        <dd><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></dd>
    </dl>

    <h2>Bien</h2>
    <dl>
        <dt>No. de Bien</dt>
        <dd><?= $mostrar($verificacion['codigo_interno'] ?? null) ?></dd>

        <dt>SICOIN</dt>
        <dd><?= $mostrar($verificacion['codigo_sicoin'] ?? null) ?></dd>

        <dt>Descripción</dt>
        <dd><?= $mostrar($verificacion['descripcion'] ?? null) ?></dd>

        <dt>Marca</dt>
        <dd><?= $mostrar($verificacion['marca'] ?? null) ?></dd>

        <dt>Modelo</dt>
        <dd><?= $mostrar($verificacion['modelo'] ?? null) ?></dd>

        <dt>Serie</dt>
        <dd><?= $mostrar($verificacion['serie'] ?? null) ?></dd>
    </dl>
    <p>
        <a href="index.php?modulo=bienes&accion=ver&id=<?= (int) ($verificacion['id_bien'] ?? 0) ?>">Ver ficha actual del bien</a>
    </p>

    <h2>Datos registrados al momento de verificar</h2>
    <p>Información registrada en el sistema al momento de realizar la verificación.</p>
    <dl>
        <dt>Responsable registrado</dt>
        <dd><?= $mostrar($verificacion['responsable_registrado_nombre'] ?? null) ?></dd>

        <dt>Ubicación registrada</dt>
        <dd><?= $mostrar($verificacion['ubicacion_registrada_nombre'] ?? null) ?></dd>

        <dt>Condición registrada</dt>
        <dd><?= $mostrar($verificacion['condicion_registrada'] ?? null) ?></dd>
    </dl>

    <h2>Resultado físico</h2>
    <dl>
        <dt>Bien localizado</dt>
        <dd><?= $localizado === 1 ? 'Sí' : 'No' ?></dd>

        <dt>Responsable correcto</dt>
        <dd><?= $mostrarSiNo($responsableCorrecto) ?></dd>

        <dt>Ubicación correcta</dt>
        <dd><?= $mostrarSiNo($ubicacionCorrecta) ?></dd>

        <dt>Condición observada</dt>
        <dd><?= $condicionObservada !== null ? $mostrar($condicionObservada) : 'No aplica' ?></dd>

        <dt>Resultado</dt>
        <dd><?= $tieneDiferencias === 1 ? 'Con diferencias' : 'Sin diferencias' ?></dd>

        <dt>Observaciones</dt>
        <dd><?= $mostrar($verificacion['observaciones'] ?? null) ?></dd>
    </dl>

    <p><a href="index.php?modulo=verificaciones">Volver al listado</a></p>
</body>
</html>
