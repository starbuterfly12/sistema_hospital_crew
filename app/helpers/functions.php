<?php
/**
 * Funciones auxiliares globales.
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return BASE_URL . '/public/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['id_usuario']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id_usuario'      => $_SESSION['id_usuario'],
        'nombre_completo' => $_SESSION['nombre_completo'],
        'usuario'         => $_SESSION['usuario'],
        'id_rol'          => $_SESSION['id_rol'],
        'nombre_rol'      => $_SESSION['nombre_rol'],
    ];
}

/**
 * Corta el flujo si no hay sesion activa.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
}

/**
 * Corta el flujo si el rol de la sesion no esta en la lista permitida.
 * Uso: requireRole(['Administrador'])
 */
function requireRole(array $rolesPermitidos): void
{
    requireLogin();

    if (!in_array($_SESSION['nombre_rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        die('No tienes permiso para acceder a este modulo.');
    }
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
