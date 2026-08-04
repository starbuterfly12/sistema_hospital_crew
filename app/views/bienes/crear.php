<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar bien</title>
</head>
<body>
    <?php
        $datos = $datos ?? [];
        $error = $error ?? null;
        $formasIngreso = $formasIngreso ?? [];
        $categorias = $categorias ?? [];
        $estados = $estados ?? [];
        $responsables = $responsables ?? [];
        $ubicaciones = $ubicaciones ?? [];
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Registrar bien</h1>

    <form method="POST" id="form-crear-bien">
        <div>
            <label for="id_forma_ingreso">Forma de ingreso</label>
            <select id="id_forma_ingreso" name="id_forma_ingreso" required>
                <option value="">Seleccione</option>
                <?php foreach ($formasIngreso as $forma): ?>
                    <option
                        value="<?= (int) $forma['id_forma_ingreso'] ?>"
                        data-nombre="<?= htmlspecialchars($forma['nombre_forma'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= ((int) ($datos['id_forma_ingreso'] ?? 0) === (int) $forma['id_forma_ingreso']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($forma['nombre_forma'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="codigo_interno">Código interno</label>
            <input
                type="text"
                id="codigo_interno"
                name="codigo_interno"
                value="<?= htmlspecialchars($datos['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="codigo_sicoin">Código SICOIN</label>
            <input type="text" id="codigo_sicoin" name="codigo_sicoin" value="<?= htmlspecialchars($datos['codigo_sicoin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
            <label for="descripcion">Descripción</label>
            <input type="text" id="descripcion" name="descripcion" value="<?= htmlspecialchars($datos['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($datos['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($datos['modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
            <label for="serie">Serie</label>
            <input type="text" id="serie" name="serie" value="<?= htmlspecialchars($datos['serie'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Seleccione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id_categoria'] ?>"<?= ((int) ($datos['id_categoria'] ?? 0) === (int) $categoria['id_categoria']) ? ' selected' : '' ?>><?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_estado_bien">Estado</label>
            <select id="id_estado_bien" name="id_estado_bien" required>
                <option value="">Seleccione</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id_estado_bien'] ?>"<?= ((int) ($datos['id_estado_bien'] ?? 0) === (int) $estado['id_estado_bien']) ? ' selected' : '' ?>><?= htmlspecialchars($estado['nombre_estado'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="condicion_bien">Condición del bien</label>
            <select id="condicion_bien" name="condicion_bien" required>
                <option value="">Seleccione</option>
                <option value="Bueno"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Bueno') ? ' selected' : '' ?>>Bueno</option>
                <option value="Regular"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Regular') ? ' selected' : '' ?>>Regular</option>
                <option value="Malo"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Malo') ? ' selected' : '' ?>>Malo</option>
            </select>
        </div>

        <div>
            <label for="fecha_ingreso">Fecha de ingreso</label>
            <input type="date" id="fecha_ingreso" name="fecha_ingreso" value="<?= htmlspecialchars($datos['fecha_ingreso'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div id="grupo-costo">
            <label for="costo">Costo</label>
            <input type="number" id="costo" name="costo" step="0.01" min="0" value="<?= htmlspecialchars($datos['costo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div id="grupo-valor-estimado">
            <label for="valor_estimado">Valor estimado</label>
            <input type="number" id="valor_estimado" name="valor_estimado" step="0.01" min="0" value="<?= htmlspecialchars($datos['valor_estimado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
            <label for="id_responsable_actual">Responsable actual</label>
            <select id="id_responsable_actual" name="id_responsable_actual">
                <option value="">Seleccione</option>
                <?php foreach ($responsables as $responsable): ?>
                    <option value="<?= (int) $responsable['id_responsable'] ?>"<?= ((int) ($datos['id_responsable_actual'] ?? 0) === (int) $responsable['id_responsable']) ? ' selected' : '' ?>><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_ubicacion_actual">Ubicación actual</label>
            <select id="id_ubicacion_actual" name="id_ubicacion_actual" required>
                <option value="">Seleccione</option>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?= (int) $ubicacion['id_ubicacion'] ?>"<?= ((int) ($datos['id_ubicacion_actual'] ?? 0) === (int) $ubicacion['id_ubicacion']) ? ' selected' : '' ?>><?= htmlspecialchars($ubicacion['nombre_ubicacion'] . ' - ' . $ubicacion['tipo_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($datos['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <fieldset id="bloque-compra">
            <legend>Datos de compra</legend>

            <div>
                <label for="proveedor">Proveedor</label>
                <input type="text" id="proveedor" name="proveedor" value="<?= htmlspecialchars($datos['proveedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="numero_factura">Número de factura</label>
                <input type="text" id="numero_factura" name="numero_factura" value="<?= htmlspecialchars($datos['numero_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="serie_factura">Serie de factura</label>
                <input type="text" id="serie_factura" name="serie_factura" value="<?= htmlspecialchars($datos['serie_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="fecha_factura">Fecha de factura</label>
                <input type="date" id="fecha_factura" name="fecha_factura" value="<?= htmlspecialchars($datos['fecha_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="numero_liquidacion">Número de liquidación</label>
                <input type="text" id="numero_liquidacion" name="numero_liquidacion" value="<?= htmlspecialchars($datos['numero_liquidacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="forma_compra">Forma de compra</label>
                <input type="text" id="forma_compra" name="forma_compra" value="<?= htmlspecialchars($datos['forma_compra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="tiene_garantia">¿Tiene garantía?</label>
                <?php $tieneGarantiaValor = (string) ($datos['tiene_garantia'] ?? '0'); ?>
                <select id="tiene_garantia" name="tiene_garantia">
                    <option value="0"<?= $tieneGarantiaValor === '0' ? ' selected' : '' ?>>No</option>
                    <option value="1"<?= $tieneGarantiaValor === '1' ? ' selected' : '' ?>>Sí</option>
                </select>
            </div>

            <div id="grupo-tiempo-garantia">
                <label for="tiempo_garantia">Tiempo de garantía</label>
                <input type="text" id="tiempo_garantia" name="tiempo_garantia" value="<?= htmlspecialchars($datos['tiempo_garantia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="documento_respaldo_compra">Documento de respaldo</label>
                <input type="text" id="documento_respaldo_compra" name="documento_respaldo" value="<?= htmlspecialchars($datos['documento_respaldo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </fieldset>

        <fieldset id="bloque-donacion">
            <legend>Datos de donación</legend>

            <div>
                <label for="procedencia_donacion">Procedencia</label>
                <input type="text" id="procedencia_donacion" name="procedencia" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="entidad_donante">Entidad donante</label>
                <input type="text" id="entidad_donante" name="entidad_donante" value="<?= htmlspecialchars($datos['entidad_donante'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="numero_acta_donacion">Número de acta</label>
                <input type="text" id="numero_acta_donacion" name="numero_acta" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="fecha_acta_donacion">Fecha de acta</label>
                <input type="date" id="fecha_acta_donacion" name="fecha_acta" value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="documento_respaldo_donacion">Documento de respaldo</label>
                <input type="text" id="documento_respaldo_donacion" name="documento_respaldo" value="<?= htmlspecialchars($datos['documento_respaldo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </fieldset>

        <fieldset id="bloque-traslado">
            <legend>Datos de traslado</legend>

            <div>
                <label for="procedencia_traslado">Procedencia</label>
                <input type="text" id="procedencia_traslado" name="procedencia" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="unidad_ejecutora_origen">Unidad ejecutora de origen</label>
                <input type="text" id="unidad_ejecutora_origen" name="unidad_ejecutora_origen" value="<?= htmlspecialchars($datos['unidad_ejecutora_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="codigo_unidad_origen">Código de unidad de origen</label>
                <input type="text" id="codigo_unidad_origen" name="codigo_unidad_origen" value="<?= htmlspecialchars($datos['codigo_unidad_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="numero_acta_traslado">Número de acta</label>
                <input type="text" id="numero_acta_traslado" name="numero_acta" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="fecha_acta_traslado">Fecha de acta</label>
                <input type="date" id="fecha_acta_traslado" name="fecha_acta" value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="documento_respaldo_traslado">Documento de respaldo</label>
                <input type="text" id="documento_respaldo_traslado" name="documento_respaldo" value="<?= htmlspecialchars($datos['documento_respaldo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </fieldset>

        <button type="submit">Guardar</button>
    </form>

    <p><a href="index.php?modulo=bienes">Volver</a></p>

    <script>
        (function () {
            var patronDiacriticos = new RegExp(
                '[' + String.fromCharCode(768) + '-' + String.fromCharCode(879) + ']',
                'g'
            );

            function normalizarTexto(texto) {
                return (texto || '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(patronDiacriticos, '')
                    .trim();
            }

            function aplicarRequerido(idCampo, esRequerido) {
                var campo = document.getElementById(idCampo);
                if (!campo) {
                    return;
                }
                if (campo.disabled || !esRequerido) {
                    campo.removeAttribute('required');
                } else {
                    campo.setAttribute('required', 'required');
                }
            }

            function configurarBloque(idBloque, activo) {
                var bloque = document.getElementById(idBloque);
                bloque.style.display = activo ? '' : 'none';

                var campos = bloque.querySelectorAll('input, select, textarea');
                campos.forEach(function (campo) {
                    campo.disabled = !activo;
                    if (!activo) {
                        campo.removeAttribute('required');
                    }
                });
            }

            function configurarGrupo(idGrupo, idCampo, mostrar) {
                var grupo = document.getElementById(idGrupo);
                var campo = document.getElementById(idCampo);

                grupo.style.display = mostrar ? '' : 'none';
                campo.disabled = !mostrar;

                if (!mostrar) {
                    campo.removeAttribute('required');
                }
            }

            function actualizarGarantia(esCompra) {
                var selectGarantia = document.getElementById('tiene_garantia');
                var campoTiempo = document.getElementById('tiempo_garantia');
                var grupoTiempo = document.getElementById('grupo-tiempo-garantia');

                var tieneGarantiaActiva = esCompra && selectGarantia.value === '1';

                grupoTiempo.style.display = tieneGarantiaActiva ? '' : 'none';
                campoTiempo.disabled = !tieneGarantiaActiva;

                aplicarRequerido('tiempo_garantia', tieneGarantiaActiva);
            }

            function actualizarFormulario() {
                var selectForma = document.getElementById('id_forma_ingreso');
                var opcionSeleccionada = selectForma.options[selectForma.selectedIndex];
                var nombreForma = normalizarTexto(
                    opcionSeleccionada ? opcionSeleccionada.getAttribute('data-nombre') : ''
                );

                var esCompra = nombreForma === 'compra';
                var esDonacion = nombreForma === 'donacion';
                var esTraslado = nombreForma === 'traslado';

                configurarBloque('bloque-compra', esCompra);
                configurarBloque('bloque-donacion', esDonacion);
                configurarBloque('bloque-traslado', esTraslado);

                configurarGrupo('grupo-costo', 'costo', esCompra || esTraslado);
                configurarGrupo('grupo-valor-estimado', 'valor_estimado', esDonacion);

                aplicarRequerido('proveedor', esCompra);
                aplicarRequerido('numero_factura', esCompra);
                aplicarRequerido('fecha_factura', esCompra);
                aplicarRequerido('costo', esCompra || esTraslado);

                aplicarRequerido('procedencia_donacion', esDonacion);
                aplicarRequerido('entidad_donante', esDonacion);
                aplicarRequerido('numero_acta_donacion', esDonacion);
                aplicarRequerido('valor_estimado', esDonacion);

                aplicarRequerido('procedencia_traslado', esTraslado);
                aplicarRequerido('unidad_ejecutora_origen', esTraslado);
                aplicarRequerido('codigo_unidad_origen', esTraslado);
                aplicarRequerido('numero_acta_traslado', esTraslado);

                actualizarGarantia(esCompra);
            }

            document.getElementById('id_forma_ingreso').addEventListener('change', actualizarFormulario);
            document.getElementById('tiene_garantia').addEventListener('change', actualizarFormulario);
            document.addEventListener('DOMContentLoaded', actualizarFormulario);

            actualizarFormulario();
        })();
    </script>
</body>
</html>
