<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UsuariosController::editar()/actualizar()).
// Solo presentación: endpoint (POST a accion=actualizar&id=), csrfField(), name/id de todos los
// campos, validaciones y reglas de protección de Administrador se conservan EXACTAMENTE. El backend
// NO gestiona contraseña aquí (eso es cambiarPassword desde el detalle) — por eso este formulario no
// tiene campos de contraseña.
$valorInput = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$usuario = $usuario ?? [];
$roles = $roles ?? [];
$error = $error ?? null;
$datosFormulario = $datosFormulario ?? [];
$esUsuarioActual = $esUsuarioActual ?? false;
$idUsuario = (int) ($usuario['id_usuario'] ?? 0);
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Editar usuario</h1>
            <?php if ($esUsuarioActual): ?>
                <p class="page-subtitle">Está editando su propia cuenta: no puede inactivarla ni cambiar su rol de Administrador.</p>
            <?php endif; ?>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=usuarios&accion=ver&id=<?= $idUsuario ?>" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" action="index.php?modulo=usuarios&accion=actualizar&id=<?= $idUsuario ?>" class="form-card">
    <?= csrfField() ?>

    <div class="form-section">
        <h2 class="form-section-title">Datos generales</h2>
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
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= (int) $rol['id_rol'] ?>" <?= ((int) ($datosFormulario['id_rol'] ?? 0) === (int) $rol['id_rol']) ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?><?= !empty($rol['es_inactivo']) ? ' (Inactivo)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="estado_usuario">Estado</label>
                <select id="estado_usuario" name="estado_usuario" class="form-control">
                    <option value="activo" <?= ($datosFormulario['estado_usuario'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($datosFormulario['estado_usuario'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="index.php?modulo=usuarios&accion=ver&id=<?= $idUsuario ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
