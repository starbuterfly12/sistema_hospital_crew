<?php

if (!function_exists('fotoBienUrl')) {
    // URL controlada para servir la fotografía principal de un bien (bienes.imagen_bien).
    // NUNCA expone la ruta física, el token ni el nombre interno: solo el id. El endpoint
    // BienesController::imagen() valida sesión, existencia y que el archivo caiga dentro de
    // storage/fotos_bienes/. Es la MISMA ruta que ya usa "Bienes -> Ver".
    function fotoBienUrl(int $idBien): string
    {
        return url('index.php?modulo=bienes&accion=imagen&id=' . $idBien);
    }
}

if (!function_exists('fotoBienThumb')) {
    // Miniatura de identificación del bien para los procesos operativos (Traslados, Préstamos,
    // Devoluciones, Verificación individual / por asignación). SOLO consulta: no permite subir,
    // reemplazar ni eliminar la fotografía (eso vive únicamente en Bienes -> Registrar / Modificar).
    // Al hacer clic abre el modal compartido #modal-foto-bien; incluir UNA vez por vista el parcial
    // app/views/partials/modal_foto_bien.php (carga public/js/foto-bien.js con la lógica).
    //
    //   $imagenBien: valor de bienes.imagen_bien (o null). Solo se usa para decidir si hay foto;
    //               nunca se imprime.
    //   $tamano: 'sm' (~52px, celdas de tabla) | 'md' (~72px, verificación por asignación)
    //          | 'lg' (~180px, verificación individual).
    //   $vacio:  'texto' -> "Sin fotografía" discreto | 'raya' -> "—" | 'nada' -> cadena vacía.
    function fotoBienThumb(
        int $idBien,
        $imagenBien,
        ?string $codigo = null,
        ?string $descripcion = null,
        string $tamano = 'sm',
        string $vacio = 'texto'
    ): string {
        $tamano = in_array($tamano, ['sm', 'md', 'lg'], true) ? $tamano : 'sm';
        $tieneFoto = $imagenBien !== null && trim((string) $imagenBien) !== '';

        if (!$tieneFoto) {
            return match ($vacio) {
                'raya' => '<span class="foto-bien-vacio">—</span>',
                'nada' => '',
                default => '<span class="foto-bien-vacio">Sin fotografía</span>',
            };
        }

        $u = htmlspecialchars(fotoBienUrl($idBien), ENT_QUOTES, 'UTF-8');
        $cod = htmlspecialchars((string) ($codigo ?? ''), ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string) ($descripcion ?? ''), ENT_QUOTES, 'UTF-8');

        return '<button type="button" class="foto-bien-thumb foto-bien-thumb--' . $tamano . '"'
            . ' data-foto-bien data-imagen="' . $u . '" data-codigo="' . $cod . '" data-descripcion="' . $desc . '"'
            . ' aria-label="Ampliar la fotografía del bien">'
            . '<img src="' . $u . '" alt="Fotografía del bien" loading="lazy">'
            . '</button>';
    }
}
