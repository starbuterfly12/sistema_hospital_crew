<?php

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        static $baseUrl = null;

        if ($baseUrl === null) {
            $config = require __DIR__ . '/../../config/app.php';
            $baseUrl = rtrim($config['base_url'], '/');
        }

        $path = ltrim($path, '/');

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('assetUrl')) {
    // Mismo cache-busting por filemtime() que logoInstitucionalUrl(), generalizado a cualquier
    // archivo estático (CSS/JS): evita que un navegador que ya cacheó una versión anterior de
    // app.css/auth.css siga aplicando esos estilos viejos después de una edición del archivo.
    function assetUrl(string $path): string
    {
        $rutaAbsoluta = __DIR__ . '/../../' . ltrim($path, '/');

        if (is_file($rutaAbsoluta)) {
            return url($path) . '?v=' . filemtime($rutaAbsoluta);
        }

        return url($path);
    }
}

if (!function_exists('logoInstitucionalUrl')) {
    // Punto único usado por layouts/main.php y auth/login.php para resolver el logo institucional.
    // Cache-busting con filemtime(): el navegador vuelve a pedir el archivo automáticamente cada vez
    // que su contenido cambia (aunque el nombre físico logo-institucional.png se mantenga siempre
    // igual) — sin esto, un navegador que ya cacheó el PNG viejo seguiría mostrándolo indefinidamente.
    // Devuelve null si todavía no existe ningún archivo (SVG ni PNG) en public/img/.
    function logoInstitucionalUrl(): ?string
    {
        foreach (['svg', 'png'] as $extension) {
            $rutaAbsoluta = __DIR__ . '/../../public/img/logo-institucional.' . $extension;

            if (is_file($rutaAbsoluta)) {
                return url('public/img/logo-institucional.' . $extension) . '?v=' . filemtime($rutaAbsoluta);
            }
        }

        return null;
    }
}
