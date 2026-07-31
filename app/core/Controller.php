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

    protected function view(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
