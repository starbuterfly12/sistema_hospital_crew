<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ResponsablesController::crear()).
// Sin JavaScript. Todos los id/name/required/maxlength se mantienen exactamente iguales a la vista
// anterior (nombre_completo, nit, cargo, profesion, telefono, id_ubicacion). Solo cambió el marcado
// visual. La validación de NIT (obligatorio, ≤20, único) vive en el controlador y no se toca.
$ubicaciones = $ubicaciones ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$idUbicacionSeleccionada = (int) ($datosFormulario['id_ubicacion'] ?? 0);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar responsable</h1>
            <p class="page-subtitle">Ingrese la información del responsable.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=responsables" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos del responsable</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="nombre_completo">Nombre completo <span class="required-mark">*</span></label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" value="<?= $valorInput($datosFormulario['nombre_completo'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="nit">NIT <span class="required-mark">*</span></label>
                <input type="text" id="nit" name="nit" class="form-control" maxlength="20" value="<?= $valorInput($datosFormulario['nit'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="cargo">Cargo <span class="required-mark">*</span></label>
                <input type="text" id="cargo" name="cargo" class="form-control" value="<?= $valorInput($datosFormulario['cargo'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="profesion">Profesión</label>
                <input type="text" id="profesion" name="profesion" class="form-control" value="<?= $valorInput($datosFormulario['profesion'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" class="form-control" maxlength="20" value="<?= $valorInput($datosFormulario['telefono'] ?? '') ?>">
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="id_ubicacion">Área / ubicación <span class="required-mark">*</span></label>
                <select id="id_ubicacion" name="id_ubicacion" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($ubicaciones as $ubicacion): ?>
                        <option
                            value="<?= (int) $ubicacion['id_ubicacion'] ?>"
                            <?= ($idUbicacionSeleccionada === (int) $ubicacion['id_ubicacion']) ? ' selected' : '' ?>
                        ><?= htmlspecialchars($ubicacion['nombre_ubicacion'] . ' - ' . $ubicacion['tipo_ubicacion'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Guardar responsable</button>
        <a href="index.php?modulo=responsables" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
