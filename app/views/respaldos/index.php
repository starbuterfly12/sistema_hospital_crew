<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respaldos</title>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $respaldos = $respaldos ?? [];
        $mensajeExito = $mensajeExito ?? null;
        $mensajeError = $mensajeError ?? null;

        $mostrarGeneradoPor = static function (array $fila): string {
            if (($fila['nombre_completo'] ?? null) !== null && ($fila['usuario'] ?? null) !== null) {
                return htmlspecialchars($fila['nombre_completo'], ENT_QUOTES, 'UTF-8')
                    . ' (' . htmlspecialchars($fila['usuario'], ENT_QUOTES, 'UTF-8') . ')';
            }

            return '—';
        };

        // El archivo puede haber sido borrado manualmente del disco sin tocar la BD (fuera del
        // alcance de este módulo evitarlo) — en ese caso se muestra "No disponible" en vez de un
        // warning de filesize() sobre un archivo inexistente.
        $mostrarTamano = static function (array $fila): string {
            $rutaFisica = resolverRutaFisicaRespaldo($fila['ruta_archivo']);

            if ($rutaFisica === null || !is_file($rutaFisica)) {
                return 'No disponible';
            }

            $tamano = filesize($rutaFisica);

            return $tamano === false ? 'No disponible' : formatearTamanoArchivo($tamano);
        };

        $mostrarEstado = static function (string $estado): string {
            return $estado === 'generado' ? 'Generado' : htmlspecialchars($estado, ENT_QUOTES, 'UTF-8');
        };
    ?>

    <h1>Respaldos</h1>
    <p>Generación y consulta de respaldos de la base de datos del sistema.</p>
    <p>El respaldo generado contiene únicamente la base de datos. Los documentos y archivos almacenados fuera de MariaDB no se incluyen.</p>

    <?php if ($mensajeExito !== null): ?>
        <p><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($mensajeError !== null): ?>
        <p><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="index.php?modulo=respaldos&accion=generar" id="form-generar-respaldo">
        <?= csrfField() ?>
        <button type="submit">Generar respaldo</button>
    </form>

    <?php if (empty($respaldos)): ?>
        <p>No se han generado respaldos todavía.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Archivo</th>
                    <th>Generado por</th>
                    <th>Tamaño</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($respaldos as $respaldo): ?>
                    <tr>
                        <td><?= $mostrar(formatDateTimeSeconds($respaldo['fecha_generacion'])) ?></td>
                        <td><?= $mostrar($respaldo['nombre_archivo']) ?></td>
                        <td><?= $mostrarGeneradoPor($respaldo) ?></td>
                        <td><?= $mostrarTamano($respaldo) ?></td>
                        <td><?= $mostrarEstado($respaldo['estado_respaldo']) ?></td>
                        <td>
                            <a href="index.php?modulo=respaldos&accion=descargar&id=<?= (int) $respaldo['id_respaldo'] ?>">Descargar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>

    <script>
        document.getElementById('form-generar-respaldo').addEventListener('submit', function (evento) {
            if (!confirm('¿Desea generar un nuevo respaldo de la base de datos?')) {
                evento.preventDefault();
            }
        });
    </script>
</body>
</html>
