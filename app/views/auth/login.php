<?php
// Fragmento de contenido: se renderiza dentro de layouts/auth.php (ver AuthController::login()).
// Misma resolución de logo institucional que usa layouts/main.php (logoInstitucionalUrl() en
// app/helpers/url.php, con cache-busting por filemtime()): si el archivo todavía no existiera en
// public/img/, se cae a un placeholder discreto en vez de romper la vista.
$logoInstitucionalUrl = logoInstitucionalUrl();

$error = $error ?? null;
?>
<div class="auth-card">
    <div class="auth-logo">
        <?php if ($logoInstitucionalUrl !== null): ?>
            <img src="<?= htmlspecialchars($logoInstitucionalUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo institucional" class="auth-logo-img">
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
            <div class="auth-input-password">
                <input type="password" id="password" name="password" class="auth-input" autocomplete="current-password" required>
                <button type="button" class="auth-toggle-password" data-toggle-password aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg data-eye-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg data-eye-off viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-boton">Ingresar</button>

        <?php if ($error !== null && $error !== ''): ?>
            <p class="auth-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </form>
</div>

<script>
    // Mostrar/ocultar contraseña — únicamente cambia el type del input en el navegador. No toca el
    // POST, el CSRF, la sesión ni el valor del campo, y no registra nada. El login no carga app.js
    // (solo main.php lo hace), por eso este bloque va inline en la vista.
    (function () {
        var campo = document.getElementById('password');
        var boton = document.querySelector('[data-toggle-password]');

        if (!campo || !boton) {
            return;
        }

        var iconoAbierto = boton.querySelector('[data-eye-open]');
        var iconoCerrado = boton.querySelector('[data-eye-off]');

        boton.addEventListener('click', function () {
            var mostrar = campo.type === 'password';

            campo.type = mostrar ? 'text' : 'password';
            boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');

            if (iconoAbierto) { iconoAbierto.hidden = mostrar; }
            if (iconoCerrado) { iconoCerrado.hidden = !mostrar; }

            campo.focus();
        });
    })();
</script>
