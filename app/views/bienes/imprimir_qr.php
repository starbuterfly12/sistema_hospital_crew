<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR del bien</title>
    <style>
        .qr-contenedor {
            text-align: center;
        }

        /*
         * El PNG ya incluye "Código: XXXXX" bajo el QR (ver BienesController::guardarQrConEtiqueta).
         * Solo se fija el ancho: height:auto conserva la proporción real del archivo tanto para
         * QR antiguos (cuadrados, sin etiqueta) como para los nuevos (más altos, con etiqueta).
         * En pantalla se usa un tamaño cómodo en px; en impresión se fuerza la medida física
         * aprobada (ancho 4.33cm, alto automático ≈ 4.81cm con el PNG actual de 320x355px).
         */
        .qr-imagen {
            display: inline-block;
            width: 320px;
            height: auto;
        }

        @media print {
            .no-imprimir {
                display: none;
            }

            .qr-imagen {
                width: 4.33cm;
                height: auto;
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

    <h1 class="no-imprimir">Código QR del bien</h1>

    <p class="no-imprimir">
        <strong>Código de inventario:</strong>
        <?= htmlspecialchars($bien['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($rutaQr)): ?>
        <div class="qr-contenedor">
            <img class="qr-imagen" src="<?= htmlspecialchars($rutaQr, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR del bien">
        </div>

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
