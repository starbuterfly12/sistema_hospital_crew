<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora</title>
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

        // Para el value="" de inputs EDITABLES: a diferencia de $mostrar() (pensado para texto de
        // solo lectura), esto NUNCA debe convertir vacío/null en "-" — si lo hiciera, un input vacío
        // quedaría con el literal "-" como valor, y al reenviar el formulario sin tocarlo el backend
        // recibiría "-" en vez de "", rompiendo la validación de fecha (isValidIsoDate('-') === false).
        $valorInput = static function ($value): string {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        $mostrarUsuario = static function (array $fila) use ($mostrar): string {
            if ($fila['id_usuario'] !== null) {
                if (($fila['nombre_completo'] ?? null) !== null && ($fila['usuario'] ?? null) !== null) {
                    return $mostrar($fila['nombre_completo']) . ' (' . $mostrar($fila['usuario']) . ')';
                }
                return 'Usuario #' . (int) $fila['id_usuario'];
            }

            if (!empty($fila['usuario_intentado'])) {
                return 'No autenticado (intento: ' . $mostrar($fila['usuario_intentado']) . ')';
            }

            return 'Sistema';
        };

        $mostrarResultado = static function (string $resultado): string {
            return $resultado === 'exitoso' ? 'Exitoso' : 'Fallido';
        };

        // Trunca de forma segura para UTF-8 (mb_substr respeta caracteres multibyte) y escapa
        // DESPUÉS de truncar, para no cortar a mitad de una entidad HTML.
        $truncar = static function (?string $texto, int $limite = 120): string {
            $texto = (string) ($texto ?? '');
            if (mb_strlen($texto, 'UTF-8') <= $limite) {
                return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
            }
            return htmlspecialchars(mb_substr($texto, 0, $limite, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '…';
        };

        $registros = $registros ?? [];
        $error = $error ?? null;
        $filtros = $filtros ?? [];
        $modulos = $modulos ?? [];
        $pagina = $pagina ?? 1;
        $totalPaginas = $totalPaginas ?? 1;
        $total = $total ?? 0;

        // Base para reconstruir los links de paginación conservando los filtros activos — nunca el
        // GET crudo (que ya trae modulo=bitacora, accion, y una posible 'pagina' vieja).
        $paramsBase = [
            'modulo' => 'bitacora',
            'fecha_desde' => $filtros['fecha_desde'] ?? '',
            'fecha_hasta' => $filtros['fecha_hasta'] ?? '',
            'buscar' => $filtros['buscar'] ?? '',
            'filtro_modulo' => $filtros['modulo'] ?? '',
            'resultado' => $filtros['resultado'] ?? '',
        ];

        $urlPagina = static function (int $numeroPagina) use ($paramsBase): string {
            $params = $paramsBase;
            $params['pagina'] = $numeroPagina;
            return 'index.php?' . http_build_query($params);
        };
    ?>

    <h1>Bitácora</h1>
    <p>Consulta de solo lectura. Registra la trazabilidad de acciones del sistema y los intentos de inicio de sesión.</p>

    <?php if ($error !== null): ?>
        <p><?= $mostrar($error) ?></p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="bitacora">

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
                    <label for="buscar">Buscar</label>
                    <input type="text" id="buscar" name="buscar" value="<?= $valorInput($filtros['buscar'] ?? '') ?>" placeholder="Usuario, acción, descripción...">
                </div>

                <div class="campo-filtro">
                    <label for="filtro_modulo">Módulo</label>
                    <select id="filtro_modulo" name="filtro_modulo">
                        <option value="">Todos los módulos</option>
                        <?php foreach ($modulos as $moduloOpcion): ?>
                            <option value="<?= $mostrar($moduloOpcion) ?>" <?= ($filtros['modulo'] ?? '') === $moduloOpcion ? 'selected' : '' ?>><?= $mostrar($moduloOpcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="resultado">Resultado</label>
                    <select id="resultado" name="resultado">
                        <option value="">Todos los resultados</option>
                        <option value="exitoso" <?= ($filtros['resultado'] ?? '') === 'exitoso' ? 'selected' : '' ?>>Exitoso</option>
                        <option value="fallido" <?= ($filtros['resultado'] ?? '') === 'fallido' ? 'selected' : '' ?>>Fallido</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <button type="submit">Filtrar</button>
                    <a href="index.php?modulo=bitacora">Limpiar filtros</a>
                </div>
            </div>
        </fieldset>
    </form>

    <p>Total de registros encontrados: <?= (int) $total ?></p>

    <?php if ($error === null && empty($registros)): ?>
        <p>No se encontraron registros para los filtros seleccionados.</p>
    <?php elseif ($error === null): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>Resultado</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?= $mostrar(formatDateTimeSeconds($registro['fecha_hora'])) ?></td>
                        <td><?= $mostrarUsuario($registro) ?></td>
                        <td><?= $mostrar($registro['accion']) ?></td>
                        <td><?= $mostrar($registro['modulo']) ?></td>
                        <td><?= $mostrarResultado($registro['resultado']) ?></td>
                        <td><?= $truncar($registro['descripcion']) ?></td>
                        <td>
                            <a href="index.php?modulo=bitacora&accion=ver&id=<?= (int) $registro['id_bitacora'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <?php if ($pagina > 1): ?>
                <a href="<?= htmlspecialchars($urlPagina($pagina - 1), ENT_QUOTES, 'UTF-8') ?>">« Anterior</a>
            <?php else: ?>
                « Anterior
            <?php endif; ?>

            &nbsp;Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?>&nbsp;

            <?php if ($pagina < $totalPaginas): ?>
                <a href="<?= htmlspecialchars($urlPagina($pagina + 1), ENT_QUOTES, 'UTF-8') ?>">Siguiente »</a>
            <?php else: ?>
                Siguiente »
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>

    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        inicializarSelectoresFecha(['fecha_desde', 'fecha_hasta']);
    </script>
</body>
</html>
