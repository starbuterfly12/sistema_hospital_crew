<div class="card shadow-sm auth-card">
    <div class="card-body p-4">
        <h1 class="h4 text-center mb-1">Hospital General de Chiquimula</h1>
        <p class="text-center text-muted mb-4">Sistema de Gestion de Bienes</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('auth/login') ?>" autocomplete="off">
            <?= csrfField() ?>

            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contrasena</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</div>
