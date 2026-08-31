<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver BienesController::editar()).
// Todos los id/name se mantienen exactamente iguales al HTML anterior (no migrado) — el JS de más
// abajo depende de ellos por getElementById()/name y no fue tocado. Solo cambió el marcado visual
// (tarjeta + secciones + grid) y las clases CSS aplicadas a cada campo.
$datos = $datos ?? [];
$bien = $bien ?? [];
$error = $error ?? null;
$formaNombre = $formaNombre ?? '';
$categorias = $categorias ?? [];
$estados = $estados ?? [];
$documentoActual = $documentoActual ?? null;

$claseBadgeEstado = static function (?string $nombreEstado): string {
    return match ($nombreEstado) {
        'Activo' => 'badge badge-exito',
        'Baja' => 'badge badge-error',
        default => 'badge',
    };
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Modificar bien</h1>
            <p class="page-subtitle">Actualice la información del bien institucional.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=bienes&accion=ver&id=<?= (int) $bien['id_bien'] ?>" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="form-layout-con-lateral">
<form method="POST" id="form-editar-bien" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="form-grid">
            <?php
                // Código interno: bloqueado SIEMPRE en edición (se fija al registrar el bien).
                // Código SICOIN: bloqueado solo si el bien YA tiene uno registrado en BD; si está
                // vacío, se permite ingresarlo por primera vez (después queda bloqueado). El estado
                // de bloqueo del SICOIN se decide con $bien (fila real de BD), no con $datos, para
                // que un re-render tras error conserve el campo editable si aún no se guardó.
                $sicoinBloqueado = !empty($bien['codigo_sicoin']);
                $tituloBloqueado = 'Este dato no puede modificarse después de registrarse.';
            ?>
            <div class="form-group">
                <label class="form-label" for="codigo_interno">Código interno <span class="required-mark">*</span></label>
                <input
                    type="text"
                    id="codigo_interno"
                    name="codigo_interno"
                    class="form-control form-control-bloqueado"
                    value="<?= htmlspecialchars($datos['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                    title="<?= htmlspecialchars($tituloBloqueado, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="codigo_sicoin">Código SICOIN</label>
                <input
                    type="text"
                    id="codigo_sicoin"
                    name="codigo_sicoin"
                    class="form-control<?= $sicoinBloqueado ? ' form-control-bloqueado' : '' ?>"
                    value="<?= htmlspecialchars($datos['codigo_sicoin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    <?= $sicoinBloqueado ? 'readonly title="' . htmlspecialchars($tituloBloqueado, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                >
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

            <div class="form-group form-grid-full">
                <label class="form-label" for="descripcion">Descripción <span class="required-mark">*</span></label>
                <input type="text" id="descripcion" name="descripcion" class="form-control" value="<?= htmlspecialchars($datos['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control"><?= htmlspecialchars($datos['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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

            <?php if ($formaNombre === 'compra' || $formaNombre === 'traslado'): ?>
                <div class="form-group">
                    <label class="form-label" for="costo">Costo <span class="required-mark">*</span></label>
                    <div class="input-money">
                        <span class="input-money-prefix" aria-hidden="true">Q</span>
                        <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($datos['costo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
            <?php elseif ($formaNombre === 'donacion'): ?>
                <div class="form-group">
                    <label class="form-label" for="valor_estimado">Valor estimado <span class="required-mark">*</span></label>
                    <div class="input-money">
                        <span class="input-money-prefix" aria-hidden="true">Q</span>
                        <input type="number" id="valor_estimado" name="valor_estimado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($datos['valor_estimado'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Responsable actual</label>
                <input
                    type="text"
                    class="form-control"
                    value="<?= htmlspecialchars(!empty($bien['responsable_actual']) ? $bien['responsable_actual'] : 'Sin asignar', ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                    disabled
                >
            </div>

            <div class="form-group">
                <label class="form-label">Ubicación actual</label>
                <input
                    type="text"
                    class="form-control"
                    value="<?= htmlspecialchars(!empty($bien['ubicacion_actual']) ? $bien['ubicacion_actual'] . (!empty($bien['tipo_ubicacion']) ? ' - ' . $bien['tipo_ubicacion'] : '') : 'Sin asignar', ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                    disabled
                >
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Forma de ingreso</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label">Forma de ingreso</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($bien['nombre_forma'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                <p class="form-hint">La forma de ingreso no puede modificarse después del registro.</p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <?php if ($formaNombre === 'compra'): ?>
            <h2 class="form-section-title">Datos de compra</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="proveedor">Proveedor <span class="required-mark">*</span></label>
                    <input type="text" id="proveedor" name="proveedor" class="form-control" value="<?= htmlspecialchars($datos['proveedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numero_factura">Número de factura <span class="required-mark">*</span></label>
                    <input type="text" id="numero_factura" name="numero_factura" class="form-control" value="<?= htmlspecialchars($datos['numero_factura'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
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
                            required
                        >
                        <button type="button" class="btn-calendario" data-flatpickr-target="fecha_factura" aria-label="Abrir calendario">📅</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numero_liquidacion">Número de liquidación</label>
                    <input type="text" id="numero_liquidacion" name="numero_liquidacion" class="form-control" value="<?= htmlspecialchars($datos['numero_liquidacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <?php
                        // Forma de pago: NO obligatoria en edición (una compra histórica puede seguir
                        // sin ella si solo se edita otro dato). Si se elige una, el backend la valida y
                        // la guarda. Dejar "Seleccione" conserva lo que ya tenga la BD (NULL o valor).
                        $idFormaPagoActual = (int) ($datos['id_forma_pago'] ?? 0);
                    ?>
                    <label class="form-label" for="id_forma_pago">Forma de pago</label>
                    <select id="id_forma_pago" name="id_forma_pago" class="form-control">
                        <option value="">Seleccione</option>
                        <?php foreach (($formasPago ?? []) as $formaPago): ?>
                            <option
                                value="<?= (int) $formaPago['id_forma_pago'] ?>"
                                <?= ($idFormaPagoActual === (int) $formaPago['id_forma_pago']) ? ' selected' : '' ?>
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

                <div class="form-grid-documento">
                    <div class="form-group">
                        <label class="form-label">Documento actual</label>
                        <?php if (!empty($documentoActual)): ?>
                            <a href="<?= htmlspecialchars($documentoActual, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                                Ver documento actual
                            </a>
                        <?php else: ?>
                            <p class="form-hint">Sin documento de respaldo</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="documento_respaldo">Nuevo documento de respaldo</label>
                        <div class="file-picker">
                            <input type="file" id="documento_respaldo" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                            <label for="documento_respaldo" class="file-picker-button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                                <?= !empty($documentoActual) ? 'Seleccionar nuevo archivo' : 'Seleccionar archivo' ?>
                            </label>
                            <span class="file-picker-name">Ningún archivo seleccionado</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($formaNombre === 'donacion'): ?>
            <h2 class="form-section-title">Datos de donación</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="procedencia">Procedencia <span class="required-mark">*</span></label>
                    <input type="text" id="procedencia" name="procedencia" class="form-control" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="entidad_donante">Entidad donante <span class="required-mark">*</span></label>
                    <input type="text" id="entidad_donante" name="entidad_donante" class="form-control" value="<?= htmlspecialchars($datos['entidad_donante'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numero_acta">Número de acta <span class="required-mark">*</span></label>
                    <input type="text" id="numero_acta" name="numero_acta" class="form-control" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="fecha_acta">Fecha de acta</label>
                    <div class="campo-fecha">
                        <input
                            type="text"
                            id="fecha_acta"
                            name="fecha_acta"
                            class="form-control"
                            value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="DD/MM/AAAA"
                        >
                        <button type="button" class="btn-calendario" data-flatpickr-target="fecha_acta" aria-label="Abrir calendario">📅</button>
                    </div>
                </div>

                <div class="form-grid-documento">
                    <div class="form-group">
                        <label class="form-label">Documento actual</label>
                        <?php if (!empty($documentoActual)): ?>
                            <a href="<?= htmlspecialchars($documentoActual, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                                Ver documento actual
                            </a>
                        <?php else: ?>
                            <p class="form-hint">Sin documento de respaldo</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="documento_respaldo">Nuevo documento de respaldo</label>
                        <div class="file-picker">
                            <input type="file" id="documento_respaldo" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                            <label for="documento_respaldo" class="file-picker-button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                                <?= !empty($documentoActual) ? 'Seleccionar nuevo archivo' : 'Seleccionar archivo' ?>
                            </label>
                            <span class="file-picker-name">Ningún archivo seleccionado</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($formaNombre === 'traslado'): ?>
            <h2 class="form-section-title">Datos de traslado</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="procedencia">Procedencia <span class="required-mark">*</span></label>
                    <input type="text" id="procedencia" name="procedencia" class="form-control" value="<?= htmlspecialchars($datos['procedencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="unidad_ejecutora_origen">Unidad ejecutora de origen <span class="required-mark">*</span></label>
                    <input type="text" id="unidad_ejecutora_origen" name="unidad_ejecutora_origen" class="form-control" value="<?= htmlspecialchars($datos['unidad_ejecutora_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="codigo_unidad_origen">Código de unidad de origen <span class="required-mark">*</span></label>
                    <input type="text" id="codigo_unidad_origen" name="codigo_unidad_origen" class="form-control" value="<?= htmlspecialchars($datos['codigo_unidad_origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numero_acta">Número de acta <span class="required-mark">*</span></label>
                    <input type="text" id="numero_acta" name="numero_acta" class="form-control" value="<?= htmlspecialchars($datos['numero_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="fecha_acta">Fecha de acta</label>
                    <div class="campo-fecha">
                        <input
                            type="text"
                            id="fecha_acta"
                            name="fecha_acta"
                            class="form-control"
                            value="<?= htmlspecialchars($datos['fecha_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="DD/MM/AAAA"
                        >
                        <button type="button" class="btn-calendario" data-flatpickr-target="fecha_acta" aria-label="Abrir calendario">📅</button>
                    </div>
                </div>

                <div class="form-grid-documento">
                    <div class="form-group">
                        <label class="form-label">Documento actual</label>
                        <?php if (!empty($documentoActual)): ?>
                            <a href="<?= htmlspecialchars($documentoActual, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                                Ver documento actual
                            </a>
                        <?php else: ?>
                            <p class="form-hint">Sin documento de respaldo</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="documento_respaldo">Nuevo documento de respaldo</label>
                        <div class="file-picker">
                            <input type="file" id="documento_respaldo" name="documento_respaldo" class="file-input visually-hidden" accept=".pdf,.jpg,.jpeg,.png">
                            <label for="documento_respaldo" class="file-picker-button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 0 1-2.12-2.12l8.49-8.48"/></svg>
                                <?= !empty($documentoActual) ? 'Seleccionar nuevo archivo' : 'Seleccionar archivo' ?>
                            </label>
                            <span class="file-picker-name">Ningún archivo seleccionado</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
            Guardar cambios
        </button>
        <a href="index.php?modulo=bienes&accion=ver&id=<?= (int) $bien['id_bien'] ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<aside class="form-layout-lateral">
    <div class="card">
        <h2 class="card-titulo">Código QR</h2>
        <?php if (!empty($bien['ruta_qr'])): ?>
            <?php
                // Cache busting: el nombre de archivo es estable (bien_{id}.png), así que sin este
                // parámetro el navegador puede seguir mostrando el PNG anterior tras regenerar el QR.
                $versionQr = !empty($bien['updated_at']) ? strtotime((string) $bien['updated_at']) : time();
                $rutaQrConVersion = $bien['ruta_qr'] . '?v=' . $versionQr;
            ?>
            <div class="qr-preview qr-preview-real">
                <img src="<?= htmlspecialchars($rutaQrConVersion, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
            </div>
            <p class="qr-estado-texto"><?= htmlspecialchars($bien['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($bien['codigo_sicoin'])): ?>
                <p class="form-hint form-hint-centrado">SICOIN: <?= htmlspecialchars($bien['codigo_sicoin'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="qr-preview" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <path d="M14 14h3v3h-3zM19 14h2M14 19h2M19 19h2"/>
                </svg>
            </div>
            <p class="qr-estado-texto">QR no disponible</p>
            <p class="form-hint form-hint-centrado">Este bien todavía no tiene un código QR generado.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-titulo">Estado del registro</h2>
        <div class="form-group">
            <span class="<?= $claseBadgeEstado($bien['nombre_estado'] ?? null) ?>"><?= htmlspecialchars($bien['nombre_estado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
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

<?php if ($formaNombre === 'compra'): ?>
    <script>
        (function () {
            var selectGarantia = document.getElementById('tiene_garantia');
            var campoTiempo = document.getElementById('tiempo_garantia');
            var grupoTiempo = document.getElementById('grupo-tiempo-garantia');
            var grupoFin = document.getElementById('grupo-fin-garantia');
            var campoFactura = document.getElementById('fecha_factura');
            var campoFin = document.getElementById('fecha_fin_garantia');

            function calcularFechaFinGarantia() {
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

            function actualizarGarantia() {
                var activa = selectGarantia.value === '1';

                grupoTiempo.style.display = activa ? '' : 'none';
                campoTiempo.disabled = !activa;
                grupoFin.style.display = activa ? '' : 'none';

                if (activa) {
                    campoTiempo.setAttribute('required', 'required');
                } else {
                    campoTiempo.removeAttribute('required');
                }

                calcularFechaFinGarantia();
            }

            selectGarantia.addEventListener('change', actualizarGarantia);
            campoFactura.addEventListener('change', calcularFechaFinGarantia);
            campoTiempo.addEventListener('change', calcularFechaFinGarantia);
            document.addEventListener('DOMContentLoaded', actualizarGarantia);

            actualizarGarantia();
        })();
    </script>
<?php endif; ?>

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
            var campoToken = document.querySelector('#form-editar-bien input[name="csrf_token"]');
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
    // Bloque independiente: solo actualiza el nombre visible del archivo elegido en el campo
    // "Nuevo documento de respaldo". El <input type="file"> real no cambia — sigue enviándose
    // igual en el POST — esto es puramente presentación.
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

<link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
<script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
<script src="<?= url('public/js/fecha-picker.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        inicializarSelectoresFecha(['fecha_ingreso', 'fecha_factura', 'fecha_acta']);
    });
</script>
