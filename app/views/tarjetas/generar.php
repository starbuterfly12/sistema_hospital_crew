<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar tarjeta de responsabilidad</title>
</head>
<body>
    <?php
        $asignaciones = $asignaciones ?? [];
        $error = $error ?? null;
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Generar tarjeta de responsabilidad</h1>

    <?php if (empty($asignaciones)): ?>
        <p>No hay asignaciones en estado Asignada disponibles para generar una tarjeta.</p>
    <?php else: ?>
        <form method="POST">
            <?= csrfField() ?>

            <div>
                <label for="id_asignacion">Asignación *</label>
                <select id="id_asignacion" name="id_asignacion" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($asignaciones as $asignacion): ?>
                        <?php
                            $ubicacionTexto = $asignacion['nombre_ubicacion'] ?? '-';

                            if (!empty($asignacion['tipo_ubicacion'])) {
                                $ubicacionTexto .= ' - ' . $asignacion['tipo_ubicacion'];
                            }

                            $etiqueta = ($asignacion['numero_asignacion'] ?? '-')
                                . ' - ' . ($asignacion['responsable_nombre'] ?? '-')
                                . ' - ' . $ubicacionTexto;
                        ?>
                        <option value="<?= (int) $asignacion['id_asignacion'] ?>"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit">Generar tarjeta</button>
                <a href="index.php?modulo=tarjetas">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>

    <p><a href="index.php?modulo=tarjetas">Volver al listado</a></p>
</body>
</html>
