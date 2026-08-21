<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Bienes sin responsable/asignación</title>
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
        $categorias = $categorias ?? [];
        $ubicaciones = $ubicaciones ?? [];

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'bienesSinAsignacion';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);
    ?>

    <h1>Bienes sin responsable/asignación</h1>
    <p>Muestra únicamente bienes en estado Activo con alguna anomalía operativa real. Un bien en Baja sin responsable NO es una anomalía y nunca aparece aquí.</p>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="bienesSinAsignacion">

        <fieldset class="filtros-reporte">
            <legend>Filtros</legend>
            <div class="filtros-campos">
                <div class="campo-filtro">
                    <label for="id_categoria">Categoría</label>
                    <select id="id_categoria" name="id_categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id_categoria'] ?>" <?= ((int) ($filtros['id_categoria'] ?? 0) === (int) $categoria['id_categoria']) ? 'selected' : '' ?>><?= $mostrar($categoria['nombre_categoria']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="id_ubicacion">Ubicación</label>
                    <select id="id_ubicacion" name="id_ubicacion">
                        <option value="">Todas</option>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>><?= $mostrar($ubicacion['nombre_ubicacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="fecha_ingreso_desde">Fecha de ingreso desde</label>
                    <span><input type="text" id="fecha_ingreso_desde" name="fecha_ingreso_desde" value="<?= $mostrar($filtros['fecha_ingreso_desde'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_ingreso_desde">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="fecha_ingreso_hasta">Fecha de ingreso hasta</label>
                    <span><input type="text" id="fecha_ingreso_hasta" name="fecha_ingreso_hasta" value="<?= $mostrar($filtros['fecha_ingreso_hasta'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_ingreso_hasta">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <button type="submit">Filtrar</button>
                </div>
            </div>
        </fieldset>
    </form>

    <p><a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>">Exportar Excel</a></p>

    <?php if ($error === null && empty($filas)): ?>
        <p>No se encontraron bienes con anomalías para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>No. de Bien</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Categoría</th>
                    <th>Condición</th>
                    <th>Ubicación actual</th>
                    <th>Fecha de ingreso</th>
                    <th>Tipo de anomalía</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar($fila['codigo_interno']) ?></td>
                        <td><?= $mostrar($fila['codigo_sicoin']) ?></td>
                        <td><?= $mostrar($fila['descripcion']) ?></td>
                        <td><?= $mostrar($fila['nombre_estado']) ?></td>
                        <td><?= $mostrar($fila['nombre_categoria']) ?></td>
                        <td><?= $mostrar($fila['condicion_bien']) ?></td>
                        <td><?= $mostrar($fila['ubicacion_actual']) ?></td>
                        <td><?= $mostrar(formatDate($fila['fecha_ingreso'])) ?></td>
                        <td><?= $mostrar($fila['tipo_anomalia']) ?></td>
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
        inicializarSelectoresFecha(['fecha_ingreso_desde', 'fecha_ingreso_hasta']);
    </script>
</body>
</html>
