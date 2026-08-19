<?php

require_once __DIR__ . '/../core/Controller.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RequisicionesController extends Controller
{
    private const ROLES_GESTION = ['Administrador', 'Operativo'];

    private const PLANTILLA_ARCHIVO = 'requisicion.xlsx';
    private const PLANTILLA_FECHA = 'F3';
    private const PLANTILLA_OFICIO = 'F4';
    private const PLANTILLA_NOMBRE_RECIBE = 'C7';
    private const PLANTILLA_AREA_RECIBE = 'C8';
    private const PLANTILLA_NUMEROS_REQUISICION = 'C11';
    private const PLANTILLA_FECHAS_REQUISICION = 'E11';
    private const PLANTILLA_FILA_ENCABEZADO_TABLA = 14;
    private const PLANTILLA_FILA_INICIO_TABLA = 15;
    private const PLANTILLA_FILA_FIN_TABLA_BASE = 31;
    private const PLANTILLA_RECIBI_NOMBRE = 'B37';
    private const PLANTILLA_RECIBI_AREA = 'B38';
    private const PLANTILLA_AUTORIZA_NOMBRE = 'E37';

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $requisicionModel = $this->model('Requisicion');

        $this->view('requisiciones/index', [
            'requisiciones' => $requisicionModel->getAll(),
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $requisicion = $requisicionModel->findById($idRequisicion);

        if ($requisicion === false) {
            echo 'Requisición no encontrada.';
            return;
        }

        $numeroModel = $this->model('NumeroRequisicion');
        $detalleModel = $this->model('DetalleRequisicion');

        $this->view('requisiciones/ver', [
            'requisicion' => $requisicion,
            'numeros' => $numeroModel->listarPorRequisicion($idRequisicion),
            'detalles' => $detalleModel->listarPorRequisicion($idRequisicion),
        ]);
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_GESTION);

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bienModel = $this->model('Bien');

        $responsables = $responsableModel->getActivos();

        [$bienesDisponibles, $errorAlmacen] = $this->cargarBienesDisponibles($ubicacionModel, $responsableModel, $bienModel);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('requisiciones/crear', [
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
                'error' => $errorAlmacen,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        [$datosFormulario, $numeroOficio, $pares, $idResponsableSolicitante, $idsBienes, $observaciones] = $this->leerFormulario();

        $error = $errorAlmacen ?? $this->validarFormulario($numeroOficio, $pares, $idResponsableSolicitante, $idsBienes);

        if ($error !== null) {
            $this->view('requisiciones/crear', [
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $numeroModel = $this->model('NumeroRequisicion');
        $detalleModel = $this->model('DetalleRequisicion');
        $asignacionModel = $this->model('Asignacion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $requisicionModel->beginTransaction();

            $responsableSolicitante = $responsableModel->findActivoByIdForUpdate($idResponsableSolicitante);

            if ($responsableSolicitante === false) {
                throw new RuntimeException('El responsable solicitante no está activo.');
            }

            $idUbicacionSolicitante = (int) $responsableSolicitante['id_ubicacion'];
            $ubicacionSolicitante = $ubicacionModel->findActivaByIdForUpdate($idUbicacionSolicitante);

            if ($ubicacionSolicitante === false) {
                throw new RuntimeException('No se puede registrar la requisición porque la ubicación del responsable solicitante está inactiva.');
            }

            [$idResponsableAlmacen, $idUbicacionAlmacen] = $this->resolverAlmacenForUpdate($ubicacionModel, $responsableModel);

            $bienesValidados = $this->validarBienesForUpdate($idsBienes, $bienModel, $asignacionModel, $detalleModel, $idResponsableAlmacen, $idUbicacionAlmacen, 0);

            $numeroSistema = $requisicionModel->generarSiguienteNumero((int) date('Y'));

            $idRequisicion = $requisicionModel->crear([
                'numero_requisicion_sistema' => $numeroSistema,
                'numero_oficio' => $numeroOficio,
                'id_responsable_solicitante' => $idResponsableSolicitante,
                'responsable_solicitante_mostrado' => $responsableSolicitante['nombre_completo'],
                'id_ubicacion_solicitante' => $idUbicacionSolicitante,
                'ubicacion_solicitante_mostrada' => $ubicacionSolicitante['nombre_ubicacion'],
                'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            foreach ($pares as $par) {
                $numeroModel->insertar($idRequisicion, $par['numero'], $par['fecha']);
            }

            foreach ($bienesValidados as $idBien => $info) {
                $detalleModel->insertar([
                    'id_requisicion' => $idRequisicion,
                    'id_bien' => $idBien,
                    'id_detalle_asignacion_origen' => $info['id_detalle_asignacion_origen'],
                    'codigo_interno_mostrado' => $info['codigo_interno'],
                    'codigo_sicoin_mostrado' => $info['codigo_sicoin'],
                    'descripcion_mostrada' => $info['descripcion'],
                    'serie_mostrada' => $info['serie'],
                    'modelo_mostrado' => $info['modelo'],
                    'valor_mostrado' => $info['valor'],
                    'estado_detalle' => 'pendiente',
                ]);
            }

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_REQUISICION',
                modulo: 'Requisiciones',
                resultado: 'exitoso',
                descripcion: sprintf(
                    'Se registró la requisición %s (Oficio %s) para %s/%s con %d bien(es).',
                    $numeroSistema,
                    $numeroOficio,
                    $responsableSolicitante['nombre_completo'],
                    $ubicacionSolicitante['nombre_ubicacion'],
                    count($bienesValidados)
                ),
                tablaAfectada: 'requisiciones',
                idRegistroAfectado: $idRequisicion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $requisicionModel->commit();

            header('Location: index.php?modulo=requisiciones&accion=ver&id=' . $idRequisicion);
            exit;
        } catch (Throwable $e) {
            if ($requisicionModel->inTransaction()) {
                $requisicionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar la requisición: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar la requisición. Verifique los datos e intente nuevamente.';
            }

            [$bienesDisponibles] = $this->cargarBienesDisponibles($ubicacionModel, $responsableModel, $bienModel);

            $this->view('requisiciones/crear', [
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
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

        requireRole(self::ROLES_GESTION);

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $requisicion = $requisicionModel->findById($idRequisicion);

        if ($requisicion === false) {
            echo 'Requisición no encontrada.';
            return;
        }

        if ($requisicion['estado_requisicion'] !== 'Pendiente') {
            echo 'Solo se pueden modificar requisiciones en estado Pendiente.';
            return;
        }

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bienModel = $this->model('Bien');
        $numeroModel = $this->model('NumeroRequisicion');
        $detalleModel = $this->model('DetalleRequisicion');

        $responsables = $responsableModel->getActivos();

        [$bienesDisponibles, $errorAlmacen] = $this->cargarBienesDisponibles($ubicacionModel, $responsableModel, $bienModel);

        $numerosActuales = $numeroModel->listarPorRequisicion($idRequisicion);
        $detallesActuales = $detalleModel->listarPorRequisicion($idRequisicion);

        // Los bienes que YA pertenecen a esta requisición deben poder seguir marcados aunque la
        // consulta de "disponibles" no los repita (no aplica aquí porque Pendiente no reserva, pero
        // se agregan de todas formas por si el bien dejó de cumplir alguna condición mientras tanto,
        // para que la usuaria pueda verlo y quitarlo explícitamente en vez de que desaparezca solo).
        $idsBienesDisponibles = array_map(static fn (array $b): int => (int) $b['id_bien'], $bienesDisponibles);

        foreach ($detallesActuales as $detalle) {
            $idBienDetalle = (int) $detalle['id_bien'];

            if (!in_array($idBienDetalle, $idsBienesDisponibles, true)) {
                $bienesDisponibles[] = [
                    'id_bien' => $idBienDetalle,
                    'codigo_interno' => $detalle['codigo_interno_mostrado'],
                    'codigo_sicoin' => $detalle['codigo_sicoin_mostrado'],
                    'descripcion' => $detalle['descripcion_mostrada'],
                    'marca' => null,
                    'modelo' => null,
                    'serie' => $detalle['serie_mostrada'],
                    'condicion_bien' => null,
                    'costo' => $detalle['valor_mostrado'],
                    'valor_estimado' => null,
                ];
            }
        }

        $idsBienesActuales = array_map(static fn (array $d): int => (int) $d['id_bien'], $detallesActuales);

        $datosFormularioBase = [
            'numero_oficio' => $requisicion['numero_oficio'],
            'id_responsable_solicitante' => (int) $requisicion['id_responsable_solicitante'],
            'observaciones' => $requisicion['observaciones'] ?? '',
            'numero_requisicion' => array_map(static fn (array $n): string => $n['numero_requisicion'], $numerosActuales),
            'fecha_requisicion' => array_map(static fn (array $n): string => $n['fecha_requisicion'], $numerosActuales),
            'bienes' => $idsBienesActuales,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('requisiciones/editar', [
                'requisicion' => $requisicion,
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
                'error' => $errorAlmacen,
                'datosFormulario' => $datosFormularioBase,
            ]);
            return;
        }

        verifyCsrf();

        [$datosFormulario, $numeroOficio, $pares, $idResponsableSolicitante, $idsBienesSubmit, $observaciones] = $this->leerFormulario();

        $error = $errorAlmacen ?? $this->validarFormulario($numeroOficio, $pares, $idResponsableSolicitante, $idsBienesSubmit);

        if ($error !== null) {
            $this->view('requisiciones/editar', [
                'requisicion' => $requisicion,
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $requisicionModel->beginTransaction();

            $requisicionBloqueada = $requisicionModel->findByIdForUpdate($idRequisicion);

            if ($requisicionBloqueada === false) {
                throw new RuntimeException('Requisición no encontrada.');
            }

            if ($requisicionBloqueada['estado_requisicion'] !== 'Pendiente') {
                throw new RuntimeException('Solo se pueden modificar requisiciones en estado Pendiente.');
            }

            $responsableSolicitante = $responsableModel->findActivoByIdForUpdate($idResponsableSolicitante);

            if ($responsableSolicitante === false) {
                throw new RuntimeException('El responsable solicitante no está activo.');
            }

            $idUbicacionSolicitante = (int) $responsableSolicitante['id_ubicacion'];
            $ubicacionSolicitante = $ubicacionModel->findActivaByIdForUpdate($idUbicacionSolicitante);

            if ($ubicacionSolicitante === false) {
                throw new RuntimeException('No se puede modificar la requisición porque la ubicación del responsable solicitante está inactiva.');
            }

            [$idResponsableAlmacen, $idUbicacionAlmacen] = $this->resolverAlmacenForUpdate($ubicacionModel, $responsableModel);

            // Diff contra el estado real en BD (bloqueado), no contra lo que mostraba el formulario.
            $detallesExistentes = $detalleModel->getPorRequisicionYEstadoForUpdate($idRequisicion, 'pendiente');
            $idsExistentes = array_map(static fn (array $d): int => (int) $d['id_bien'], $detallesExistentes);

            $idsAEliminar = array_diff($idsExistentes, $idsBienesSubmit);
            $idsAAgregar = array_diff($idsBienesSubmit, $idsExistentes);

            $bienesNuevosValidados = empty($idsAAgregar)
                ? []
                : $this->validarBienesForUpdate(array_values($idsAAgregar), $bienModel, $asignacionModel, $detalleModel, $idResponsableAlmacen, $idUbicacionAlmacen, $idRequisicion);

            foreach ($detallesExistentes as $detalle) {
                if (in_array((int) $detalle['id_bien'], $idsAEliminar, true)) {
                    $detalleModel->eliminarDetallePendiente((int) $detalle['id_detalle_requisicion']);
                }
            }

            foreach ($bienesNuevosValidados as $idBien => $info) {
                $detalleModel->insertar([
                    'id_requisicion' => $idRequisicion,
                    'id_bien' => $idBien,
                    'id_detalle_asignacion_origen' => $info['id_detalle_asignacion_origen'],
                    'codigo_interno_mostrado' => $info['codigo_interno'],
                    'codigo_sicoin_mostrado' => $info['codigo_sicoin'],
                    'descripcion_mostrada' => $info['descripcion'],
                    'serie_mostrada' => $info['serie'],
                    'modelo_mostrado' => $info['modelo'],
                    'valor_mostrado' => $info['valor'],
                    'estado_detalle' => 'pendiente',
                ]);
            }

            $numeroModel->eliminarPorRequisicion($idRequisicion);

            foreach ($pares as $par) {
                $numeroModel->insertar($idRequisicion, $par['numero'], $par['fecha']);
            }

            $requisicionModel->actualizarCabecera($idRequisicion, [
                'numero_oficio' => $numeroOficio,
                'id_responsable_solicitante' => $idResponsableSolicitante,
                'responsable_solicitante_mostrado' => $responsableSolicitante['nombre_completo'],
                'id_ubicacion_solicitante' => $idUbicacionSolicitante,
                'ubicacion_solicitante_mostrada' => $ubicacionSolicitante['nombre_ubicacion'],
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_REQUISICION',
                modulo: 'Requisiciones',
                resultado: 'exitoso',
                descripcion: 'Se modificó la requisición ' . $requisicionBloqueada['numero_requisicion_sistema'] . '.',
                tablaAfectada: 'requisiciones',
                idRegistroAfectado: $idRequisicion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $requisicionModel->commit();

            header('Location: index.php?modulo=requisiciones&accion=ver&id=' . $idRequisicion);
            exit;
        } catch (Throwable $e) {
            if ($requisicionModel->inTransaction()) {
                $requisicionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al modificar la requisición ' . $idRequisicion . ': ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible modificar la requisición. Verifique los datos e intente nuevamente.';
            }

            $this->view('requisiciones/editar', [
                'requisicion' => $requisicion,
                'responsables' => $responsables,
                'bienesDisponibles' => $bienesDisponibles,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    public function autorizar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $detalleModel = $this->model('DetalleRequisicion');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bienModel = $this->model('Bien');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $requisicionModel->beginTransaction();

            $requisicion = $requisicionModel->findByIdForUpdate($idRequisicion);

            if ($requisicion === false) {
                throw new RuntimeException('Requisición no encontrada.');
            }

            if ($requisicion['estado_requisicion'] !== 'Pendiente') {
                throw new RuntimeException('Solo se pueden autorizar requisiciones en estado Pendiente.');
            }

            $detalles = $detalleModel->getPorRequisicionYEstadoForUpdate($idRequisicion, 'pendiente');

            if (empty($detalles)) {
                throw new RuntimeException('No se puede autorizar una requisición sin bienes.');
            }

            [$idResponsableAlmacen, $idUbicacionAlmacen] = $this->resolverAlmacenForUpdate($ubicacionModel, $responsableModel);

            foreach ($detalles as $detalle) {
                $idBien = (int) $detalle['id_bien'];
                $etiqueta = $detalle['codigo_interno_mostrado'];

                $bien = $bienModel->findActualParaRequisicionForUpdate($idBien, $idResponsableAlmacen, $idUbicacionAlmacen);

                if ($bien === false) {
                    throw new RuntimeException('El bien ' . $etiqueta . ' ya no está disponible en Bodega de Almacén y la requisición no puede autorizarse.');
                }

                $detalleAsignacionOrigen = $this->model('Asignacion')->findDetalleByIdForUpdate((int) $detalle['id_detalle_asignacion_origen']);

                if ($detalleAsignacionOrigen === false || $detalleAsignacionOrigen['estado_detalle'] !== 'activo') {
                    throw new RuntimeException('El bien ' . $etiqueta . ' ya no tiene un detalle de asignación activo en Bodega.');
                }

                if ($detalleModel->existeReservaActivaPorBienForUpdate($idBien, $idRequisicion)) {
                    throw new RuntimeException('El bien ' . $etiqueta . ' ya fue reservado por otra requisición autorizada.');
                }
            }

            $detalleModel->marcarReservados($idRequisicion);
            $requisicionModel->autorizar($idRequisicion, (int) $_SESSION['id_usuario']);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'AUTORIZAR_REQUISICION',
                modulo: 'Requisiciones',
                resultado: 'exitoso',
                descripcion: 'Se autorizó la requisición ' . $requisicion['numero_requisicion_sistema'] . '.',
                tablaAfectada: 'requisiciones',
                idRegistroAfectado: $idRequisicion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $requisicionModel->commit();

            header('Location: index.php?modulo=requisiciones&accion=ver&id=' . $idRequisicion);
            exit;
        } catch (Throwable $e) {
            if ($requisicionModel->inTransaction()) {
                $requisicionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al autorizar la requisición ' . $idRequisicion . ': ' . $e->getMessage());
                echo 'No fue posible autorizar la requisición. Intente nuevamente.';
            }
            return;
        }
    }

    public function anular(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_GESTION);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $motivoAnulacion = trim((string) ($_POST['motivo_anulacion'] ?? ''));

        if ($motivoAnulacion === '') {
            echo 'El motivo de anulación es obligatorio.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $detalleModel = $this->model('DetalleRequisicion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $requisicionModel->beginTransaction();

            $requisicion = $requisicionModel->findByIdForUpdate($idRequisicion);

            if ($requisicion === false) {
                throw new RuntimeException('Requisición no encontrada.');
            }

            if (!in_array($requisicion['estado_requisicion'], ['Pendiente', 'Autorizada'], true)) {
                throw new RuntimeException('Solo se pueden anular requisiciones en estado Pendiente o Autorizada.');
            }

            $detalleModel->marcarAnulados($idRequisicion);
            $requisicionModel->anular($idRequisicion, $motivoAnulacion);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'ANULAR_REQUISICION',
                modulo: 'Requisiciones',
                resultado: 'exitoso',
                descripcion: 'Se anuló la requisición ' . $requisicion['numero_requisicion_sistema'] . '. Motivo: ' . $motivoAnulacion,
                tablaAfectada: 'requisiciones',
                idRegistroAfectado: $idRequisicion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $requisicionModel->commit();

            header('Location: index.php?modulo=requisiciones&accion=ver&id=' . $idRequisicion);
            exit;
        } catch (Throwable $e) {
            if ($requisicionModel->inTransaction()) {
                $requisicionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al anular la requisición ' . $idRequisicion . ': ' . $e->getMessage());
                echo 'No fue posible anular la requisición. Intente nuevamente.';
            }
            return;
        }
    }

    public function confirmarEntrega(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_GESTION);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $detalleModel = $this->model('DetalleRequisicion');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $bienModel = $this->model('Bien');
        $asignacionModel = $this->model('Asignacion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $requisicionModel->beginTransaction();

            $requisicion = $requisicionModel->findByIdForUpdate($idRequisicion);

            if ($requisicion === false) {
                throw new RuntimeException('Requisición no encontrada.');
            }

            if ($requisicion['estado_requisicion'] !== 'Autorizada') {
                throw new RuntimeException('Solo se pueden confirmar entregas de requisiciones en estado Autorizada.');
            }

            $detalles = $detalleModel->getPorRequisicionYEstadoForUpdate($idRequisicion, 'reservado');

            if (empty($detalles)) {
                throw new RuntimeException('Esta requisición no tiene bienes reservados para entregar.');
            }

            [$idResponsableAlmacen, $idUbicacionAlmacen] = $this->resolverAlmacenForUpdate($ubicacionModel, $responsableModel);

            $idResponsableSolicitante = (int) $requisicion['id_responsable_solicitante'];
            $responsableSolicitante = $responsableModel->findActivoByIdForUpdate($idResponsableSolicitante);

            if ($responsableSolicitante === false) {
                throw new RuntimeException('No se puede confirmar la entrega porque el responsable solicitante está inactivo.');
            }

            $idUbicacionDestino = (int) $responsableSolicitante['id_ubicacion'];
            $ubicacionDestino = $ubicacionModel->findActivaByIdForUpdate($idUbicacionDestino);

            if ($ubicacionDestino === false) {
                throw new RuntimeException('No se puede confirmar la entrega porque la ubicación del responsable solicitante está inactiva.');
            }

            // Resolver asignación vigente destino — mismos Casos A/B/C que TrasladosController::crear().
            $asignacionVigenteDestino = $asignacionModel->findVigentePorResponsableForUpdate($idResponsableSolicitante);

            if ($asignacionVigenteDestino !== false && $asignacionVigenteDestino['estado_asignacion'] === 'Pendiente') {
                throw new RuntimeException('El responsable solicitante tiene una asignación Pendiente sin confirmar. Debe resolverse antes de confirmar la entrega.');
            }

            if ($asignacionVigenteDestino !== false && $asignacionVigenteDestino['estado_asignacion'] === 'Asignada') {
                $idAsignacionDestino = (int) $asignacionVigenteDestino['id_asignacion'];

                if ((int) $asignacionVigenteDestino['id_ubicacion'] !== $idUbicacionDestino) {
                    throw new RuntimeException('La ubicación de la asignación destino no coincide con la ubicación actual del responsable.');
                }
            } else {
                $numeroAsignacionDestino = $asignacionModel->generarSiguienteNumero((int) date('Y'));

                $idAsignacionDestino = $asignacionModel->crear([
                    'numero_asignacion' => $numeroAsignacionDestino,
                    'id_responsable' => $idResponsableSolicitante,
                    'id_ubicacion' => $idUbicacionDestino,
                    'fecha_asignacion' => date('Y-m-d'),
                    'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                    'estado_asignacion' => 'Asignada',
                    'observaciones' => 'Asignación creada automáticamente por Requisición.',
                ]);
            }

            $fechaEntrega = date('Y-m-d');

            foreach ($detalles as $detalle) {
                $idBien = (int) $detalle['id_bien'];
                $etiqueta = $detalle['codigo_interno_mostrado'];

                $bien = $bienModel->findActualParaRequisicionForUpdate($idBien, $idResponsableAlmacen, $idUbicacionAlmacen);

                if ($bien === false) {
                    throw new RuntimeException('El bien ' . $etiqueta . ' ya no está disponible en Bodega de Almacén.');
                }

                $idAsignacionOrigenBien = (int) $bien['id_asignacion_actual'];
                $asignacionOrigen = $asignacionModel->findByIdForUpdate($idAsignacionOrigenBien);

                if ($asignacionOrigen === false) {
                    throw new RuntimeException('No se pudo verificar la asignación actual de Bodega para el bien ' . $etiqueta . '.');
                }

                $detalleOrigen = $asignacionModel->findDetalleActivoPorBienForUpdate($idAsignacionOrigenBien, $idBien);

                if ($detalleOrigen === false) {
                    throw new RuntimeException('El bien ' . $etiqueta . ' no tiene un detalle de asignación activo identificable en Bodega.');
                }

                $asignacionModel->retirarBien((int) $detalleOrigen['id_detalle_asignacion'], $fechaEntrega);

                $idDetalleAsignacionDestino = $asignacionModel->agregarBien($idAsignacionDestino, $idBien, $fechaEntrega, null);

                $espejoActualizado = $bienModel->actualizarAsignacionActual(
                    $idBien,
                    $idAsignacionOrigenBien,
                    $idAsignacionDestino,
                    $idResponsableSolicitante,
                    $idUbicacionDestino
                );

                if (!$espejoActualizado) {
                    throw new RuntimeException('No se pudo actualizar el bien ' . $etiqueta . ' porque su información cambió durante el proceso. Intente nuevamente.');
                }

                $detalleModel->marcarEntregado((int) $detalle['id_detalle_requisicion'], $idDetalleAsignacionDestino);
            }

            $requisicionModel->confirmarEntrega($idRequisicion, (int) $_SESSION['id_usuario']);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CONFIRMAR_ENTREGA_REQUISICION',
                modulo: 'Requisiciones',
                resultado: 'exitoso',
                descripcion: sprintf(
                    'Se confirmó la entrega de la requisición %s a %s/%s con %d bien(es).',
                    $requisicion['numero_requisicion_sistema'],
                    $responsableSolicitante['nombre_completo'],
                    $ubicacionDestino['nombre_ubicacion'],
                    count($detalles)
                ),
                tablaAfectada: 'requisiciones',
                idRegistroAfectado: $idRequisicion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $requisicionModel->commit();

            header('Location: index.php?modulo=requisiciones&accion=ver&id=' . $idRequisicion);
            exit;
        } catch (Throwable $e) {
            if ($requisicionModel->inTransaction()) {
                $requisicionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al confirmar la entrega de la requisición ' . $idRequisicion . ': ' . $e->getMessage());
                echo 'No fue posible confirmar la entrega. Intente nuevamente.';
            }
            return;
        }
    }

    public function descargarConstancia(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idRequisicion = (int) ($_GET['id'] ?? 0);

        if ($idRequisicion <= 0) {
            echo 'Requisición no válida.';
            return;
        }

        $requisicionModel = $this->model('Requisicion');
        $requisicion = $requisicionModel->findById($idRequisicion);

        if ($requisicion === false) {
            echo 'Requisición no encontrada.';
            return;
        }

        if (!in_array($requisicion['estado_requisicion'], ['Autorizada', 'Entregada'], true)) {
            echo 'La constancia solo puede descargarse cuando la requisición está Autorizada o Entregada.';
            return;
        }

        $numeroModel = $this->model('NumeroRequisicion');
        $detalleModel = $this->model('DetalleRequisicion');
        $ubicacionModel = $this->model('Ubicacion');
        $responsableModel = $this->model('Responsable');

        $numeros = $numeroModel->listarPorRequisicion($idRequisicion);
        $detalles = $detalleModel->listarPorRequisicion($idRequisicion);

        if (empty($detalles)) {
            echo 'Esta requisición no tiene bienes registrados.';
            return;
        }

        $ubicacionInventarios = $ubicacionModel->findInventariosInstitucional();

        if ($ubicacionInventarios === false) {
            echo 'No fue posible generar la constancia: la ubicación institucional de Inventarios no está configurada.';
            return;
        }

        $responsableInventarios = $responsableModel->findActivoPorUbicacion((int) $ubicacionInventarios['id_ubicacion']);

        if ($responsableInventarios === false) {
            echo 'No fue posible generar la constancia: no se identificó de forma única a la Encargada de Inventarios.';
            return;
        }

        try {
            $spreadsheet = $this->generarWorkbookConstancia($requisicion, $numeros, $detalles, $responsableInventarios['nombre_completo']);
        } catch (Throwable $e) {
            error_log('Error al generar la constancia de la requisición ' . $idRequisicion . ': ' . $e->getMessage());
            echo 'No fue posible generar la constancia de requisición.';
            return;
        }

        $nombreArchivo = 'Constancia_Requisicion_'
            . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $requisicion['numero_requisicion_sistema'])
            . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Reconstruye la constancia de Requisición como workbook XLSX a partir de la plantilla maestra
     * real aportada por la usuaria, SIN modificarla: se carga en memoria y se retorna el objeto
     * Spreadsheet ya listo para escribir. Misma filosofía que TrasladosController::generarWorkbookConstancia()
     * y PrestamosController::generarWorkbookConstanciaPrestamo(). Usa exclusivamente snapshots de
     * requisiciones/numeros_requisicion/detalle_requisicion — nunca datos actuales de bienes/responsables.
     */
    private function generarWorkbookConstancia(array $requisicion, array $numeros, array $detalles, string $nombreAutoriza): Spreadsheet
    {
        $rutaPlantilla = dirname(__DIR__, 2) . '/storage/templates/' . self::PLANTILLA_ARCHIVO;

        if (!is_file($rutaPlantilla)) {
            throw new RuntimeException('No se encontró la plantilla maestra de constancia de requisición.');
        }

        $spreadsheet = IOFactory::load($rutaPlantilla);
        $hoja = $spreadsheet->getSheet(0);

        // Repetir la fila de encabezado de la tabla de bienes en cada página física adicional.
        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(
            self::PLANTILLA_FILA_ENCABEZADO_TABLA,
            self::PLANTILLA_FILA_ENCABEZADO_TABLA
        );

        // Fecha del documento = fecha de autorización (no se pide otra fecha manual).
        $hoja->setCellValueExplicit(
            self::PLANTILLA_FECHA,
            $this->formatearFechaLarga((string) $requisicion['fecha_autorizacion']),
            DataType::TYPE_STRING
        );

        $hoja->setCellValueExplicit(self::PLANTILLA_OFICIO, (string) $requisicion['numero_oficio'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_NOMBRE_RECIBE, (string) $requisicion['responsable_solicitante_mostrado'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_AREA_RECIBE, (string) $requisicion['ubicacion_solicitante_mostrada'], DataType::TYPE_STRING);

        // Números/fechas de requisición institucional: concatenados en orden exacto, sin deduplicar.
        $numerosConcat = implode(', ', array_map(static fn (array $n): string => (string) $n['numero_requisicion'], $numeros));
        $fechasConcat = implode(', ', array_map(static fn (array $n): string => formatDate((string) $n['fecha_requisicion']), $numeros));

        $hoja->setCellValueExplicit(self::PLANTILLA_NUMEROS_REQUISICION, $numerosConcat, DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_FECHAS_REQUISICION, $fechasConcat, DataType::TYPE_STRING);

        // Recibí Conforme: el responsable solicitante al que finalmente se carga el bien (snapshot de
        // cabecera), nunca quien físicamente hizo clic en "Confirmar entrega".
        $hoja->setCellValueExplicit(self::PLANTILLA_RECIBI_NOMBRE, (string) $requisicion['responsable_solicitante_mostrado'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_RECIBI_AREA, (string) $requisicion['ubicacion_solicitante_mostrada'], DataType::TYPE_STRING);

        // Autoriza: nombre de la Encargada de Inventarios resuelto institucionalmente (nunca el
        // usuario que hizo clic en "Autorizar"). El cargo fijo ya está en la plantilla y no se toca.
        $hoja->setCellValueExplicit(self::PLANTILLA_AUTORIZA_NOMBRE, $nombreAutoriza, DataType::TYPE_STRING);

        // Tabla de bienes: la plantilla trae 17 filas base (15-31). Si hay más bienes, se insertan
        // filas adicionales justo después de la última fila base (antes de Total/firmas, ya escritas
        // arriba), que PhpSpreadsheet desplaza hacia abajo junto con su contenido y estilos — mismo
        // criterio que TrasladosController/PrestamosController.
        $filaInicio = self::PLANTILLA_FILA_INICIO_TABLA;
        $filaFinBase = self::PLANTILLA_FILA_FIN_TABLA_BASE;
        $capacidadBase = $filaFinBase - $filaInicio + 1;
        $totalBienes = count($detalles);
        $filasExtra = max(0, $totalBienes - $capacidadBase);

        if ($filasExtra > 0) {
            $filaInsercion = $filaFinBase + 1;
            $hoja->insertNewRowBefore($filaInsercion, $filasExtra);

            for ($i = 0; $i < $filasExtra; $i++) {
                $filaNueva = $filaInsercion + $i;

                foreach (['A', 'B', 'E', 'F', 'G'] as $columna) {
                    $hoja->duplicateStyle($hoja->getStyle($columna . $filaFinBase), $columna . $filaNueva);
                }

                $hoja->getRowDimension($filaNueva)->setRowHeight($hoja->getRowDimension($filaFinBase)->getRowHeight());
                $hoja->mergeCells('B' . $filaNueva . ':D' . $filaNueva);
            }
        }

        $fila = $filaInicio;

        // Ancho combinado real de la plantilla (B+C+D, el merge de descripción) y altura base de la
        // última fila de ejemplo — se usan como piso/referencia para estimar cuántas líneas ocupará
        // el texto largo, sin tocar los anchos de columna en ningún momento.
        $anchoDescripcionCombinado = (float) $hoja->getColumnDimension('B')->getWidth()
            + (float) $hoja->getColumnDimension('C')->getWidth()
            + (float) $hoja->getColumnDimension('D')->getWidth();
        $alturaFilaBase = (float) $hoja->getRowDimension($filaFinBase)->getRowHeight();

        foreach ($detalles as $detalle) {
            $codigoSicoin = trim((string) ($detalle['codigo_sicoin_mostrado'] ?? ''));
            $descripcionCompleta = $this->construirDescripcionCompleta($detalle);

            $hoja->setCellValue('A' . $fila, 1);
            $hoja->setCellValueExplicit('B' . $fila, $descripcionCompleta, DataType::TYPE_STRING);

            // wrapText en vez de truncar/reducir fuente/ensanchar columnas: Excel decide el corte
            // visual, la fila crece para mostrar el texto completo (ver calcularAlturaFilaDescripcion()).
            $hoja->getStyle('B' . $fila . ':D' . $fila)->getAlignment()->setWrapText(true);
            $hoja->getRowDimension($fila)->setRowHeight(
                $this->calcularAlturaFilaDescripcion($descripcionCompleta, $anchoDescripcionCombinado, $alturaFilaBase)
            );

            $hoja->setCellValueExplicit('E' . $fila, (string) $detalle['codigo_interno_mostrado'], DataType::TYPE_STRING);

            if ($codigoSicoin !== '') {
                $hoja->setCellValueExplicit('F' . $fila, $codigoSicoin, DataType::TYPE_STRING);
            }

            $hoja->setCellValue('G' . $fila, (float) $detalle['valor_mostrado']);

            $fila++;
        }

        // Limpiar filas base de ejemplo que no se usaron.
        for ($filaLimpiar = $fila; $filaLimpiar <= $filaFinBase; $filaLimpiar++) {
            $hoja->setCellValue('A' . $filaLimpiar, null);
            $hoja->setCellValue('B' . $filaLimpiar, null);
            $hoja->setCellValue('E' . $filaLimpiar, null);
            $hoja->setCellValue('F' . $filaLimpiar, null);
            $hoja->setCellValue('G' . $filaLimpiar, null);
        }

        // Total: nunca se confía en la fórmula =SUM(...) original de la plantilla (rango fijo, se
        // rompe si hubo filas insertadas) — se escribe el total calculado como valor plano.
        $filaTotal = $filaFinBase + 1 + $filasExtra;
        $totalCalculado = array_sum(array_map(
            static fn (array $d): float => (float) $d['valor_mostrado'],
            $detalles
        ));
        $hoja->setCellValue('G' . $filaTotal, $totalCalculado);

        return $spreadsheet;
    }

    /**
     * Compone "Descripción, modelo: X, serie: Y" para la columna B:D de la constancia, a partir
     * exclusivamente de los snapshots congelados en detalle_requisicion — nunca de bienes.modelo/serie
     * actuales, para que la constancia no cambie si el bien se edita después. Omite por completo
     * cualquier fragmento vacío o NULL (nunca escribe "modelo: -" ni "serie: NULL").
     */
    private function construirDescripcionCompleta(array $detalle): string
    {
        $partes = [trim((string) $detalle['descripcion_mostrada'])];

        $modelo = trim((string) ($detalle['modelo_mostrado'] ?? ''));

        if ($modelo !== '') {
            $partes[] = 'modelo: ' . $modelo;
        }

        $serie = trim((string) ($detalle['serie_mostrada'] ?? ''));

        if ($serie !== '') {
            $partes[] = 'serie: ' . $serie;
        }

        return implode(', ', $partes);
    }

    /**
     * Estima cuántas líneas ocupará el texto de descripción dentro del merge B:D con wrapText activo,
     * y devuelve la altura de fila (en puntos) necesaria para mostrarlo completo. PhpSpreadsheet no
     * calcula esto automáticamente para el writer Xlsx (no hay motor de renderizado real al guardar),
     * así que se estima con la heurística estándar de Excel: 1 unidad de ancho de columna ≈ 1 carácter
     * en la fuente por defecto. Nunca reduce la fila por debajo de $alturaMinima (la altura original de
     * la plantilla), y nunca toca anchos de columna.
     */
    private function calcularAlturaFilaDescripcion(string $texto, float $anchoColumnasCombinado, float $alturaMinima): float
    {
        $anchoColumnasCombinado = max($anchoColumnasCombinado, 1.0);
        $caracteresPorLinea = max(1, (int) floor($anchoColumnasCombinado));
        $longitudTexto = mb_strlen($texto, 'UTF-8');
        $lineasEstimadas = max(1, (int) ceil($longitudTexto / $caracteresPorLinea));

        $alturaPorLinea = 14.4;
        $alturaCalculada = $lineasEstimadas * $alturaPorLinea;

        return max($alturaMinima, $alturaCalculada);
    }

    /**
     * "16 de agosto de 2026" — controlado explícitamente en español, sin depender de
     * setlocale()/LC_TIME del sistema operativo. Misma implementación que TrasladosController y
     * PrestamosController.
     */
    private function formatearFechaLarga(string $fechaHora): string
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaHora)
            ?: DateTime::createFromFormat('Y-m-d', substr($fechaHora, 0, 10));

        if ($fecha === false) {
            return $fechaHora;
        }

        $dia = (int) $fecha->format('j');
        $mes = $meses[(int) $fecha->format('n')];
        $anio = $fecha->format('Y');

        return $dia . ' de ' . $mes . ' de ' . $anio;
    }

    /**
     * Resuelve y bloquea (FOR UPDATE) la Bodega de Almacén institucional y su responsable activo
     * único. Debe ejecutarse dentro de una transacción activa. Lanza RuntimeException si el Almacén
     * no está configurado correctamente o no tiene un responsable único identificable.
     *
     * @return array{0: int, 1: int} [idResponsableAlmacen, idUbicacionAlmacen]
     */
    private function resolverAlmacenForUpdate(Ubicacion $ubicacionModel, Responsable $responsableModel): array
    {
        $ubicacionAlmacen = $ubicacionModel->findActivaByIdForUpdate(Ubicacion::ID_UBICACION_ALMACEN_INSTITUCIONAL);

        if ($ubicacionAlmacen === false || $ubicacionAlmacen['tipo_ubicacion'] !== 'Bodega') {
            throw new RuntimeException('Almacén Institucional no está configurado correctamente.');
        }

        $idUbicacionAlmacen = (int) $ubicacionAlmacen['id_ubicacion'];

        $responsableAlmacenCandidato = $responsableModel->findActivoPorUbicacion($idUbicacionAlmacen);

        if ($responsableAlmacenCandidato === false) {
            throw new RuntimeException('No fue posible identificar de forma única al responsable actual de Bodega de Almacén.');
        }

        $responsableAlmacen = $responsableModel->findActivoByIdForUpdate((int) $responsableAlmacenCandidato['id_responsable']);

        if ($responsableAlmacen === false) {
            throw new RuntimeException('No fue posible identificar de forma única al responsable actual de Bodega de Almacén.');
        }

        return [(int) $responsableAlmacen['id_responsable'], $idUbicacionAlmacen];
    }

    /**
     * Valida y bloquea (FOR UPDATE) cada bien seleccionado: debe estar Activo, bajo el responsable/
     * ubicación de Bodega de Almacén, con detalle_asignacion activo identificable, y no reservado por
     * otra requisición ya Autorizada. Devuelve snapshots listos para insertar en detalle_requisicion.
     * Debe ejecutarse dentro de una transacción activa.
     *
     * @param int[] $idsBienes
     * @return array<int, array{codigo_interno: string, codigo_sicoin: ?string, descripcion: string, serie: ?string, valor: float, id_detalle_asignacion_origen: int}>
     */
    private function validarBienesForUpdate(
        array $idsBienes,
        Bien $bienModel,
        Asignacion $asignacionModel,
        DetalleRequisicion $detalleModel,
        int $idResponsableAlmacen,
        int $idUbicacionAlmacen,
        int $idRequisicionExcluir
    ): array {
        $bienesValidados = [];

        foreach ($idsBienes as $idBien) {
            $bien = $bienModel->findActualParaRequisicionForUpdate($idBien, $idResponsableAlmacen, $idUbicacionAlmacen);

            if ($bien === false) {
                throw new RuntimeException('Uno de los bienes seleccionados ya no está disponible en Bodega de Almacén.');
            }

            if ($detalleModel->existeReservaActivaPorBienForUpdate($idBien, $idRequisicionExcluir)) {
                throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' ya está reservado por otra requisición autorizada.');
            }

            $idAsignacionActual = (int) $bien['id_asignacion_actual'];
            $asignacionActual = $asignacionModel->findByIdForUpdate($idAsignacionActual);

            if ($asignacionActual === false) {
                throw new RuntimeException('No se pudo verificar la asignación actual de Bodega para el bien ' . $bien['codigo_interno'] . '.');
            }

            $detalleOrigen = $asignacionModel->findDetalleActivoPorBienForUpdate($idAsignacionActual, $idBien);

            if ($detalleOrigen === false) {
                throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' no tiene un detalle de asignación activo identificable en Bodega.');
            }

            $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

            if ($valor === null) {
                throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' no tiene costo ni valor estimado registrado y no puede incluirse en una requisición.');
            }

            $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));

            $modelo = trim((string) ($bien['modelo'] ?? ''));

            $bienesValidados[$idBien] = [
                'codigo_interno' => $bien['codigo_interno'],
                'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
                'descripcion' => $bien['descripcion'],
                'modelo' => $modelo !== '' ? $modelo : null,
                'serie' => $bien['serie'],
                'valor' => $valor,
                'id_detalle_asignacion_origen' => (int) $detalleOrigen['id_detalle_asignacion'],
            ];
        }

        return $bienesValidados;
    }

    /**
     * @return array{0: array, 1: ?string}
     */
    private function cargarBienesDisponibles(Ubicacion $ubicacionModel, Responsable $responsableModel, Bien $bienModel): array
    {
        $ubicacionAlmacen = $ubicacionModel->findAlmacenInstitucional();

        if ($ubicacionAlmacen === false) {
            return [[], 'Almacén Institucional no está configurado correctamente.'];
        }

        $idUbicacionAlmacen = (int) $ubicacionAlmacen['id_ubicacion'];
        $responsableAlmacen = $responsableModel->findActivoPorUbicacion($idUbicacionAlmacen);

        if ($responsableAlmacen === false) {
            return [[], 'No fue posible identificar de forma única al responsable actual de Bodega de Almacén.'];
        }

        $bienes = $bienModel->getDisponiblesEnAlmacenParaRequisicion((int) $responsableAlmacen['id_responsable'], $idUbicacionAlmacen);

        return [$bienes, null];
    }

    /**
     * @return array{0: array, 1: string, 2: array, 3: int, 4: int[], 5: string}
     */
    private function leerFormulario(): array
    {
        $numeroOficio = trim((string) ($_POST['numero_oficio'] ?? ''));
        $numerosPost = is_array($_POST['numero_requisicion'] ?? null) ? $_POST['numero_requisicion'] : [];
        $fechasPost = is_array($_POST['fecha_requisicion'] ?? null) ? $_POST['fecha_requisicion'] : [];
        $idResponsableSolicitante = (int) ($_POST['id_responsable_solicitante'] ?? 0);
        $idsBienes = array_values(array_unique(array_map('intval', $_POST['bienes'] ?? [])));
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

        $pares = [];

        foreach ($numerosPost as $indice => $numero) {
            $numeroLimpio = trim((string) $numero);
            $fechaLimpia = trim((string) ($fechasPost[$indice] ?? ''));

            if ($numeroLimpio === '' && $fechaLimpia === '') {
                continue;
            }

            $pares[] = ['numero' => $numeroLimpio, 'fecha' => $fechaLimpia];
        }

        $datosFormulario = [
            'numero_oficio' => $numeroOficio,
            'numero_requisicion' => array_column($pares, 'numero'),
            'fecha_requisicion' => array_column($pares, 'fecha'),
            'id_responsable_solicitante' => $idResponsableSolicitante,
            'bienes' => $idsBienes,
            'observaciones' => $observaciones,
        ];

        return [$datosFormulario, $numeroOficio, $pares, $idResponsableSolicitante, $idsBienes, $observaciones];
    }

    private function validarFormulario(string $numeroOficio, array $pares, int $idResponsableSolicitante, array $idsBienes): ?string
    {
        if ($numeroOficio === '') {
            return 'El número de oficio es obligatorio.';
        }

        if (mb_strlen($numeroOficio, 'UTF-8') > 50) {
            return 'El número de oficio no puede superar 50 caracteres.';
        }

        if (empty($pares)) {
            return 'Debe agregar al menos un número de requisición con su fecha.';
        }

        $numerosVistos = [];

        foreach ($pares as $par) {
            if ($par['numero'] === '' || $par['fecha'] === '') {
                return 'Cada número de requisición debe tener su fecha correspondiente completa.';
            }

            if (mb_strlen($par['numero'], 'UTF-8') > 50) {
                return 'El número de requisición no puede superar 50 caracteres.';
            }

            if (!isValidIsoDate($par['fecha'])) {
                return 'Una de las fechas de requisición no es válida.';
            }

            if (isset($numerosVistos[$par['numero']])) {
                return 'No se puede repetir el mismo número de requisición dentro de la misma constancia.';
            }

            $numerosVistos[$par['numero']] = true;
        }

        if ($idResponsableSolicitante <= 0) {
            return 'Debe seleccionar el responsable solicitante.';
        }

        if (empty($idsBienes)) {
            return 'Debe seleccionar al menos un bien.';
        }

        return null;
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

        if (str_contains($detalleError, 'uq_requisiciones_numero_sistema')) {
            return 'No fue posible generar un número único para la requisición. Intente nuevamente.';
        }

        if (str_contains($detalleError, 'uq_detalle_requisicion_bien')) {
            return 'Un bien fue seleccionado más de una vez en la requisición.';
        }

        if (str_contains($detalleError, 'uq_numeros_requisicion_requisicion_numero')) {
            return 'No se puede repetir el mismo número de requisición dentro de la misma constancia.';
        }

        if (str_contains($detalleError, 'uq_asignaciones_numero')) {
            return 'No fue posible generar un número único para la asignación destino. Intente nuevamente.';
        }

        return null;
    }
}
