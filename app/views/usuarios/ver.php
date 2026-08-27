<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver UsuariosController::ver()).
// Solo lectura + acciones POST reales sin cambios (cambiarEstado / cambiarPassword): mismos
// endpoints, mismos name/id de campos, mismo csrfField(). El feedback (éxito/error) lo muestra
// layouts/main.php mediante #modal-feedback a partir del flash del controlador — esta vista ya NO
// imprime mensajes de sesión. Las confirmaciones de Activar/Inactivar usan el #modal-confirm global.
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};

$usuario = $usuario ?? [];
$esUsuarioActual = $esUsuarioActual ?? false;
$idUsuario = (int) ($usuario['id_usuario'] ?? 0);
$estadoUsuario = $usuario['estado_usuario'] ?? '';
$rolInactivo = (($usuario['estado_rol'] ?? 'activo') !== 'activo');

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'activo' => 'badge badge-exito',
        'inactivo' => 'badge badge-error',
        default => 'badge',
    };
};

$svgCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4L19 6"/></svg>';
$svgEquis = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de usuario</h1>
            <p class="page-subtitle">Consulta de la información y el acceso del usuario.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=usuarios" class="btn btn-secondary">Volver</a>
            <a href="index.php?modulo=usuarios&accion=editar&id=<?= $idUsuario ?>" class="btn btn-lila">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Modificar
            </a>

            <?php if (!$esUsuarioActual && $estadoUsuario === 'activo'): ?>
                <button type="button" class="btn btn-danger"
                    data-confirm
                    data-confirm-form="form-usuario-estado"
                    data-confirm-icon="alerta" data-confirm-variant="rosa"
                    data-confirm-title="Confirmar inactivación"
                    data-confirm-text="El usuario ya no podrá iniciar sesión en el sistema."
                    data-confirm-subtext="¿Desea inactivar el usuario?"
                    data-confirm-ok="Inactivar usuario"
                    data-confirm-btnclass="btn-danger">
                    <?= $svgEquis ?>
                    Inactivar usuario
                </button>
            <?php elseif (!$esUsuarioActual && $estadoUsuario === 'inactivo'): ?>
                <button type="button" class="btn btn-success"
                    data-confirm
                    data-confirm-form="form-usuario-estado"
                    data-confirm-icon="check" data-confirm-variant="menta"
                    data-confirm-title="Confirmar activación"
                    data-confirm-text="El usuario podrá volver a iniciar sesión en el sistema."
                    data-confirm-subtext="¿Desea activar el usuario?"
                    data-confirm-ok="Activar usuario"
                    data-confirm-btnclass="btn-success">
                    <?= $svgCheck ?>
                    Activar usuario
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$esUsuarioActual && in_array($estadoUsuario, ['activo', 'inactivo'], true)): ?>
    <form method="POST" action="index.php?modulo=usuarios&accion=cambiarEstado&id=<?= $idUsuario ?>" id="form-usuario-estado" hidden>
        <?= csrfField() ?>
        <button type="submit" tabindex="-1" aria-hidden="true">Confirmar</button>
    </form>
<?php endif; ?>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($usuario['nombre_completo'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($usuario['usuario'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos de usuario</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Nombre completo</span>
                <span class="detail-value"><?= $mostrar($usuario['nombre_completo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Usuario</span>
                <span class="detail-value"><?= $mostrar($usuario['usuario'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Rol</span>
                <span class="detail-value"><?= $mostrar($usuario['nombre_rol'] ?? null) ?><?= $rolInactivo ? ' (rol inactivo)' : '' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado($estadoUsuario) ?>"><?= $estadoUsuario === 'activo' ? 'Activo' : 'Inactivo' ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Correo</span>
                <span class="detail-value"><?= $mostrar($usuario['correo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Teléfono</span>
                <span class="detail-value"><?= $mostrar($usuario['telefono'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Último acceso</span>
                <span class="detail-value"><?= ($usuario['ultimo_acceso'] ?? null) !== null ? $mostrar(formatDateTime($usuario['ultimo_acceso'])) : 'Nunca' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($usuario['created_at'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de actualización</span>
                <span class="detail-value"><?= ($usuario['updated_at'] ?? null) !== null ? $mostrar(formatDateTime($usuario['updated_at'])) : '—' ?></span>
            </div>
        </div>
    </div>
</div>

<div class="form-card" style="margin-top:20px;">
    <div class="form-section">
        <h2 class="form-section-title">Cambiar contraseña</h2>

        <?php if ($esUsuarioActual): ?>
            <form method="POST" action="index.php?modulo=usuarios&accion=cambiarPassword&id=<?= $idUsuario ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="password_actual">Contraseña actual <span class="required-mark">*</span></label>
                        <input type="password" id="password_actual" name="password_actual" class="form-control" autocomplete="current-password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_nueva">Nueva contraseña <span class="required-mark">*</span></label>
                        <input type="password" id="password_nueva" name="password_nueva" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmacion">Confirmar nueva contraseña <span class="required-mark">*</span></label>
                        <input type="password" id="password_confirmacion" name="password_confirmacion" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                </div>
            </form>
        <?php else: ?>
            <form method="POST" action="index.php?modulo=usuarios&accion=cambiarPassword&id=<?= $idUsuario ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="password_nueva">Nueva contraseña <span class="required-mark">*</span></label>
                        <input type="password" id="password_nueva" name="password_nueva" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmacion">Confirmar nueva contraseña <span class="required-mark">*</span></label>
                        <input type="password" id="password_confirmacion" name="password_confirmacion" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
