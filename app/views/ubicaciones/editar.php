<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UbicacionesController::editar()).
// Sin JavaScript. Mismos id/name/required/maxlength que Registrar; mismo endpoint POST (action
// explícito con id). Cambiar nombre/tipo/descripción aquí NO mueve bienes: el controlador solo hace
// UPDATE de ubicaciones (ver actualizar()). El whitelist de tipo (TIPOS_UBICACION_VALIDOS) se conserva.
$ubicacion = $ubicacion ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];

$valorInput = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$idUbicacion = (int) ($ubicacion['id_ubicacion'] ?? 0);
$tipoUbicacionValor = $datosFormulario['tipo_ubicacion'] ?? '';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Modificar área / ubicación</h1>
            <p class="page-subtitle">Actualice la información del área o ubicación.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=ubicaciones&accion=ver&id=<?= $idUbicacion ?>" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" class="form-card" action="index.php?modulo=ubicaciones&accion=editar&id=<?= $idUbicacion ?>">
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
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="index.php?modulo=ubicaciones&accion=ver&id=<?= $idUbicacion ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
