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
