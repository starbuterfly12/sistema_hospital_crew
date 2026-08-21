<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación física</title>
</head>
<body>
    <h1>Verificación física</h1>

    <?php if (tieneRol(['Administrador', 'Operativo'])): ?>
        <p>
            <a href="index.php?modulo=verificaciones&accion=crear">Nueva verificación</a>
        </p>
    <?php endif; ?>

    <?php
        $verificaciones = $verificaciones ?? [];
        $responsables = $responsables ?? [];
        $ubicaciones = $ubicaciones ?? [];
        $filtros = $filtros ?? [];
    ?>

    <?php if (!empty($filtros['id_bien'])): ?>
        <p>
            Mostrando historial del bien seleccionado.
            <a href="index.php?modulo=verificaciones">Ver todas las verificaciones</a>
        </p>
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="modulo" value="verificaciones">
        <?php if (!empty($filtros['id_bien'])): ?>
            <input type="hidden" name="id_bien" value="<?= (int) $filtros['id_bien'] ?>">
        <?php endif; ?>

        <label for="busqueda">Buscar (código interno / SICOIN / descripción)</label>
        <input type="text" id="busqueda" name="busqueda" value="<?= htmlspecialchars($filtros['busqueda'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="id_responsable">Responsable registrado</label>
        <select id="id_responsable" name="id_responsable">
            <option value="">Todos</option>
            <?php foreach ($responsables as $responsable): ?>
                <option value="<?= (int) $responsable['id_responsable'] ?>" <?= ((int) ($filtros['id_responsable_registrado'] ?? 0) === (int) $responsable['id_responsable']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="id_ubicacion">Ubicación registrada</label>
        <select id="id_ubicacion" name="id_ubicacion">
            <option value="">Todas</option>
            <?php foreach ($ubicaciones as $ubicacion): ?>
                <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= ((int) ($filtros['id_ubicacion_registrada'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ubicacion['nombre_ubicacion'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="localizado">Localizado</label>
        <select id="localizado" name="localizado">
            <option value="">Todos</option>
            <option value="1" <?= (($filtros['localizado'] ?? '') === '1') ? 'selected' : '' ?>>Sí</option>
            <option value="0" <?= (($filtros['localizado'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
        </select>

        <label for="con_diferencias">Resultado</label>
        <select id="con_diferencias" name="con_diferencias">
            <option value="">Todos</option>
            <option value="0" <?= (($filtros['con_diferencias'] ?? '') === '0') ? 'selected' : '' ?>>Sin diferencias</option>
            <option value="1" <?= (($filtros['con_diferencias'] ?? '') === '1') ? 'selected' : '' ?>>Con diferencias</option>
        </select>

        <button type="submit">Filtrar</button>
        <a href="index.php?modulo=verificaciones">Limpiar filtros</a>
    </form>

    <?php if (empty($verificaciones)): ?>
        <p>No hay verificaciones registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>No. de Bien</th>
                    <th>SICOIN</th>
                    <th>Descripción</th>
                    <th>Responsable registrado</th>
                    <th>Ubicación registrada</th>
                    <th>Localizado</th>
                    <th>Resultado</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($verificaciones as $verificacion): ?>
                    <tr>
                        <td><?= htmlspecialchars(formatDateTime($verificacion['fecha_hora'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($verificacion['codigo_interno'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($verificacion['codigo_sicoin'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($verificacion['descripcion'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($verificacion['responsable_registrado_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($verificacion['ubicacion_registrada_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ((int) $verificacion['bien_localizado'] === 1) ? 'Sí' : 'No' ?></td>
                        <td><?= ((int) $verificacion['tiene_diferencias'] === 1) ? 'Con diferencias' : 'Sin diferencias' ?></td>
                        <td>
                            <a href="index.php?modulo=verificaciones&accion=ver&id=<?= (int) $verificacion['id_verificacion'] ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php?modulo=movimientos">Volver a Movimientos</a></p>
</body>
</html>
