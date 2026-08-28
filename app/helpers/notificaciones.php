<?php

/**
 * Puente ligero entre layouts/main.php y el modelo Notificacion: evita que el layout tenga que
 * instanciar modelos ni escribir SQL. Devuelve lo justo para pintar la campana del topbar.
 *
 * Falla en silencio (devuelve el resumen vacío) si no hay sesión o si la consulta revienta —
 * la campana nunca debe romper el render de una página que por lo demás está bien.
 */

require_once __DIR__ . '/../models/Notificacion.php';

if (!function_exists('notificacionesResumenTopbar')) {
    /**
     * @return array{no_leidas:int, items:array<int,array<string,mixed>>}
     */
    function notificacionesResumenTopbar(): array
    {
        $vacio = ['no_leidas' => 0, 'items' => []];

        $idUsuario = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
        if ($idUsuario <= 0) {
            return $vacio;
        }

        try {
            $modelo = new Notificacion();

            return [
                'no_leidas' => $modelo->contarNoLeidas($idUsuario),
                'items' => $modelo->ultimasPara($idUsuario, 10),
            ];
        } catch (Throwable $e) {
            error_log('No se pudo cargar el resumen de notificaciones: ' . $e->getMessage());

            return $vacio;
        }
    }
}
