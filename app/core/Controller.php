<?php

class Controller
{
    public function __construct()
    {
        // Controlador base del sistema.
    }

    protected function model(string $modelName): object
    {
        $modelFile = __DIR__ . '/../models/' . $modelName . '.php';

        if (!file_exists($modelFile)) {
            throw new RuntimeException("Modelo no encontrado: {$modelName}");
        }

        require_once $modelFile;

        if (!class_exists($modelName)) {
            throw new RuntimeException("Clase de modelo no encontrada: {$modelName}");
        }

        return new $modelName();
    }

    protected function view(string $view, array $data = [], ?string $layout = null): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);

        if ($layout === null) {
            require $viewFile;
            return;
        }

        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';

        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout no encontrado: {$layout}");
        }

        // El buffer solo se abre cuando se pide layout: así una vista sin layout (JSON, PDF,
        // Excel, QR, descargas) conserva exactamente el comportamiento de salida directa de antes.
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }
}
