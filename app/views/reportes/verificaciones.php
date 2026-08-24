<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: Verificaciones con diferencias</title>
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

        $mostrarSiNo = static function ($valor): string {
            if ($valor === null) {
                return 'No aplica';
            }
            return (int) $valor === 1 ? 'Sí' : 'No';
        };

        // Para el value="" de inputs EDITABLES (los filtros de fecha): a diferencia de $mostrar()
        // (pensado para texto de solo lectura), esto NUNCA debe convertir vacío/null en "-" — si lo
        // hiciera, un filtro de fecha vacío quedaría con el literal "-" como valor, y al reenviar el
        // formulario sin tocarlo el backend recibiría "-" en vez de "", rompiendo la validación de
        // fecha opcional.
        $valorInput = static function ($value): string {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        $filas = $filas ?? [];
        $filtros = $filtros ?? [];
        $error = $error ?? null;
        $ubicaciones = $ubicaciones ?? [];

        $paramsExportar = $_GET;
        $paramsExportar['modulo'] = 'reportes';
        $paramsExportar['accion'] = 'verificaciones';
        $paramsExportar['formato'] = 'excel';
        $urlExportar = 'index.php?' . http_build_query($paramsExportar);

        $paramsExportarPdf = $paramsExportar;
        $paramsExportarPdf['formato'] = 'pdf';
        $urlExportarPdf = 'index.php?' . http_build_query($paramsExportarPdf);
    ?>

    <h1>Verificaciones con diferencias</h1>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="reportes">
        <input type="hidden" name="accion" value="verificaciones">

        <fieldset class="filtros-reporte">
            <legend>Filtros</legend>
            <div class="filtros-campos">
                <div class="campo-filtro">
                    <label for="fecha_desde">Fecha desde</label>
                    <span><input type="text" id="fecha_desde" name="fecha_desde" value="<?= $valorInput($filtros['fecha_desde'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_desde">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="fecha_hasta">Fecha hasta</label>
                    <span><input type="text" id="fecha_hasta" name="fecha_hasta" value="<?= $valorInput($filtros['fecha_hasta'] ?? '') ?>"> <button type="button" data-flatpickr-target="fecha_hasta">📅</button></span>
                </div>

                <div class="campo-filtro">
                    <label for="resultado">Resultado</label>
                    <select id="resultado" name="resultado">
                        <option value="1" <?= ($filtros['resultado'] ?? '1') === '1' ? 'selected' : '' ?>>Con diferencias</option>
                        <option value="0" <?= ($filtros['resultado'] ?? '') === '0' ? 'selected' : '' ?>>Sin diferencias</option>
                        <option value="" <?= ($filtros['resultado'] ?? '') === '' ? 'selected' : '' ?>>Todas</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="localizado">Localizado</label>
                    <select id="localizado" name="localizado">
                        <option value="">Todos</option>
                        <option value="1" <?= ($filtros['localizado'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= ($filtros['localizado'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="id_ubicacion">Ubicación registrada</label>
                    <select id="id_ubicacion" name="id_ubicacion">
                        <option value="">Todas</option>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_registrada'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>><?= $mostrar($ubicacion['nombre_ubicacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <button type="submit">Filtrar</button>
                    <a href="index.php?modulo=reportes&accion=verificaciones">Limpiar filtros</a>
                </div>
            </div>
        </fieldset>
    </form>

    <p><a href="<?= htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8') ?>">Exportar Excel</a> | <a href="<?= htmlspecialchars($urlExportarPdf, ENT_QUOTES, 'UTF-8') ?>">Exportar PDF</a></p>

    <?php if ($error === null && empty($filas)): ?>
        <p>No se encontraron verificaciones para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha/hora</th>
                    <th>No. de Bien</th>
                    <th>Descripción</th>
                    <th>Responsable registrado</th>
                    <th>Ubicación registrada</th>
                    <th>Condición registrada</th>
                    <th>Localizado</th>
                    <th>Responsable correcto</th>
                    <th>Ubicación correcta</th>
                    <th>Condición observada</th>
                    <th>Resultado</th>
                    <th>Observaciones</th>
                    <th>Verificado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= $mostrar(formatDateTime($fila['fecha_hora'])) ?></td>
                        <td><?= $mostrar($fila['codigo_interno']) ?></td>
                        <td><?= $mostrar($fila['descripcion']) ?></td>
                        <td><?= $mostrar($fila['responsable_registrado']) ?></td>
                        <td><?= $mostrar($fila['ubicacion_registrada']) ?></td>
                        <td><?= $mostrar($fila['condicion_registrada']) ?></td>
                        <td><?= (int) $fila['bien_localizado'] === 1 ? 'Sí' : 'No' ?></td>
                        <td><?= $mostrarSiNo($fila['responsable_correcto']) ?></td>
                        <td><?= $mostrarSiNo($fila['ubicacion_correcta']) ?></td>
                        <td><?= $fila['condicion_observada'] !== null ? $mostrar($fila['condicion_observada']) : 'No aplica' ?></td>
                        <td><?= (int) $fila['tiene_diferencias'] === 1 ? 'Con diferencias' : 'Sin diferencias' ?></td>
                        <td><?= $mostrar($fila['observaciones']) ?></td>
                        <td><?= $mostrar($fila['usuario_verifica']) ?></td>
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
