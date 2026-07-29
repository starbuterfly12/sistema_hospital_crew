<?php
/**
 * Router simple basado en segmentos de URL: /controlador/metodo/param1/param2
 * El .htaccess reescribe todas las peticiones hacia index.php?url=...
 */
class Router
{
    private string $defaultController = 'AuthController';
    private string $defaultMethod = 'login';
    private string $indexMethod = 'index';

    public function dispatch(string $url): void
    {
        $url = trim($url, '/');
        $segments = $url === '' ? [] : explode('/', $url);

        $hasControllerSegment = !empty($segments[0]);
        $controllerName = $hasControllerSegment ? $this->toControllerName($segments[0]) : $this->defaultController;
        $method = $segments[1] ?? ($hasControllerSegment ? $this->indexMethod : $this->defaultMethod);
        $params = array_slice($segments, 2);

        $controllerFile = APP_ROOT . '/app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    private function toControllerName(string $segment): string
    {
        $segment = str_replace(['-', '_'], ' ', $segment);
        $segment = str_replace(' ', '', ucwords($segment));
        return $segment . 'Controller';
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '404 - Pagina no encontrada';
    }
}
