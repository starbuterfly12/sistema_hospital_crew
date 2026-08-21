<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Resumen de movimientos</title>
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

        $resultado = $resultado ?? ['filas' => [], 'total_operaciones' => 0, 'total_bienes_unicos' => 0];
        $filtros = $filtros ?? [];
        $error = $error ?? null;

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'resumen';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);
    ?>

    <h1>Resumen de movimientos por período</h1>
    <p>"Operaciones" cuenta operaciones únicas (una Requisición con 5 bienes cuenta como 1 operación). "Bienes involucrados" cuenta bienes distintos dentro del tipo.</p>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="resumen">

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
                    <button type="submit">Filtrar</button>
                </div>
            </div>
        </fieldset>
    </form>

    <p><a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>">Exportar Excel</a></p>

    <?php if ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Operaciones</th>
                    <th>Bienes involucrados</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultado['filas'] as $fila): ?>
                    <tr>
                        <td><?= $mostrar($fila['tipo']) ?></td>
                        <td><?= (int) $fila['operaciones'] ?></td>
                        <td><?= (int) $fila['bienes_involucrados'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total operaciones</strong></td>
                    <td colspan="2"><?= (int) $resultado['total_operaciones'] ?></td>
                </tr>
                <tr>
                    <td><strong>Total bienes únicos involucrados</strong></td>
                    <td colspan="2"><?= (int) $resultado['total_bienes_unicos'] ?></td>
                </tr>
            </tfoot>
        </table>
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
