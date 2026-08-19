<?php

require_once __DIR__ . '/../core/Controller.php';

class AsignacionesController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $asignacionModel = $this->model('Asignacion');

        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $asignaciones = $asignacionModel->buscar($q);
        } else {
            $asignaciones = $asignacionModel->getAll();
        }

        $this->view('asignaciones/index', [
            'asignaciones' => $asignaciones,
            'q' => $q,
        ]);
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $asignacionModel = $this->model('Asignacion');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        $responsables = $responsableModel->getActivos();
        $ubicaciones = $ubicacionModel->getActivas();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('asignaciones/crear', [
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
                'error' => null,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        $idResponsable = (int) ($_POST['id_responsable'] ?? 0);
        $fechaAsignacion = trim($_POST['fecha_asignacion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $datosFormulario = [
            'id_responsable' => $idResponsable,
            'fecha_asignacion' => $fechaAsignacion,
            'observaciones' => $observaciones,
        ];

        $error = null;
        $fechaValida = null;

        if ($idResponsable <= 0) {
            $error = 'Debe seleccionar un responsable.';
        } else {
            $responsableValido = false;
            foreach ($responsables as $responsable) {
                if ((int) $responsable['id_responsable'] === $idResponsable) {
                    $responsableValido = true;
                    break;
                }
            }

            if (!$responsableValido) {
                $error = 'El responsable seleccionado no es válido.';
            }
        }

        if ($error === null) {
            if ($fechaAsignacion === '') {
                $error = 'La fecha de asignación es obligatoria.';
            } elseif (!isValidIsoDate($fechaAsignacion)) {
                $error = 'La fecha de asignación no es válida.';
            } else {
                $fechaValida = DateTime::createFromFormat('!Y-m-d', $fechaAsignacion);
            }
        }

        if ($error !== null) {
            $this->view('asignaciones/crear', [
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        try {
            $asignacionModel->beginTransaction();

            $responsableBloqueado = $responsableModel->findActivoByIdForUpdate($idResponsable);

            if ($responsableBloqueado === false) {
                $asignacionModel->rollBack();

                $this->view('asignaciones/crear', [
                    'responsables' => $responsables,
                    'ubicaciones' => $ubicaciones,
                    'error' => 'El responsable seleccionado no es válido.',
                    'datosFormulario' => $datosFormulario,
                ]);
                return;
            }

            $idUbicacion = (int) $responsableBloqueado['id_ubicacion'];

            $ubicacionBloqueada = $ubicacionModel->findActivaByIdForUpdate($idUbicacion);

            if ($ubicacionBloqueada === false) {
                $asignacionModel->rollBack();

                $this->view('asignaciones/crear', [
                    'responsables' => $responsables,
                    'ubicaciones' => $ubicaciones,
                    'error' => 'No se puede crear la asignación porque la ubicación del responsable está inactiva.',
                    'datosFormulario' => $datosFormulario,
                ]);
                return;
            }

            if ($asignacionModel->existeAsignacionVigentePorResponsable($idResponsable)) {
                $asignacionModel->rollBack();

                $this->view('asignaciones/crear', [
                    'responsables' => $responsables,
                    'ubicaciones' => $ubicaciones,
                    'error' => 'El responsable ya tiene una asignación vigente.',
                    'datosFormulario' => $datosFormulario,
                ]);
                return;
            }

            $anio = (int) $fechaValida->format('Y');
            $numeroAsignacion = $asignacionModel->generarSiguienteNumero($anio);

            $datos = [
                'numero_asignacion' => $numeroAsignacion,
                'id_responsable' => $idResponsable,
                'id_ubicacion' => $idUbicacion,
                'fecha_asignacion' => $fechaAsignacion,
                'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                'estado_asignacion' => 'Pendiente',
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ];

            $idAsignacion = $asignacionModel->crear($datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_ASIGNACION',
                modulo: 'Asignaciones',
                resultado: 'exitoso',
                descripcion: 'Se registró la asignación ' . $numeroAsignacion . ' en estado Pendiente.',
                tablaAfectada: 'asignaciones',
                idRegistroAfectado: $idAsignacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $asignacionModel->commit();

            header('Location: index.php?modulo=asignaciones&accion=ver&id=' . $idAsignacion);
            exit;
        } catch (Throwable $e) {
            if ($asignacionModel->inTransaction()) {
                $asignacionModel->rollBack();
            }

            error_log('Error al registrar la asignación: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible registrar la asignación. Verifique los datos e intente nuevamente.';

            $this->view('asignaciones/crear', [
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $asignacion = $asignacionModel->findById($idAsignacion);

        if ($asignacion === false) {
            echo 'Asignación no encontrada.';
            return;
        }

        $bienesAsignacion = $asignacionModel->getBienesPorAsignacion($idAsignacion);

        $bienesDisponibles = [];

        if (
            in_array($asignacion['estado_asignacion'], ['Pendiente', 'Asignada'], true)
            && tieneRol(['Administrador', 'Operativo'])
        ) {
            $bienModel = $this->model('Bien');
            $bienesDisponibles = $bienModel->getDisponiblesParaAsignacion();
        }

        $this->view('asignaciones/ver', [
            'asignacion' => $asignacion,
            'bienesAsignacion' => $bienesAsignacion,
            'bienesDisponibles' => $bienesDisponibles,
        ]);
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $asignacion = $asignacionModel->findById($idAsignacion);

        if ($asignacion === false) {
            echo 'Asignación no encontrada.';
            return;
        }

        if ($asignacion['estado_asignacion'] !== 'Pendiente') {
            echo 'Solo se pueden modificar asignaciones en estado Pendiente.';
            return;
        }

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');

        $responsables = $responsableModel->getActivos();
        $ubicaciones = $ubicacionModel->getActivas();

        $idResponsableOriginal = (int) $asignacion['id_responsable'];
        $idUbicacionOriginal = (int) $asignacion['id_ubicacion'];

        $responsablesFormulario = $responsables;

        $responsableOriginalPresente = false;
        foreach ($responsables as $responsable) {
            if ((int) $responsable['id_responsable'] === $idResponsableOriginal) {
                $responsableOriginalPresente = true;
                break;
            }
        }

        if (!$responsableOriginalPresente) {
            $responsableOriginal = $responsableModel->findById($idResponsableOriginal);

            if ($responsableOriginal !== false) {
                $responsablesFormulario[] = [
                    'id_responsable' => (int) $responsableOriginal['id_responsable'],
                    'nombre_completo' => $responsableOriginal['nombre_completo'],
                    'id_ubicacion' => $responsableOriginal['id_ubicacion'] !== null ? (int) $responsableOriginal['id_ubicacion'] : null,
                    'nombre_ubicacion' => $responsableOriginal['nombre_ubicacion'],
                    'tipo_ubicacion' => $responsableOriginal['tipo_ubicacion'],
                    'es_inactivo' => true,
                ];
            } else {
                $responsablesFormulario[] = [
                    'id_responsable' => $idResponsableOriginal,
                    'nombre_completo' => $asignacion['responsable_nombre'],
                    'es_inactivo' => true,
                ];
            }
        }

        $ubicacionesFormulario = $ubicaciones;

        $ubicacionOriginalPresente = false;
        foreach ($ubicaciones as $ubicacion) {
            if ((int) $ubicacion['id_ubicacion'] === $idUbicacionOriginal) {
                $ubicacionOriginalPresente = true;
                break;
            }
        }

        if (!$ubicacionOriginalPresente) {
            $ubicacionesFormulario[] = [
                'id_ubicacion' => $idUbicacionOriginal,
                'nombre_ubicacion' => $asignacion['nombre_ubicacion'],
                'tipo_ubicacion' => $asignacion['tipo_ubicacion'],
                'es_inactiva' => true,
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('asignaciones/editar', [
                'asignacion' => $asignacion,
                'responsables' => $responsablesFormulario,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => null,
                'datosFormulario' => [
                    'id_responsable' => $idResponsableOriginal,
                    'id_ubicacion' => $idUbicacionOriginal,
                    'fecha_asignacion' => $asignacion['fecha_asignacion'],
                    'observaciones' => $asignacion['observaciones'],
                ],
            ]);
            return;
        }

        verifyCsrf();

        $idResponsable = (int) ($_POST['id_responsable'] ?? 0);
        $fechaAsignacion = trim($_POST['fecha_asignacion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $datosFormulario = [
            'id_responsable' => $idResponsable,
            'fecha_asignacion' => $fechaAsignacion,
            'observaciones' => $observaciones,
        ];

        $error = null;

        if ($idResponsable <= 0) {
            $error = 'Debe seleccionar un responsable.';
        } else {
            $responsableValido = false;
            foreach ($responsables as $responsable) {
                if ((int) $responsable['id_responsable'] === $idResponsable) {
                    $responsableValido = true;
                    break;
                }
            }

            if (!$responsableValido && $idResponsable === $idResponsableOriginal) {
                $responsableValido = true;
            }

            if (!$responsableValido) {
                $error = 'El responsable seleccionado no es válido.';
            }
        }

        if ($error === null) {
            if ($fechaAsignacion === '') {
                $error = 'La fecha de asignación es obligatoria.';
            } elseif (!isValidIsoDate($fechaAsignacion)) {
                $error = 'La fecha de asignación no es válida.';
            }
        }

        if ($error !== null) {
            $this->view('asignaciones/editar', [
                'asignacion' => $asignacion,
                'responsables' => $responsablesFormulario,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $bitacoraModel = $this->model('Bitacora');

        try {
            $asignacionModel->beginTransaction();

            $asignacionBloqueada = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacionBloqueada === false) {
                $asignacionModel->rollBack();
                echo 'Asignación no encontrada.';
                return;
            }

            if ($asignacionBloqueada['estado_asignacion'] !== 'Pendiente') {
                $asignacionModel->rollBack();
                echo 'Solo se pueden modificar asignaciones en estado Pendiente.';
                return;
            }

            $idResponsableActualBloqueado = (int) $asignacionBloqueada['id_responsable'];
            $esResponsableActual = $idResponsable === $idResponsableActualBloqueado;

            $responsableBloqueado = $responsableModel->findByIdForUpdate($idResponsable);

            if ($responsableBloqueado === false) {
                $asignacionModel->rollBack();

                $this->view('asignaciones/editar', [
                    'asignacion' => $asignacion,
                    'responsables' => $responsablesFormulario,
                    'ubicaciones' => $ubicacionesFormulario,
                    'error' => 'El responsable seleccionado no es válido.',
                    'datosFormulario' => $datosFormulario,
                ]);
                return;
            }

            if ($responsableBloqueado['estado_responsable'] === 'activo') {
                $idUbicacion = (int) $responsableBloqueado['id_ubicacion'];

                $ubicacionBloqueada = $ubicacionModel->findActivaByIdForUpdate($idUbicacion);

                if ($ubicacionBloqueada === false) {
                    $asignacionModel->rollBack();

                    $this->view('asignaciones/editar', [
                        'asignacion' => $asignacion,
                        'responsables' => $responsablesFormulario,
                        'ubicaciones' => $ubicacionesFormulario,
                        'error' => 'No se puede guardar la asignación porque la ubicación del responsable está inactiva.',
                        'datosFormulario' => $datosFormulario,
                    ]);
                    return;
                }

                if ($asignacionModel->existeAsignacionVigentePorResponsable($idResponsable, $idAsignacion)) {
                    $asignacionModel->rollBack();

                    $this->view('asignaciones/editar', [
                        'asignacion' => $asignacion,
                        'responsables' => $responsablesFormulario,
                        'ubicaciones' => $ubicacionesFormulario,
                        'error' => 'El responsable ya tiene una asignación vigente.',
                        'datosFormulario' => $datosFormulario,
                    ]);
                    return;
                }
            } else {
                if (!$esResponsableActual) {
                    $asignacionModel->rollBack();

                    $this->view('asignaciones/editar', [
                        'asignacion' => $asignacion,
                        'responsables' => $responsablesFormulario,
                        'ubicaciones' => $ubicacionesFormulario,
                        'error' => 'El responsable seleccionado no es válido.',
                        'datosFormulario' => $datosFormulario,
                    ]);
                    return;
                }

                $idUbicacion = (int) $responsableBloqueado['id_ubicacion'];

                if ($asignacionModel->existeAsignacionVigentePorResponsable($idResponsable, $idAsignacion)) {
                    $asignacionModel->rollBack();

                    $this->view('asignaciones/editar', [
                        'asignacion' => $asignacion,
                        'responsables' => $responsablesFormulario,
                        'ubicaciones' => $ubicacionesFormulario,
                        'error' => 'El responsable ya tiene una asignación vigente.',
                        'datosFormulario' => $datosFormulario,
                    ]);
                    return;
                }
            }

            $datos = [
                'id_responsable' => $idResponsable,
                'id_ubicacion' => $idUbicacion,
                'fecha_asignacion' => $fechaAsignacion,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ];

            $asignacionModel->actualizar($idAsignacion, $datos);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_ASIGNACION',
                modulo: 'Asignaciones',
                resultado: 'exitoso',
                descripcion: 'Se modificó la asignación ' . $asignacionBloqueada['numero_asignacion'] . '.',
                tablaAfectada: 'asignaciones',
                idRegistroAfectado: $idAsignacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $asignacionModel->commit();

            header('Location: index.php?modulo=asignaciones&accion=ver&id=' . $idAsignacion);
            exit;
        } catch (Throwable $e) {
            if ($asignacionModel->inTransaction()) {
                $asignacionModel->rollBack();
            }

            error_log('Error al modificar la asignación: ' . $e->getMessage());

            $this->view('asignaciones/editar', [
                'asignacion' => $asignacion,
                'responsables' => $responsablesFormulario,
                'ubicaciones' => $ubicacionesFormulario,
                'error' => 'No fue posible modificar la asignación. Verifique los datos e intente nuevamente.',
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function agregarBien(): void
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

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $idBien = (int) ($_POST['id_bien'] ?? 0);
        $observacionesDetalle = trim($_POST['observaciones_detalle'] ?? '');

        if ($idBien <= 0) {
            echo 'Debe seleccionar un bien válido.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $bienModel = $this->model('Bien');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $asignacionModel->beginTransaction();

            $asignacion = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacion === false) {
                $asignacionModel->rollBack();
                echo 'Asignación no encontrada.';
                return;
            }

            if (!in_array($asignacion['estado_asignacion'], ['Pendiente', 'Asignada'], true)) {
                $asignacionModel->rollBack();
                echo 'Solo se pueden agregar bienes a asignaciones Pendientes o Asignadas.';
                return;
            }

            $esAsignada = $asignacion['estado_asignacion'] === 'Asignada';
            $idResponsableAsignacion = (int) $asignacion['id_responsable'];
            $idUbicacion = (int) $asignacion['id_ubicacion'];

            if ($esAsignada) {
                $responsableActivo = $responsableModel->findActivoByIdForUpdate($idResponsableAsignacion);

                if ($responsableActivo === false) {
                    $asignacionModel->rollBack();
                    echo 'No se puede agregar el bien porque el responsable de la asignación está inactivo.';
                    return;
                }

                if ($idUbicacion !== (int) $responsableActivo['id_ubicacion']) {
                    $asignacionModel->rollBack();
                    echo 'La ubicación de la asignación no coincide con la ubicación actual del responsable.';
                    return;
                }

                $idUbicacion = (int) $responsableActivo['id_ubicacion'];

                $ubicacionActiva = $ubicacionModel->findActivaByIdForUpdate($idUbicacion);

                if ($ubicacionActiva === false) {
                    $asignacionModel->rollBack();
                    echo 'No se puede agregar el bien porque la ubicación de la asignación está inactiva.';
                    return;
                }
            }

            if ($asignacionModel->existeBienEnAsignacion($idAsignacion, $idBien)) {
                $asignacionModel->rollBack();
                echo 'El bien ya pertenece al detalle de esta asignación.';
                return;
            }

            $bienDisponible = $bienModel->findDisponibleParaAsignacion($idBien);

            if ($bienDisponible === false) {
                $asignacionModel->rollBack();
                echo 'El bien seleccionado no está disponible para asignación.';
                return;
            }

            $idDetalle = $asignacionModel->agregarBien(
                $idAsignacion,
                $idBien,
                date('Y-m-d'),
                $observacionesDetalle !== '' ? $observacionesDetalle : null
            );

            if ($esAsignada) {
                $bienModel->asignarActual(
                    $idBien,
                    $idAsignacion,
                    $idResponsableAsignacion,
                    $idUbicacion
                );
            }

            $identificadorBien = $bienDisponible['codigo_interno'] ?? ('#' . $idBien);

            $descripcionBitacora = $esAsignada
                ? 'Se incorporó el bien ' . $identificadorBien . ' a la asignación ' . $asignacion['numero_asignacion'] . ' (estado Asignada).'
                : 'Se agregó el bien ' . $identificadorBien . ' a la asignación ' . $asignacion['numero_asignacion'] . '.';

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'AGREGAR_BIEN_ASIGNACION',
                modulo: 'Asignaciones',
                resultado: 'exitoso',
                descripcion: $descripcionBitacora,
                tablaAfectada: 'detalle_asignacion',
                idRegistroAfectado: $idDetalle,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $asignacionModel->commit();

            header('Location: index.php?modulo=asignaciones&accion=ver&id=' . $idAsignacion);
            exit;
        } catch (Throwable $e) {
            if ($asignacionModel->inTransaction()) {
                $asignacionModel->rollBack();
            }

            error_log('Error al agregar bien a la asignación: ' . $e->getMessage());

            echo 'No fue posible agregar el bien a la asignación. Intente nuevamente.';
            return;
        }
    }

    public function retirarBien(): void
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

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $idDetalleAsignacion = (int) ($_POST['id_detalle_asignacion'] ?? 0);

        if ($idDetalleAsignacion <= 0) {
            echo 'Detalle de asignación no válido.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');

        try {
            $asignacionModel->beginTransaction();

            $asignacion = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacion === false) {
                $asignacionModel->rollBack();
                echo 'Asignación no encontrada.';
                return;
            }

            if ($asignacion['estado_asignacion'] !== 'Pendiente') {
                $asignacionModel->rollBack();
                echo 'Solo se pueden retirar bienes mientras la asignación esté Pendiente.';
                return;
            }

            $detalle = $asignacionModel->findDetalleByIdForUpdate($idDetalleAsignacion);

            if ($detalle === false) {
                $asignacionModel->rollBack();
                echo 'El detalle de asignación no existe.';
                return;
            }

            if ((int) $detalle['id_asignacion'] !== $idAsignacion) {
                $asignacionModel->rollBack();
                echo 'El detalle de asignación no pertenece a esta asignación.';
                return;
            }

            if ($detalle['estado_detalle'] !== 'activo') {
                $asignacionModel->rollBack();
                echo 'Este bien ya fue retirado de la asignación.';
                return;
            }

            // La cabecera ya fue verificada como 'Pendiente' arriba: el bien nunca llegó a estar
            // realmente asignado, así que se elimina el detalle en vez de marcarlo 'retirado' y no
            // se genera bitácora de retiro (no hubo ningún retiro real que auditar).
            $asignacionModel->eliminarDetallePendiente($idDetalleAsignacion);

            $asignacionModel->commit();

            header('Location: index.php?modulo=asignaciones&accion=ver&id=' . $idAsignacion);
            exit;
        } catch (Throwable $e) {
            if ($asignacionModel->inTransaction()) {
                $asignacionModel->rollBack();
            }

            error_log('Error al retirar bien de la asignación: ' . $e->getMessage());

            echo 'No fue posible retirar el bien de la asignación. Intente nuevamente.';
            return;
        }
    }

    public function confirmar(): void
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

        $idAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idAsignacion <= 0) {
            echo 'Asignación no válida.';
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $bienModel = $this->model('Bien');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $asignacionModel->beginTransaction();

            $asignacion = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacion === false) {
                $asignacionModel->rollBack();
                echo 'Asignación no encontrada.';
                return;
            }

            if ($asignacion['estado_asignacion'] !== 'Pendiente') {
                $asignacionModel->rollBack();
                echo 'Solo se pueden confirmar asignaciones en estado Pendiente.';
                return;
            }

            $responsableActivo = $responsableModel->findActivoByIdForUpdate((int) $asignacion['id_responsable']);

            if ($responsableActivo === false) {
                $asignacionModel->rollBack();
                echo 'No se puede confirmar la asignación porque el responsable está inactivo.';
                return;
            }

            if ((int) $asignacion['id_ubicacion'] !== (int) $responsableActivo['id_ubicacion']) {
                $asignacionModel->rollBack();
                echo 'La ubicación de la asignación no coincide con la ubicación actual del responsable.';
                return;
            }

            $idUbicacion = (int) $responsableActivo['id_ubicacion'];

            $ubicacionActiva = $ubicacionModel->findActivaByIdForUpdate($idUbicacion);

            if ($ubicacionActiva === false) {
                $asignacionModel->rollBack();
                echo 'No se puede confirmar la asignación porque la ubicación está inactiva.';
                return;
            }

            $detalles = $asignacionModel->getDetallesActivosForUpdate($idAsignacion);

            if (empty($detalles)) {
                $asignacionModel->rollBack();
                echo 'No se puede confirmar una asignación sin bienes activos.';
                return;
            }

            foreach ($detalles as $detalle) {
                $bienConfirmable = $bienModel->findConfirmableParaAsignacion((int) $detalle['id_bien'], $idAsignacion);

                if ($bienConfirmable === false) {
                    $asignacionModel->rollBack();

                    error_log(
                        'Confirmación de asignación ' . $idAsignacion .
                        ' rechazada: el bien ' . $detalle['id_bien'] . ' ya no está disponible.'
                    );

                    echo 'No se puede confirmar la asignación porque uno o más bienes ya no están disponibles.';
                    return;
                }
            }

            foreach ($detalles as $detalle) {
                $bienModel->asignarActual(
                    (int) $detalle['id_bien'],
                    $idAsignacion,
                    (int) $asignacion['id_responsable'],
                    $idUbicacion
                );
            }

            $asignacionModel->confirmar($idAsignacion);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CONFIRMAR_ASIGNACION',
                modulo: 'Asignaciones',
                resultado: 'exitoso',
                descripcion: 'Se confirmó la asignación ' . $asignacion['numero_asignacion'] . '.',
                tablaAfectada: 'asignaciones',
                idRegistroAfectado: $idAsignacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $asignacionModel->commit();

            header('Location: index.php?modulo=asignaciones&accion=ver&id=' . $idAsignacion);
            exit;
        } catch (Throwable $e) {
            if ($asignacionModel->inTransaction()) {
                $asignacionModel->rollBack();
            }

            error_log('Error al confirmar la asignación: ' . $e->getMessage());

            echo 'No fue posible confirmar la asignación. Intente nuevamente.';
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

        if (str_contains($detalleError, 'uq_asignaciones_numero')) {
            return 'No fue posible generar un número único para la asignación. Intente nuevamente.';
        }

        return null;
    }
}