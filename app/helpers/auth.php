<?php

if (!function_exists('tieneRol')) {
    function tieneRol(array $roles): bool
    {
        $rolActual = $_SESSION['nombre_rol'] ?? null;

        return $rolActual !== null && in_array($rolActual, $roles, true);
    }
}

if (!function_exists('requireRole')) {
    function requireRole(array $roles): void
    {
        if (tieneRol($roles)) {
            return;
        }

        http_response_code(403);
        echo 'No tiene permisos para realizar esta acción.';
        exit;
    }
}
