<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Préstamos pendientes o vencidos</title>
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
        $responsables = $responsables ?? [];

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'prestamos';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);

        $paramsExportarPdf = $paramsExportar;
        $paramsExportarPdf['formato'] = 'pdf';
        $urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);
    ?>

    <h1>Préstamos pendientes o vencidos</h1>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="prestamos">

        <fieldset class="filtros-reporte">
            <legend>Filtros</legend>
            <div class="filtros-campos">
                <div class="campo-filtro">
                    <label for="fecha_desde">Fecha préstamo desde</label>
                    <span><input type="text" id="fecha_desde" name="fecha_desde" value="<?= $mostrar($filtros['fecha_desde'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_desde">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="fecha_hasta">Fecha préstamo hasta</label>
                    <span><input type="text" id="fecha_hasta" name="fecha_hasta" value="<?= $mostrar($filtros['fecha_hasta'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_hasta">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="">Todos (activo + parcial)</option>
                        <option value="activo" <?= ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="parcial" <?= ($filtros['estado'] ?? '') === 'parcial' ? 'selected' : '' ?>>Parcial</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="id_responsable">Receptor</label>
                    <select id="id_responsable" name="id_responsable">
                        <option value="">Todos</option>
                        <?php foreach ($responsables as $responsable): ?>
                            <option value="<?= (int) $responsable['id_responsable'] ?>" <?= ((int) ($filtros['id_responsable_destino'] ?? 0) === (int) $responsable['id_responsable']) ? 'selected' : '' ?>><?= $mostrar($responsable['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="vencido">Vencido</label>
                    <select id="vencido" name="vencido">
                        <option value="">Todos</option>
                        <option value="1" <?= ($filtros['vencido'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= ($filtros['vencido'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
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
        <p>No existen préstamos pendientes o vencidos con los criterios seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. préstamo</th>
                    <th>Receptor</th>
                    <th>Fecha préstamo</th>
                    <th>Fecha prevista de devolución</th>
                    <th>Estado</th>
                    <th>Bienes pendientes</th>
                    <th>Vencido</th>
                    <th>Días vencidos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar($fila['numero_prestamo']) ?></td>
                        <td><?= $mostrar($fila['responsable_destino_mostrado']) ?></td>
                        <td><?= $mostrar(formatDate($fila['fecha_prestamo'])) ?></td>
                        <td><?= $mostrar(formatDate($fila['fecha_devolucion_estimada'])) ?></td>
                        <td><?= $mostrar(ucfirst($fila['estado_prestamo'])) ?></td>
                        <td><?= (int) $fila['bienes_pendientes'] ?></td>
                        <td><?= $fila['vencido'] ? 'Sí' : 'No' ?></td>
                        <td><?= (int) $fila['dias_vencido'] ?></td>
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
