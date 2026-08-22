<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Bajas por período</title>
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
        $ubicaciones = $ubicaciones ?? [];
        $tiposBaja = $tiposBaja ?? [];

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'bajas';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);

        $paramsExportarPdf = $paramsExportar;
        $paramsExportarPdf['formato'] = 'pdf';
        $urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);
    ?>

    <h1>Bajas por período</h1>
    <p>Por defecto muestra bajas <strong>Finalizadas</strong> filtradas por fecha de baja real. Al elegir otro estado, el rango de fechas se aplica sobre la fecha de preparación (trámite), porque la fecha de baja aún no existe en esos casos.</p>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="bajas">

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
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="finalizada" <?= ($filtros['estado'] ?? 'finalizada') === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                        <option value="pendiente" <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="autorizada" <?= ($filtros['estado'] ?? '') === 'autorizada' ? 'selected' : '' ?>>Autorizada</option>
                        <option value="rechazada" <?= ($filtros['estado'] ?? '') === 'rechazada' ? 'selected' : '' ?>>Rechazada</option>
                        <option value="" <?= ($filtros['estado'] ?? '') === '' ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="id_tipo_baja">Tipo de baja</label>
                    <select id="id_tipo_baja" name="id_tipo_baja">
                        <option value="">Todos</option>
                        <?php foreach ($tiposBaja as $tipoBaja): ?>
                            <option value="<?= (int) $tipoBaja['id_tipo_baja'] ?>" <?= ((int) ($filtros['id_tipo_baja'] ?? 0) === (int) $tipoBaja['id_tipo_baja']) ? 'selected' : '' ?>><?= $mostrar($tipoBaja['nombre_tipo_baja']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="id_ubicacion_anterior">Servicio/ubicación anterior</label>
                    <select id="id_ubicacion_anterior" name="id_ubicacion_anterior">
                        <option value="">Todas</option>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_anterior'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>><?= $mostrar($ubicacion['nombre_ubicacion']) ?></option>
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
        <p>No se encontraron bajas para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. Baja</th>
                    <th>Fecha preparación</th>
                    <th>Fecha autorización</th>
                    <th>Fecha baja</th>
                    <th>Estado</th>
                    <th>No. de Bien</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Responsable anterior</th>
                    <th>Ubicación anterior</th>
                    <th>Tipo de baja</th>
                    <th>Valor</th>
                    <th>Bodega destino</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar($fila['numero_baja']) ?></td>
                        <td><?= $mostrar(formatDateTime($fila['fecha_preparacion'])) ?></td>
                        <td><?= $mostrar(formatDateTime($fila['fecha_autorizacion'])) ?></td>
                        <td><?= $mostrar(formatDate($fila['fecha_baja'])) ?></td>
                        <td><?= $mostrar(ucfirst($fila['estado_baja'])) ?></td>
                        <td><?= $mostrar($fila['codigo_interno_mostrado']) ?></td>
                        <td><?= $mostrar($fila['codigo_sicoin_mostrado']) ?></td>
                        <td><?= $mostrar($fila['descripcion_mostrada']) ?></td>
                        <td><?= $mostrar($fila['responsable_anterior']) ?></td>
                        <td><?= $mostrar($fila['ubicacion_anterior']) ?></td>
                        <td><?= $mostrar($fila['nombre_tipo_baja']) ?></td>
                        <td>Q <?= number_format((float) $fila['valor_mostrado'], 2) ?></td>
                        <td><?= $mostrar($fila['bodega_destino']) ?></td>
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
