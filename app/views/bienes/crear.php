<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar bien</title>
</head>
<body>

    <h1>Registrar bien</h1>

    <form method="POST">
        <div>
            <label for="id_forma_ingreso">Forma de ingreso</label>
            <select id="id_forma_ingreso" name="id_forma_ingreso" required>
                <option value="">Seleccione</option>
                <?php foreach ($formasIngreso as $forma): ?>
                    <option value="<?= (int) $forma['id_forma_ingreso'] ?>"><?= htmlspecialchars($forma['nombre_forma'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="codigo_interno">Código interno</label>
            <input
                type="text"
                id="codigo_interno"
                name="codigo_interno"
                required
            >
        </div>

        <div>
            <label for="codigo_sicoin">Código SICOIN</label>
            <input type="text" id="codigo_sicoin" name="codigo_sicoin">
        </div>

        <div>
            <label for="descripcion">Descripción</label>
            <input type="text" id="descripcion" name="descripcion" required>
        </div>

        <div>
            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca">
        </div>

        <div>
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo">
        </div>

        <div>
            <label for="serie">Serie</label>
            <input type="text" id="serie" name="serie">
        </div>

        <div>
            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Seleccione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id_categoria'] ?>"><?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_estado_bien">Estado</label>
            <select id="id_estado_bien" name="id_estado_bien" required>
                <option value="">Seleccione</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id_estado_bien'] ?>"><?= htmlspecialchars($estado['nombre_estado'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="condicion_bien">Condición del bien</label>
            <select id="condicion_bien" name="condicion_bien" required>
                <option value="">Seleccione</option>
                <option value="Bueno">Bueno</option>
                <option value="Regular">Regular</option>
                <option value="Malo">Malo</option>
            </select>
        </div>

        <div>
            <label for="fecha_ingreso">Fecha de ingreso</label>
            <input type="date" id="fecha_ingreso" name="fecha_ingreso" required>
        </div>

        <div>
            <label for="costo">Costo</label>
            <input type="number" id="costo" name="costo" step="0.01" min="0">
        </div>

        <div>
            <label for="valor_estimado">Valor estimado</label>
            <input type="number" id="valor_estimado" name="valor_estimado" step="0.01" min="0">
        </div>

        <div>
            <label for="id_responsable_actual">Responsable actual</label>
            <select id="id_responsable_actual" name="id_responsable_actual">
                <option value="">Seleccione</option>
                <?php foreach ($responsables as $responsable): ?>
                    <option value="<?= (int) $responsable['id_responsable'] ?>"><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_ubicacion_actual">Ubicación actual</label>
            <select id="id_ubicacion_actual" name="id_ubicacion_actual" required>
                <option value="">Seleccione</option>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?= (int) $ubicacion['id_ubicacion'] ?>"><?= htmlspecialchars($ubicacion['nombre_ubicacion'] . ' - ' . $ubicacion['tipo_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"></textarea>
        </div>

        <button type="submit">Guardar</button>
    </form>

    <p><a href="index.php?modulo=bienes">Volver</a></p>
</body>
</html>
