<?php
/**
 * Controlador base. Provee carga de vistas dentro del layout y acceso a modelos.
 */
abstract class Controller
{
    protected function model(string $modelName): object
    {
        $modelFile = APP_ROOT . '/app/models/' . $modelName . '.php';
        require_once $modelFile;
        return new $modelName();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        extract($data);

        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die('Vista no encontrada: ' . $view);
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = APP_ROOT . '/app/views/layouts/' . $layout . '.php';
        require $layoutFile;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }
}
