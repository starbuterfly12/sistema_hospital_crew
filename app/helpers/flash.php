<?php

/**
 * Mensajes flash de una sola lectura, para el patrón POST → redirect → GET.
 *
 * Un controlador, DESPUÉS de que la operación real ya resultó exitosa (transacción confirmada,
 * método del modelo devolvió éxito), hace:
 *
 *     setFlash('success', 'Bien registrado correctamente', 'El bien fue registrado y quedó disponible en el sistema.');
 *     header('Location: ...');
 *     exit;
 *
 * y layouts/main.php consume el mensaje UNA sola vez con getFlash() y lo muestra como modal
 * (#modal-feedback). Nunca se muestra dos veces: getFlash() lo borra de la sesión al leerlo.
 *
 * Tipos previstos: 'success' | 'error' | 'warning' | 'info'.
 */

if (!function_exists('setFlash')) {
    function setFlash(string $tipo, string $titulo, string $mensaje): void
    {
        $tiposValidos = ['success', 'error', 'warning', 'info'];

        $_SESSION['flash'] = [
            'tipo' => in_array($tipo, $tiposValidos, true) ? $tipo : 'info',
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ];
    }
}

if (!function_exists('getFlash')) {
    /**
     * Devuelve el flash pendiente y lo elimina de la sesión (lectura única). Devuelve null si no hay.
     *
     * Puente de compatibilidad: si todavía no se migró un flujo y dejó un
     * $_SESSION['mensaje_exito'] / $_SESSION['mensaje_error'] sin que su vista lo consumiera,
     * se traduce a un flash equivalente para no perder el mensaje. Los flujos que ya imprimen ese
     * texto en su propia vista lo hacen ANTES de que corra el layout (la vista se bufferiza primero),
     * así que para ellos este puente nunca se dispara y no hay duplicado.
     *
     * @return array{tipo: string, titulo: string, mensaje: string}|null
     */
    function getFlash(): ?array
    {
        if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);

            return [
                'tipo' => (string) ($flash['tipo'] ?? 'info'),
                'titulo' => (string) ($flash['titulo'] ?? ''),
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        if (isset($_SESSION['mensaje_exito']) && $_SESSION['mensaje_exito'] !== '') {
            $mensaje = (string) $_SESSION['mensaje_exito'];
            unset($_SESSION['mensaje_exito']);

            return ['tipo' => 'success', 'titulo' => 'Operación realizada', 'mensaje' => $mensaje];
        }

        if (isset($_SESSION['mensaje_error']) && $_SESSION['mensaje_error'] !== '') {
            $mensaje = (string) $_SESSION['mensaje_error'];
            unset($_SESSION['mensaje_error']);

            return ['tipo' => 'error', 'titulo' => 'No fue posible completar la operación', 'mensaje' => $mensaje];
        }

        return null;
    }
}
