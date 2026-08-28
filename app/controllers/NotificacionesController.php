<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Interacción del usuario con SUS notificaciones. No genera notificaciones (eso ocurre en el
 * momento del evento real, dentro de Requisiciones/Bajas). Aquí solo se marcan como leídas.
 *
 * Toda operación está acotada al usuario autenticado: Notificacion::findDeUsuario() y los UPDATE
 * llevan siempre `id_usuario = <sesión>`, de modo que cambiar el id de la URL por el de otro
 * usuario nunca marca ni abre una notificación ajena.
 */
class NotificacionesController extends Controller
{
    /**
     * GET index.php?modulo=notificaciones&accion=abrir&id=N
     * Verifica sesión + propiedad, marca la notificación como leída y redirige a su url_destino
     * (solo si es una ruta interna del sistema; si no, cae al panel principal).
     */
    public function abrir(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idNotificacion = (int) ($_GET['id'] ?? 0);

        $modelo = $this->model('Notificacion');
        $notificacion = $idNotificacion > 0 ? $modelo->findDeUsuario($idNotificacion, $idUsuario) : false;

        if ($notificacion === false) {
            // No existe o no es de este usuario: no se revela la diferencia, se vuelve al panel.
            header('Location: index.php?modulo=dashboard');
            exit;
        }

        $modelo->marcarLeida($idNotificacion, $idUsuario);

        $destino = (string) ($notificacion['url_destino'] ?? '');

        header('Location: ' . ($this->esRutaInterna($destino) ? $destino : 'index.php?modulo=dashboard'));
        exit;
    }

    /**
     * POST index.php?modulo=notificaciones&accion=marcar_todas
     * Marca como leídas todas las notificaciones no leídas del usuario autenticado. Sin modal de
     * confirmación (acción reversible y de bajo riesgo); CSRF obligatorio.
     */
    public function marcarTodas(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idUsuario = (int) $_SESSION['id_usuario'];

        try {
            $this->model('Notificacion')->marcarTodasLeidas($idUsuario);
        } catch (Throwable $e) {
            error_log('No se pudieron marcar todas las notificaciones como leídas: ' . $e->getMessage());
        }

        // Vuelve a la página desde la que se envió el formulario (siempre interna), o al panel.
        $retorno = (string) ($_POST['retorno'] ?? '');
        header('Location: ' . ($this->esRutaInterna($retorno) ? $retorno : 'index.php?modulo=dashboard'));
        exit;
    }

    /**
     * Solo se acepta redirección a rutas RELATIVAS internas del propio front controller
     * (index.php?...). Nunca a URLs absolutas, protocol-relative (//host) ni con esquema — así el
     * valor guardado (siempre construido por el servidor) no puede convertirse en un open redirect
     * aunque en el futuro alguien lo llenara con datos externos.
     */
    private function esRutaInterna(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '//') || str_contains($url, "\n") || str_contains($url, "\r")) {
            return false;
        }

        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) === 1) {
            return false; // tiene esquema (http:, javascript:, etc.)
        }

        return str_starts_with($url, 'index.php?');
    }
}
