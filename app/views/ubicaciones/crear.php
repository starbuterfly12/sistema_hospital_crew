<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UbicacionesController::crear()).
// Sin JavaScript. Todos los id/name/required/maxlength se mantienen iguales a la vista anterior
// (nombre_ubicacion, tipo_ubicacion, descripcion). El <select> de tipo sigue alimentándose del
// whitelist real UbicacionesController::TIPOS_UBICACION_VALIDOS. Validación de nombre único y de
// tipo permitido vive en el controlador y no se toca.
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$tipoUbicacionValor = $datosFormulario['tipo_ubicacion'] ?? '';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar área / ubicación</h1>
            <p class="page-subtitle">Ingrese la información del área o ubicación.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=ubicaciones" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos del área / ubicación</h2>
        <div class="form-grid">
            <div class="form-group form-grid-full">
                <label class="form-label" for="nombre_ubicacion">Nombre <span class="required-mark">*</span></label>
                <input type="text" id="nombre_ubicacion" name="nombre_ubicacion" class="form-control" maxlength="150" value="<?= $valorInput($datosFormulario['nombre_ubicacion'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="tipo_ubicacion">Tipo <span class="required-mark">*</span></label>
                <select id="tipo_ubicacion" name="tipo_ubicacion" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach (UbicacionesController::TIPOS_UBICACION_VALIDOS as $tipoOpcion): ?>
                        <option value="<?= htmlspecialchars($tipoOpcion, ENT_QUOTES, 'UTF-8') ?>"<?= $tipoUbicacionValor === $tipoOpcion ? ' selected' : '' ?>><?= htmlspecialchars($tipoOpcion, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-grid-full">
                <label class="form-label" for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($datosFormulario['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Registrar ubicación</button>
        <a href="index.php?modulo=ubicaciones" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
