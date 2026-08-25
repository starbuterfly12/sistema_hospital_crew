<?php
// Fragmento de contenido: se renderiza dentro de layouts/auth.php (ver AuthController::login()).
// Misma resolución de logo institucional que usa layouts/main.php: si el archivo todavía no
// existiera en public/img/, se cae a un placeholder discreto en vez de romper la vista.
$logoInstitucionalUrl = null;
foreach (['svg', 'png'] as $extensionLogo) {
    if (is_file(__DIR__ . '/../../../public/img/logo-institucional.' . $extensionLogo)) {
        $logoInstitucionalUrl = 'public/img/logo-institucional.' . $extensionLogo;
        break;
    }
}

$error = $error ?? null;
?>
<div class="auth-card">
    <div class="auth-logo">
        <?php if ($logoInstitucionalUrl !== null): ?>
            <img src="<?= htmlspecialchars(url($logoInstitucionalUrl), ENT_QUOTES, 'UTF-8') ?>" alt="Logo institucional" class="auth-logo-img">
        <?php else: ?>
            <span class="auth-logo-placeholder" aria-hidden="true">SGB</span>
        <?php endif; ?>
    </div>

    <h1 class="auth-titulo">Sistema de Gestión de<br>Bienes Institucionales</h1>
    <p class="auth-subtitulo">Iniciar sesión</p>

    <form method="POST">
        <?= csrfField() ?>

        <div class="auth-campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" class="auth-input" autocomplete="username" required>
        </div>

        <div class="auth-campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="auth-input" autocomplete="current-password" required>
        </div>

        <button type="submit" class="auth-boton">Ingresar</button>

        <?php if ($error !== null && $error !== ''): ?>
            <p class="auth-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </form>
</div>
