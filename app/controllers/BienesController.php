<?php

require_once __DIR__ . '/../core/Controller.php';

class BienesController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $bienModel = $this->model('Bien');
        $bienes = $bienModel->getAll();

        $this->view('bienes/index', [
            'bienes' => $bienes,
        ]);
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $formaIngresoModel = $this->model('FormaIngreso');
        $categoriaBienModel = $this->model('CategoriaBien');
        $estadoBienModel = $this->model('EstadoBien');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');

        $formasIngreso = $formaIngresoModel->getActivas();
        $categorias = $categoriaBienModel->getActivas();
        $estados = $estadoBienModel->getActivos();
        $responsables = $responsableModel->getActivos();
        $ubicaciones = $ubicacionModel->getActivas();

        $this->view('bienes/crear', [
            'formasIngreso' => $formasIngreso,
            'categorias' => $categorias,
            'estados' => $estados,
            'responsables' => $responsables,
            'ubicaciones' => $ubicaciones,
        ]);
    }
}