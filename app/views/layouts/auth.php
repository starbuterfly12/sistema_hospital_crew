<?php

$tituloPagina = trim((string) ($tituloPagina ?? ''));
if ($tituloPagina === '') {
    $tituloPagina = 'Sistema de Gestión de Bienes';
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= url('public/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('public/css/auth.css') ?>">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-fondo" aria-hidden="true"></div>
        <div class="auth-centro">
            <?= $content ?? '' ?>
        </div>
        <p class="auth-pie">© 2026 MAVG</p>
    </div>
</body>
</html>
