<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BienesController::crear()).
// Todos los id/name se mantienen exactamente iguales al HTML anterior (no migrado) — el JS de más
// abajo depende de ellos por getElementById()/name y no fue tocado. Solo cambió el marcado visual
// (tarjeta + secciones + grid) y las clases CSS aplicadas a cada campo.
$datos = $datos ?? [];
$error = $error ?? null;
$formasIngreso = $formasIngreso ?? [];
$categorias = $categorias ?? [];
$estados = $estados ?? [];
$bodegaConfigurada = (bool) ($bodegaConfigurada ?? true);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar bien</h1>
            <p class="page-subtitle">Ingrese la información del bien institucional.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bienes" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$bodegaConfigurada): ?>
    <div class="alert alert-error">
        <strong>No se puede registrar bienes todavía:</strong> debe configurarse primero la Bodega
        de Almacén (una ubicación activa de tipo "Bodega") y su responsable activo asociado.
    </div>
<?php endif; ?>

<div class="form-layout-con-lateral">
<form method="POST" id="form-crear-bien" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="codigo_interno">Código interno <span class="required-mark">*</span></label>
                <input
                    type="text"
                    id="codigo_interno"
                    name="codigo_interno"
                    class="form-control"
                    value="<?= htmlspecialchars($datos['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="codigo_sicoin">Código SICOIN</label>
                <input type="text" id="codigo_sicoin" name="codigo_sicoin" class="form-control" value="<?= htmlspecialchars($datos['codigo_sicoin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="descripcion">Descripción <span class="required-mark">*</span></label>
                <input type="text" id="descripcion" name="descripcion" class="form-control" value="<?= htmlspecialchars($datos['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="marca">Marca</label>
                <input type="text" id="marca" name="marca" class="form-control" value="<?= htmlspecialchars($datos['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="modelo">Modelo</label>
                <input type="text" id="modelo" name="modelo" class="form-control" value="<?= htmlspecialchars($datos['modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="serie">Serie</label>
                <input type="text" id="serie" name="serie" class="form-control" value="<?= htmlspecialchars($datos['serie'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="id_categoria">Categoría <span class="required-mark">*</span></label>
                <div class="input-with-action">
                    <select id="id_categoria" name="id_categoria" class="form-control" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id_categoria'] ?>"<?= ((int) ($datos['id_categoria'] ?? 0) === (int) $categoria['id_categoria']) ? ' selected' : '' ?>><?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btn-nueva-categoria" class="btn btn-secondary">Nueva categoría</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="id_estado_bien">Estado <span class="required-mark">*</span></label>
                <select id="id_estado_bien" name="id_estado_bien" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= (int) $estado['id_estado_bien'] ?>"<?= ((int) ($datos['id_estado_bien'] ?? 0) === (int) $estado['id_estado_bien']) ? ' selected' : '' ?>><?= htmlspecialchars($estado['nombre_estado'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="condicion_bien">Condición del bien <span class="required-mark">*</span></label>
                <select id="condicion_bien" name="condicion_bien" class="form-control" required>
                    <option value="">Seleccione</option>
                    <option value="Bueno"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Bueno') ? ' selected' : '' ?>>Bueno</option>
                    <option value="Regular"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Regular') ? ' selected' : '' ?>>Regular</option>
                    <option value="Malo"<?= (isset($datos['condicion_bien']) && $datos['condicion_bien'] === 'Malo') ? ' selected' : '' ?>>Malo</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_ingreso">Fecha de ingreso <span class="required-mark">*</span></label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_ingreso"
                        name="fecha_ingreso"
                        class="form-control"
                        value="<?= htmlspecialchars($datos['fecha_ingreso'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                        required
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_ingreso" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <div id="grupo-costo" class="form-group">
                <label class="form-label" for="costo">Costo <span class="required-mark">*</span></label>
                <div class="input-money">
                    <span class="input-money-prefix" aria-hidden="true">Q</span>
                    <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($datos['costo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div id="grupo-valor-estimado" class="form-group">
                <label class="form-label" for="valor_estimado">Valor estimado <span class="required-mark">*</span></label>
                <div class="input-money">
                    <span class="input-money-prefix" aria-hidden="true">Q</span>
                    <input type="number" id="valor_estimado" name="valor_estimado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($datos['valor_estimado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control"><?= htmlspecialchars($datos['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="fotografia_bien">Fotografía del bien</label>
                <div class="file-picker">
                    <input type="file" id="fotografia_bien" name="fotografia_bien" class="file-input visually-hidden" accept=".jpg,.jpeg,.png,.webp">
                    <label for="fotografia_bien" class="file-picker-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        Seleccionar imagen
                    </label>
                    <span class="file-picker-name">Ningún archivo seleccionado</span>
                </div>
                <div id="fotografia-bien-preview" class="foto-bien-preview" hidden>
                    <img id="fotografia-bien-preview-img" alt="Vista previa de la fotografía seleccionada">
                    <button type="button" id="fotografia-bien-quitar" class="btn btn-secondary">Quitar imagen</button>
                </div>
                <p class="form-hint">Opcional. JPG, PNG o WEBP, hasta 5 MB.</p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Forma de ingreso</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="id_forma_ingreso">Forma de ingreso <span class="required-mark">*</span></label>
                <select id="id_forma_ingreso" name="id_forma_ingreso" class="form-control" required>
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
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Datos del ingreso</h2>

        <div id="bloque-compra" class="form-grid">
            <p class="form-section-subtitulo">Datos de compra</p>

            <div class="form-group">
                <label class="form-label" for="proveedor">Proveedor <span class="required-mark">*</span></label>
                <input type="text" id="proveedor" name="proveedor" class="form-control" value="<?= htmlspecialchars($datos['proveedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="numero_factura">Número de factura <span class="required-mark">*</span></label>
                <input type="text" id="numero_factura" name="numero_factura" class="form-control" value="<?= htmlspecialchars($datos['numero_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="serie_factura">Serie de factura</label>
                <input type="text" id="serie_factura" name="serie_factura" class="form-control" value="<?= htmlspecialchars($datos['serie_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_factura">Fecha de factura <span class="required-mark">*</span></label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_factura"
                        name="fecha_factura"
                        class="form-control"
                        value="<?= htmlspecialchars($datos['fecha_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_factura" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="numero_liquidacion">Número de liquidación</label>
                <input type="text" id="numero_liquidacion" name="numero_liquidacion" class="form-control" value="<?= htmlspecialchars($datos['numero_liquidacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="id_forma_pago">Forma de pago <span class="required-mark">*</span></label>
                <select id="id_forma_pago" name="id_forma_pago" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach (($formasPago ?? []) as $formaPago): ?>
                        <option
                            value="<?= (int) $formaPago['id_forma_pago'] ?>"
                            <?= ((int) ($datos['id_forma_pago'] ?? 0) === (int) $formaPago['id_forma_pago']) ? ' selected' : '' ?>
                        ><?= htmlspecialchars($formaPago['nombre_forma_pago'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="tiene_garantia">¿Tiene garantía?</label>
                <?php $tieneGarantiaValor = (string) ($datos['tiene_garantia'] ?? '0'); ?>
                <select id="tiene_garantia" name="tiene_garantia" class="form-control">
                    <option value="0"<?= $tieneGarantiaValor === '0' ? ' selected' : '' ?>>No</option>
                    <option value="1"<?= $tieneGarantiaValor === '1' ? ' selected' : '' ?>>Sí</option>
                </select>
            </div>

            <div id="grupo-tiempo-garantia" class="form-group">
                <label class="form-label" for="tiempo_garantia">Tiempo de garantía</label>
                <?php $tiempoGarantiaValor = (string) ($datos['tiempo_garantia'] ?? ''); ?>
                <select id="tiempo_garantia" name="tiempo_garantia" class="form-control">
                    <option value="">Seleccione</option>
                    <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                        <option value="<?= $mes ?>"<?= $tiempoGarantiaValor === (string) $mes ? ' selected' : '' ?>><?= $mes ?> <?= $mes === 1 ? 'mes' : 'meses' ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div id="grupo-fin-garantia" class="form-group">
                <label class="form-label" for="fecha_fin_garantia">Fecha estimada de fin de garantía</label>
                <input type="text" id="fecha_fin_garantia" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label class="form-label" for="documento_respaldo_compra">Documento de respaldo</label>
                <div class="file-picker">
                    <input type="file" id="documento_respaldo_compra" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                    <label for="documento_respaldo_compra" class="file-picker-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                        Seleccionar archivo
                    </label>
                    <span class="file-picker-name">Ningún archivo seleccionado</span>
                </div>
            </div>
        </div>

        <div id="bloque-donacion" class="form-grid">
            <p class="form-section-subtitulo">Datos de donación</p>

            <div class="form-group">
                <label class="form-label" for="procedencia_donacion">Procedencia <span class="required-mark">*</span></label>
                <input type="text" id="procedencia_donacion" name="procedencia" class="form-control" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="entidad_donante">Entidad donante <span class="required-mark">*</span></label>
                <input type="text" id="entidad_donante" name="entidad_donante" class="form-control" value="<?= htmlspecialchars($datos['entidad_donante'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="numero_acta_donacion">Número de acta <span class="required-mark">*</span></label>
                <input type="text" id="numero_acta_donacion" name="numero_acta" class="form-control" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_acta_donacion">Fecha de acta <span class="required-mark">*</span></label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_acta_donacion"
                        name="fecha_acta"
                        class="form-control"
                        value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_acta_donacion" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="documento_respaldo_donacion">Documento de respaldo</label>
                <div class="file-picker">
                    <input type="file" id="documento_respaldo_donacion" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                    <label for="documento_respaldo_donacion" class="file-picker-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                        Seleccionar archivo
                    </label>
                    <span class="file-picker-name">Ningún archivo seleccionado</span>
                </div>
            </div>
        </div>

        <div id="bloque-traslado" class="form-grid">
            <p class="form-section-subtitulo">Datos de traslado</p>

            <div class="form-group">
                <label class="form-label" for="procedencia_traslado">Procedencia <span class="required-mark">*</span></label>
                <input type="text" id="procedencia_traslado" name="procedencia" class="form-control" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="unidad_ejecutora_origen">Unidad ejecutora de origen <span class="required-mark">*</span></label>
                <input type="text" id="unidad_ejecutora_origen" name="unidad_ejecutora_origen" class="form-control" value="<?= htmlspecialchars($datos['unidad_ejecutora_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="codigo_unidad_origen">Código de unidad de origen <span class="required-mark">*</span></label>
                <input type="text" id="codigo_unidad_origen" name="codigo_unidad_origen" class="form-control" value="<?= htmlspecialchars($datos['codigo_unidad_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="numero_acta_traslado">Número de acta <span class="required-mark">*</span></label>
                <input type="text" id="numero_acta_traslado" name="numero_acta" class="form-control" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_acta_traslado">Fecha de acta <span class="required-mark">*</span></label>
                <div class="campo-fecha">
                    <input
                        type="text"
                        id="fecha_acta_traslado"
                        name="fecha_acta"
                        class="form-control"
                        value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DD/MM/AAAA"
                    >
                    <button type="button" class="btn-calendario" data-flatpickr-target="fecha_acta_traslado" aria-label="Abrir calendario">📅</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="documento_respaldo_traslado">Documento de respaldo</label>
                <div class="file-picker">
                    <input type="file" id="documento_respaldo_traslado" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                    <label for="documento_respaldo_traslado" class="file-picker-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                        Seleccionar archivo
                    </label>
                    <span class="file-picker-name">Ningún archivo seleccionado</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
            Guardar bien
        </button>
        <a href="index.php?modulo=bienes" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<aside class="form-layout-lateral">
    <div class="card">
        <h2 class="card-titulo">Código QR</h2>
        <div class="qr-preview" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <path d="M14 14h3v3h-3zM19 14h2M14 19h2M19 19h2"/>
            </svg>
        </div>
        <p class="qr-estado-texto">Pendiente de generación</p>
        <p class="form-hint form-hint-centrado">El código QR estará disponible después de guardar el bien.</p>
    </div>

    <div class="card">
        <h2 class="card-titulo">Estado del registro</h2>
        <div class="form-group">
            <span class="badge">Nuevo</span>
            <p class="form-hint">Registro sin guardar</p>
        </div>
    </div>
</aside>
</div>

<div id="modal-nueva-categoria" class="modal-overlay">
    <div class="modal-caja">
        <h2>Nueva categoría</h2>

        <p id="modal-categoria-mensaje" class="modal-mensaje"></p>

        <div class="form-group">
            <label class="form-label" for="modal-nombre-categoria">Nombre de la categoría <span class="required-mark">*</span></label>
            <input type="text" id="modal-nombre-categoria" class="form-control" maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label" for="modal-descripcion-categoria">Descripción</label>
            <textarea id="modal-descripcion-categoria" class="form-control"></textarea>
        </div>

        <div class="form-actions">
            <button type="button" id="btn-guardar-categoria" class="btn btn-primary">Guardar categoría</button>
            <button type="button" id="btn-cancelar-categoria" class="btn btn-secondary">Cancelar</button>
        </div>
    </div>
</div>

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

        function calcularFechaFinGarantia() {
            var campoFactura = document.getElementById('fecha_factura');
            var campoTiempo = document.getElementById('tiempo_garantia');
            var campoFin = document.getElementById('fecha_fin_garantia');
            var selectGarantia = document.getElementById('tiene_garantia');

            if (selectGarantia.value !== '1' || !campoFactura.value || !campoTiempo.value) {
                campoFin.value = '';
                return;
            }

            var partes = campoFactura.value.split('-');

            if (partes.length !== 3) {
                campoFin.value = '';
                return;
            }

            // Misma aritmética de calendario que calcularFinGarantia() en PHP (no
            // setUTCMonth() directo sobre el día de origen: eso se desborda de mes cuando
            // el día no existe en el mes destino, ej. 31 ene + 1 mes -> 3 mar en vez de 28 feb).
            var anioOrigen = parseInt(partes[0], 10);
            var mesOrigen = parseInt(partes[1], 10) - 1;
            var diaOrigen = parseInt(partes[2], 10);
            var meses = parseInt(campoTiempo.value, 10);

            var totalMeses = mesOrigen + meses;
            var anioDestino = anioOrigen + Math.floor(totalMeses / 12);
            var mesDestinoIndex = ((totalMeses % 12) + 12) % 12;

            var ultimoDiaMesDestino = new Date(Date.UTC(anioDestino, mesDestinoIndex + 1, 0)).getUTCDate();
            var diaDestino = Math.min(diaOrigen, ultimoDiaMesDestino);

            var dia = String(diaDestino).padStart(2, '0');
            var mes = String(mesDestinoIndex + 1).padStart(2, '0');

            campoFin.value = dia + '/' + mes + '/' + anioDestino;
        }

        function actualizarGarantia(esCompra) {
            var selectGarantia = document.getElementById('tiene_garantia');
            var campoTiempo = document.getElementById('tiempo_garantia');
            var grupoTiempo = document.getElementById('grupo-tiempo-garantia');
            var grupoFin = document.getElementById('grupo-fin-garantia');

            var tieneGarantiaActiva = esCompra && selectGarantia.value === '1';

            grupoTiempo.style.display = tieneGarantiaActiva ? '' : 'none';
            campoTiempo.disabled = !tieneGarantiaActiva;
            grupoFin.style.display = tieneGarantiaActiva ? '' : 'none';

            aplicarRequerido('tiempo_garantia', tieneGarantiaActiva);

            calcularFechaFinGarantia();
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
            aplicarRequerido('id_forma_pago', esCompra);
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
        document.getElementById('fecha_factura').addEventListener('change', calcularFechaFinGarantia);
        document.getElementById('tiempo_garantia').addEventListener('change', calcularFechaFinGarantia);
        document.addEventListener('DOMContentLoaded', actualizarFormulario);

        actualizarFormulario();
    })();
</script>

<script>
    (function () {
        var modal = document.getElementById('modal-nueva-categoria');
        var btnAbrir = document.getElementById('btn-nueva-categoria');
        var btnCancelar = document.getElementById('btn-cancelar-categoria');
        var btnGuardar = document.getElementById('btn-guardar-categoria');
        var campoNombre = document.getElementById('modal-nombre-categoria');
        var campoDescripcion = document.getElementById('modal-descripcion-categoria');
        var campoMensaje = document.getElementById('modal-categoria-mensaje');
        var selectCategoria = document.getElementById('id_categoria');

        function obtenerTokenCsrf() {
            var campoToken = document.querySelector('#form-crear-bien input[name="csrf_token"]');
            return campoToken ? campoToken.value : '';
        }

        function mostrarMensajeModal(texto) {
            campoMensaje.textContent = texto || '';
        }

        function limpiarModal() {
            campoNombre.value = '';
            campoDescripcion.value = '';
            mostrarMensajeModal('');
        }

        function abrirModal() {
            limpiarModal();
            modal.classList.add('modal-abierto');
            campoNombre.focus();
        }

        function cerrarModal() {
            modal.classList.remove('modal-abierto');
        }

        function agregarCategoriaAlSelect(categoria) {
            var opcion = document.createElement('option');
            opcion.value = String(categoria.id_categoria);
            opcion.textContent = categoria.nombre_categoria;

            selectCategoria.appendChild(opcion);
            selectCategoria.value = String(categoria.id_categoria);
        }

        function guardarCategoria() {
            var nombre = campoNombre.value.trim();

            mostrarMensajeModal('');
            btnGuardar.disabled = true;

            var cuerpo = new URLSearchParams();
            cuerpo.set('csrf_token', obtenerTokenCsrf());
            cuerpo.set('nombre_categoria', nombre);
            cuerpo.set('descripcion', campoDescripcion.value.trim());

            fetch('index.php?modulo=bienes&accion=crear_categoria', {
                method: 'POST',
                body: cuerpo
            })
                .then(function (respuesta) {
                    var tipoContenido = respuesta.headers.get('Content-Type') || '';

                    if (tipoContenido.indexOf('application/json') === -1) {
                        throw new Error('respuesta_no_json');
                    }

                    return respuesta.json();
                })
                .then(function (payload) {
                    if (payload && payload.ok === true && payload.categoria) {
                        agregarCategoriaAlSelect(payload.categoria);
                        cerrarModal();
                        return;
                    }

                    mostrarMensajeModal((payload && payload.mensaje) || 'No se pudo registrar la categoría.');
                })
                .catch(function () {
                    mostrarMensajeModal('La sesión puede haber expirado. Recargue la página e intente nuevamente.');
                })
                .finally(function () {
                    btnGuardar.disabled = false;
                });
        }

        btnAbrir.addEventListener('click', abrirModal);
        btnCancelar.addEventListener('click', cerrarModal);
        btnGuardar.addEventListener('click', guardarCategoria);
    })();
</script>

<script>
    // Bloque independiente: solo actualiza el nombre visible del archivo elegido en los 3 campos
    // "Documento de respaldo" (Compra/Donación/Traslado). El <input type="file"> real no cambia —
    // sigue enviándose igual en el POST — esto es puramente presentación.
    (function () {
        document.querySelectorAll('.file-input').forEach(function (input) {
            var selector = input.closest('.file-picker');
            var nombre = selector ? selector.querySelector('.file-picker-name') : null;

            if (!nombre) {
                return;
            }

            var textoVacio = nombre.textContent;

            input.addEventListener('change', function () {
                if (input.files && input.files.length > 0) {
                    nombre.textContent = input.files[0].name;
                    nombre.classList.add('file-picker-name-activo');
                } else {
                    nombre.textContent = textoVacio;
                    nombre.classList.remove('file-picker-name-activo');
                }
            });
        });
    })();
</script>

<script>
    // Previsualización local de la fotografía del bien (sin AJAX): al elegir un archivo se muestra
    // una miniatura con object-fit contain; "Quitar imagen" solo limpia la selección del formulario.
    (function () {
        var input = document.getElementById('fotografia_bien');
        var caja = document.getElementById('fotografia-bien-preview');
        var img = document.getElementById('fotografia-bien-preview-img');
        var quitar = document.getElementById('fotografia-bien-quitar');
        if (!input || !caja || !img || !quitar) { return; }

        var urlActual = null;

        function limpiarUrl() {
            if (urlActual) { URL.revokeObjectURL(urlActual); urlActual = null; }
        }

        input.addEventListener('change', function () {
            limpiarUrl();
            if (input.files && input.files[0]) {
                urlActual = URL.createObjectURL(input.files[0]);
                img.src = urlActual;
                caja.hidden = false;
            } else {
                img.removeAttribute('src');
                caja.hidden = true;
            }
        });

        quitar.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('change'));
        });
    })();
</script>

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        inicializarSelectoresFecha(['fecha_ingreso', 'fecha_factura', 'fecha_acta_donacion', 'fecha_acta_traslado']);
    });
</script>
