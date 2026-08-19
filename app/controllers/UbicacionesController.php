<?php

require_once __DIR__ . '/../core/Controller.php';

class UbicacionesController extends Controller
{
    // tipo_ubicacion ya no se escribe libremente: solo se acepta uno de estos tres valores,
    // seleccionados desde un <select> cerrado. Como `ubicaciones` quedó vacía tras el reset de
    // datos de desarrollo, no hace falta migrar valores anteriores.
    public const TIPOS_UBICACION_VALIDOS = ['Área', 'Bodega', 'Departamento'];

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $ubicacionModel = $this->model('Ubicacion');

        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $ubicaciones = $ubicacionModel->buscar($q);
        } else {
            $ubicaciones = $ubicacionModel->getAll();
        }

        $this->view('ubicaciones/index', [
            'ubicaciones' => $ubicaciones,
            'q' => $q,
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idUbicacion = (int) ($_GET['id'] ?? 0);

        if ($idUbicacion <= 0) {
            echo 'Ubicación no válida.';
            return;
        }

        $ubicacionModel = $this->model('Ubicacion');
        $ubicacion = $ubicacionModel->findById($idUbicacion);

        if ($ubicacion === false) {
            echo 'Ubicación no encontrada.';
            return;
        }

        $this->view('ubicaciones/ver', [
            'ubicacion' => $ubicacion,
        ]);
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('ubicaciones/crear', [
                'error' => null,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        $nombreUbicacion = trim($_POST['nombre_ubicacion'] ?? '');
        $tipoUbicacion = trim($_POST['tipo_ubicacion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        $datosFormulario = [
            'nombre_ubicacion' => $nombreUbicacion,
            'tipo_ubicacion' => $tipoUbicacion,
            'descripcion' => $descripcion,
        ];

        $error = null;

        if ($nombreUbicacion === '') {
            $error = 'El nombre de la ubicación es obligatorio.';
        } elseif (strlen($nombreUbicacion) > 150) {
            $error = 'El nombre de la ubicación no puede superar los 150 caracteres.';
        } elseif ($tipoUbicacion === '') {
            $error = 'El tipo de ubicación es obligatorio.';
        } elseif (!in_array($tipoUbicacion, self::TIPOS_UBICACION_VALIDOS, true)) {
            $error = 'El tipo de ubicación seleccionado no es válido.';
        } elseif ($ubicacionModel->existeNombre($nombreUbicacion)) {
            $error = 'Ya existe una ubicación con ese nombre.';
        }

        if ($error !== null) {
            $this->view('ubicaciones/crear', [
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $datos = [
            'nombre_ubicacion' => $nombreUbicacion,
            'tipo_ubicacion' => $tipoUbicacion,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'estado_ubicacion' => 'activa',
        ];

        try {
            $ubicacionModel->beginTransaction();

            $idUbicacion = $ubicacionModel->crear($datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_UBICACION',
                modulo: 'Ubicaciones',
                resultado: 'exitoso',
                descripcion: 'Se registró la ubicación "' . $nombreUbicacion . '" (' . $tipoUbicacion . ').',
                tablaAfectada: 'ubicaciones',
                idRegistroAfectado: $idUbicacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $ubicacionModel->commit();

            header('Location: index.php?modulo=ubicaciones&accion=ver&id=' . $idUbicacion);
            exit;
        } catch (Throwable $e) {
            if ($ubicacionModel->inTransaction()) {
                $ubicacionModel->rollBack();
            }

            error_log('Error al registrar la ubicación: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible registrar la ubicación. Verifique los datos e intente nuevamente.';

            $this->view('ubicaciones/crear', [
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $idUbicacion = (int) ($_GET['id'] ?? 0);

        if ($idUbicacion <= 0) {
            echo 'Ubicación no válida.';
            return;
        }

        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        $ubicacion = $ubicacionModel->findById($idUbicacion);

        if ($ubicacion === false) {
            echo 'Ubicación no encontrada.';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('ubicaciones/editar', [
                'ubicacion' => $ubicacion,
                'error' => null,
                'datosFormulario' => $ubicacion,
            ]);
            return;
        }

        verifyCsrf();

        $nombreUbicacion = trim($_POST['nombre_ubicacion'] ?? '');
        $tipoUbicacion = trim($_POST['tipo_ubicacion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        $datosFormulario = [
            'nombre_ubicacion' => $nombreUbicacion,
            'tipo_ubicacion' => $tipoUbicacion,
            'descripcion' => $descripcion,
        ];

        $error = null;

        if ($nombreUbicacion === '') {
            $error = 'El nombre de la ubicación es obligatorio.';
        } elseif (strlen($nombreUbicacion) > 150) {
            $error = 'El nombre de la ubicación no puede superar los 150 caracteres.';
        } elseif ($tipoUbicacion === '') {
            $error = 'El tipo de ubicación es obligatorio.';
        } elseif (!in_array($tipoUbicacion, self::TIPOS_UBICACION_VALIDOS, true)) {
            $error = 'El tipo de ubicación seleccionado no es válido.';
        } elseif ($ubicacionModel->existeNombre($nombreUbicacion, $idUbicacion)) {
            $error = 'Ya existe una ubicación con ese nombre.';
        }

        if ($error !== null) {
            $this->view('ubicaciones/editar', [
                'ubicacion' => $ubicacion,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $datos = [
            'nombre_ubicacion' => $nombreUbicacion,
            'tipo_ubicacion' => $tipoUbicacion,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'estado_ubicacion' => $ubicacion['estado_ubicacion'],
        ];

        try {
            $ubicacionModel->beginTransaction();

            $ubicacionModel->actualizar($idUbicacion, $datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_UBICACION',
                modulo: 'Ubicaciones',
                resultado: 'exitoso',
                descripcion: 'Se modificó la información de la ubicación "' . $nombreUbicacion . '".',
                tablaAfectada: 'ubicaciones',
                idRegistroAfectado: $idUbicacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $ubicacionModel->commit();

            header('Location: index.php?modulo=ubicaciones&accion=ver&id=' . $idUbicacion);
            exit;
        } catch (Throwable $e) {
            if ($ubicacionModel->inTransaction()) {
                $ubicacionModel->rollBack();
            }

            error_log('Error al actualizar la ubicación: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible actualizar la ubicación. Verifique los datos e intente nuevamente.';

            $this->view('ubicaciones/editar', [
                'ubicacion' => $ubicacion,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function cambiarEstado(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idUbicacion = (int) ($_GET['id'] ?? 0);

        if ($idUbicacion <= 0) {
            echo 'Ubicación no válida.';
            return;
        }

        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        $ubicacion = $ubicacionModel->findById($idUbicacion);

        if ($ubicacion === false) {
            echo 'Ubicación no encontrada.';
            return;
        }

        $estadoAnterior = $ubicacion['estado_ubicacion'];

        if ($estadoAnterior === 'activa') {
            $estadoNuevo = 'inactiva';
        } elseif ($estadoAnterior === 'inactiva') {
            $estadoNuevo = 'activa';
        } else {
            echo 'Estado de la ubicación no válido.';
            return;
        }

        try {
            $ubicacionModel->beginTransaction();

            $ubicacionModel->cambiarEstado($idUbicacion, $estadoNuevo);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CAMBIAR_ESTADO_UBICACION',
                modulo: 'Ubicaciones',
                resultado: 'exitoso',
                descripcion:
                    'Cambio de estado: ' .
                    $estadoAnterior .
                    ' → ' .
                    $estadoNuevo .
                    ' ("' .
                    $ubicacion['nombre_ubicacion'] .
                    '").',
                tablaAfectada: 'ubicaciones',
                idRegistroAfectado: $idUbicacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $ubicacionModel->commit();

            header('Location: index.php?modulo=ubicaciones&accion=ver&id=' . $idUbicacion);
            exit;
        } catch (Throwable $e) {
            if ($ubicacionModel->inTransaction()) {
                $ubicacionModel->rollBack();
            }

            error_log('Error al cambiar el estado de la ubicación: ' . $e->getMessage());

            echo 'No fue posible cambiar el estado de la ubicación. Intente nuevamente.';
            return;
        }
    }

    private function mensajeErrorDuplicado(Throwable $e): ?string
    {
        if (!($e instanceof PDOException)) {
            return null;
        }

        if (($e->errorInfo[0] ?? null) !== '23000') {
            return null;
        }

        $detalleError = $e->errorInfo[2] ?? '';

        if (str_contains($detalleError, 'uq_ubicaciones_nombre')) {
            return 'Ya existe una ubicación con ese nombre.';
        }

        return null;
    }
}
