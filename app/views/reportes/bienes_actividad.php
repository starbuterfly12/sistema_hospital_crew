<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Bienes con actividad</title>
    <link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
    <style>
        .filtros-reporte { border: 1px solid #bbb; border-radius: 4px; padding: 10px 14px; margin-bottom: 12px; }
        .filtros-reporte legend { font-weight: bold; padding: 0 6px; }
        .filtros-campos { display: flex; flex-wrap: wrap; gap: 10px 20px; align-items: flex-end; }
        .campo-filtro { display: flex; flex-direction: column; gap: 2px; }
        .campo-filtro label { font-size: 0.9em; }
    </style>
</head>
<body>
    <?php
        $mostrar = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $filas = $filas ?? [];
        $filtros = $filtros ?? [];
        $error = $error ?? null;

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'bienesActividad';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);

        $paramsExportarPdf = $paramsExportar;
        $paramsExportarPdf['formato'] = 'pdf';
        $urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);
    ?>

    <h1>Bienes con actividad en un período</h1>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="bienesActividad">

        <fieldset class="filtros-reporte">
            <legend>Filtros</legend>
            <div class="filtros-campos">
                <div class="campo-filtro">
                    <label for="fecha_desde">Fecha desde</label>
                    <span><input type="text" id="fecha_desde" name="fecha_desde" value="<?= $mostrar($filtros['fecha_desde'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_desde">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="fecha_hasta">Fecha hasta</label>
                    <span><input type="text" id="fecha_hasta" name="fecha_hasta" value="<?= $mostrar($filtros['fecha_hasta'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_hasta">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="tipo">Tipo de actividad</label>
                    <select id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach (ReportesService::TIPOS_EVENTO as $tipoEvento): ?>
                            <option value="<?= $mostrar($tipoEvento) ?>" <?= ($filtros['tipo'] ?? '') === $tipoEvento ? 'selected' : '' ?>><?= $mostrar($tipoEvento) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <button type="submit">Filtrar</button>
                </div>
            </div>
        </fieldset>
    </form>

    <p><a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>">Exportar Excel</a> | <a href="<?= htmlspecialchars($urlExportarPdf, ENT_QUOTES, 'UTF-8') ?>">Exportar PDF</a></p>

    <?php if ($error === null && empty($filas)): ?>
        <p>No se encontraron bienes con actividad para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. de Bien</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Cantidad de eventos</th>
                    <th>Primer evento</th>
                    <th>Último evento</th>
                    <th>Tipos de actividad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar($fila['codigo_interno']) ?></td>
                        <td><?= $mostrar($fila['codigo_sicoin']) ?></td>
                        <td><?= $mostrar($fila['descripcion']) ?></td>
                        <td><?= (int) $fila['eventos'] ?></td>
                        <td><?= $mostrar(formatFechaSegunTipo($fila['primer_evento'], $fila['primer_evento_es_datetime'])) ?></td>
                        <td><?= $mostrar(formatFechaSegunTipo($fila['ultimo_evento'], $fila['ultimo_evento_es_datetime'])) ?></td>
                        <td><?= $mostrar($fila['tipos']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>Total de registros: <?= count($filas) ?></p>
    <?php endif; ?>

    <p><a href="index.php?modulo=reportes">Volver a Reportes</a></p>

    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        inicializarSelectoresFecha(['fecha_desde', 'fecha_hasta']);
    </script>
</body>
</html>
