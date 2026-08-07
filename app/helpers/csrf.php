<?php

if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        $token = csrfToken();

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verifyCsrf')) {
    function verifyCsrf(): void
    {
        $tokenEnviado = $_POST['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';

        if ($tokenEnviado === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $tokenEnviado)) {
            http_response_code(403);
            echo 'Solicitud no válida.';
            exit;
        }
    }
}
