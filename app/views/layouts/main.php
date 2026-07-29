<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php $usuarioActual = currentUser(); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= url('dashboard') ?>">Sistema de Bienes - Hospital Chiquimula</a>
        <?php if ($usuarioActual): ?>
            <div class="d-flex align-items-center text-light">
                <span class="me-3">
                    <?= e($usuarioActual['nombre_completo']) ?>
                    <span class="badge bg-secondary ms-1"><?= e($usuarioActual['nombre_rol']) ?></span>
                </span>
                <a href="<?= url('auth/logout') ?>" class="btn btn-outline-light btn-sm">Cerrar sesion</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container-fluid py-4">
    <?php if ($mensaje = flash('success')): ?>
        <div class="alert alert-success"><?= e($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($mensaje = flash('error')): ?>
        <div class="alert alert-danger"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>
</body>
</html>
