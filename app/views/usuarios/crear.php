<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UsuariosController::crear()/guardar()).
// Solo presentación: endpoint (POST a accion=guardar), csrfField(), name/id de todos los campos,
// validaciones (required, minlength/maxlength) y campos obligatorios se conservan EXACTAMENTE.
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '';
};
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$roles = $roles ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Registrar usuario</h1>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=usuarios" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" action="index.php?modulo=usuarios&accion=guardar" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos del usuario</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="nombre_completo">Nombre completo <span class="required-mark">*</span></label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" maxlength="150" value="<?= $valorInput($datosFormulario['nombre_completo'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="usuario">Usuario <span class="required-mark">*</span></label>
                <input type="text" id="usuario" name="usuario" class="form-control" minlength="3" maxlength="50" value="<?= $valorInput($datosFormulario['usuario'] ?? '') ?>" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="correo">Correo</label>
                <input type="text" id="correo" name="correo" class="form-control" maxlength="100" value="<?= $valorInput($datosFormulario['correo'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" class="form-control" maxlength="20" value="<?= $valorInput($datosFormulario['telefono'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="id_rol">Rol <span class="required-mark">*</span></label>
                <select id="id_rol" name="id_rol" class="form-control" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= (int) $rol['id_rol'] ?>" <?= ((int) ($datosFormulario['id_rol'] ?? 0) === (int) $rol['id_rol']) ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="estado_usuario">Estado</label>
                <select id="estado_usuario" name="estado_usuario" class="form-control">
                    <option value="activo" <?= ($datosFormulario['estado_usuario'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($datosFormulario['estado_usuario'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section-title">Contraseña</h2>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="password">Contraseña <span class="required-mark">*</span></label>
                <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmacion">Confirmar contraseña <span class="required-mark">*</span></label>
                <input type="password" id="password_confirmacion" name="password_confirmacion" class="form-control" minlength="8" autocomplete="new-password" required>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Registrar usuario</button>
        <a href="index.php?modulo=usuarios" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
