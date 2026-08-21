<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Ingresos de bienes</title>
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
        $formasIngreso = $formasIngreso ?? [];
        $categorias = $categorias ?? [];

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'ingresos';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);
    ?>

    <h1>Ingresos de bienes por período</h1>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="ingresos">

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
                    <label for="id_forma_ingreso">Forma de ingreso</label>
                    <select id="id_forma_ingreso" name="id_forma_ingreso">
                        <option value="">Todas</option>
                        <?php foreach ($formasIngreso as $forma): ?>
                            <option value="<?= (int) $forma['id_forma_ingreso'] ?>" <?= ((int) ($filtros['id_forma_ingreso'] ?? 0) === (int) $forma['id_forma_ingreso']) ? 'selected' : '' ?>><?= $mostrar($forma['nombre_forma']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

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
                    <label for="procedencia">Procedencia / Proveedor</label>
                    <input type="text" id="procedencia" name="procedencia" value="<?= $mostrar($filtros['procedencia'] ?? '') ?>">
                </div>

                <div class="campo-filtro">
                    <button type="submit">Filtrar</button>
                </div>
            </div>
        </fieldset>
    </form>

    <p><a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>">Exportar Excel</a></p>

    <?php if ($error === null && empty($filas)): ?>
        <p>No se encontraron ingresos de bienes para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha ingreso</th>
                    <th>No. de Bien</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Forma de ingreso</th>
                    <th>Categoría</th>
                    <th>Procedencia / Proveedor</th>
                    <th>Valor</th>
                    <th>Estado actual</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar(formatDate($fila['fecha_ingreso'])) ?></td>
                        <td><?= $mostrar($fila['codigo_interno']) ?></td>
                        <td><?= $mostrar($fila['codigo_sicoin']) ?></td>
                        <td><?= $mostrar($fila['descripcion']) ?></td>
                        <td><?= $mostrar($fila['nombre_forma']) ?></td>
                        <td><?= $mostrar($fila['nombre_categoria']) ?></td>
                        <td><?= $mostrar($fila['procedencia']) ?></td>
                        <td><?= $fila['valor'] !== null ? 'Q ' . number_format((float) $fila['valor'], 2) : '-' ?></td>
                        <td><?= $mostrar($fila['nombre_estado']) ?></td>
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
