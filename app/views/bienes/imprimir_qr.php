<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR del bien</title>
    <style>
        @media print {
            .no-imprimir {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php
        $bien = $bien ?? [];
        $rutaQr = $bien['ruta_qr'] ?? null;
    ?>

    <div class="no-imprimir">
        <p><a href="index.php?modulo=bienes&accion=ver&id=<?= (int) ($bien['id_bien'] ?? 0) ?>">Volver al bien</a></p>
    </div>

    <h1>Código QR del bien</h1>

    <p>
        <strong>Código de inventario:</strong>
        <?= htmlspecialchars($bien['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($rutaQr)): ?>
        <p>
            <img src="<?= htmlspecialchars($rutaQr, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
        </p>

        <div class="no-imprimir">
            <button type="button" onclick="window.print()">Imprimir</button>
            <a href="<?= htmlspecialchars($rutaQr, ENT_QUOTES, 'UTF-8') ?>" download>Descargar QR</a>
        </div>
    <?php else: ?>
        <p>El bien no tiene un código QR disponible.</p>
    <?php endif; ?>

    <div class="no-imprimir">
        <p><a href="index.php?modulo=bienes&accion=ver&id=<?= (int) ($bien['id_bien'] ?? 0) ?>">Volver al bien</a></p>
    </div>
</body>
</html>
