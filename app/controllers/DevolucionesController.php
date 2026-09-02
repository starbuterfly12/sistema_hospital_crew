<?php

require_once __DIR__ . '/../core/Controller.php';
// Reutiliza PrestamosController::ETIQUETAS_TIPO (solo lectura) en las vistas de Devoluciones — una
// devolución siempre está atada a un préstamo, así que "Entre servicios"/"Desde Inventarios" debe
// mostrarse con el mismo texto en ambos módulos. No se modifica PrestamosController.
require_once __DIR__ . '/PrestamosController.php';
// Reutiliza Bien::CONDICIONES_VALIDAS (solo lectura) para validar "condición de devolución" contra
// el mismo catálogo controlado que usa Bienes/Préstamos, en vez de aceptar texto libre.
require_once __DIR__ . '/../models/Bien.php';

class DevolucionesController extends Controller
{
    public const ETIQUETAS_ESTADO = [
        'parcial' => 'Parcial',
        'completa' => 'Completa',
    ];

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $devolucionModel = $this->model('Devolucion');

        $q = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        $this->view('devoluciones/index', [
            'devoluciones' => $devolucionModel->getAll($q !== '' ? $q : null, $estado !== '' ? $estado : null),
            'q' => $q,
            'estado' => $estado,
            'tituloPagina' => 'Devoluciones',
        ], 'main');
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idDevolucion = (int) ($_GET['id'] ?? 0);

        if ($idDevolucion <= 0) {
            echo 'Devolución no válida.';
            return;
        }

        $devolucionModel = $this->model('Devolucion');
        $devolucion = $devolucionModel->findById($idDevolucion);

        if ($devolucion === false) {
            echo 'Devolución no encontrada.';
            return;
        }

        $detalleDevolucionModel = $this->model('DetalleDevolucion');
        $detalles = $detalleDevolucionModel->listarPorDevolucion($idDevolucion);

        $this->view('devoluciones/ver', [
            'devolucion' => $devolucion,
            'detalles' => $detalles,
            'tituloPagina' => 'Detalle de devolución',
        ], 'main');
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $prestamoModel = $this->model('Prestamo');
        $detallePrestamoModel = $this->model('DetallePrestamo');

        $prestamosPendientes = $prestamoModel->getPendientesDevolucion();

        $detallesPorPrestamo = [];

        foreach ($prestamosPendientes as $prestamo) {
            $idPrestamo = (int) $prestamo['id_prestamo'];
            $detallesPorPrestamo[$idPrestamo] = array_map(
                static function (array $detalle): array {
                    return [
                        'id_detalle_prestamo' => (int) $detalle['id_detalle_prestamo'],
                        'id_bien' => (int) $detalle['id_bien'],
                        // Solo un booleano: nunca se expone la ruta / nombre interno de la foto.
                        'tiene_foto' => ($detalle['imagen_bien'] ?? null) !== null && trim((string) $detalle['imagen_bien']) !== '',
                        'codigo_interno' => $detalle['codigo_interno_mostrado'],
                        'codigo_sicoin' => $detalle['codigo_sicoin_mostrado'],
                        'descripcion' => $detalle['descripcion_mostrada'],
                        'serie' => $detalle['serie_mostrada'],
                        'modelo' => $detalle['modelo_mostrado'],
                        'condicion_entrega' => $detalle['condicion_entrega'],
                    ];
                },
                $detallePrestamoModel->listarPendientesPorPrestamo($idPrestamo)
            );
        }

        $idPrestamoPreseleccionado = (int) ($_GET['id_prestamo'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('devoluciones/crear', [
                'prestamosPendientes' => $prestamosPendientes,
                'detallesPorPrestamo' => $detallesPorPrestamo,
                'idPrestamoPreseleccionado' => $idPrestamoPreseleccionado,
                'error' => null,
                'datosFormulario' => [],
                'tituloPagina' => 'Registrar devolución',
            ], 'main');
            return;
        }

        verifyCsrf();

        $idPrestamo = (int) ($_POST['id_prestamo'] ?? 0);
        $idsDetalle = array_values(array_unique(array_map('intval', $_POST['detalles'] ?? [])));
        $condicionesDevolucionPost = is_array($_POST['condicion_devolucion'] ?? null) ? $_POST['condicion_devolucion'] : [];
        $observacionesPorBienPost = is_array($_POST['observaciones_bien'] ?? null) ? $_POST['observaciones_bien'] : [];
        $fechaDevolucion = trim((string) ($_POST['fecha_devolucion'] ?? ''));
        $motivoPost = trim((string) ($_POST['motivo'] ?? ''));
        $observacionesPost = trim((string) ($_POST['observaciones'] ?? ''));

        $datosFormulario = [
            'id_prestamo' => $idPrestamo,
            'detalles' => $idsDetalle,
            'condicion_devolucion' => $condicionesDevolucionPost,
            'observaciones_bien' => $observacionesPorBienPost,
            'fecha_devolucion' => $fechaDevolucion,
            'motivo' => $motivoPost,
            'observaciones' => $observacionesPost,
        ];

        $error = null;

        if ($idPrestamo <= 0) {
            $error = 'Debe seleccionar el préstamo a devolver.';
        } elseif (empty($idsDetalle)) {
            $error = 'Debe seleccionar al menos un bien para devolver.';
        } elseif ($fechaDevolucion === '' || !isValidIsoDate($fechaDevolucion)) {
            $error = 'La fecha de devolución no es válida.';
        } elseif (mb_strlen($motivoPost, 'UTF-8') > 150) {
            $error = 'El motivo no puede superar 150 caracteres.';
        }

        if ($error !== null) {
            $this->view('devoluciones/crear', [
                'prestamosPendientes' => $prestamosPendientes,
                'detallesPorPrestamo' => $detallesPorPrestamo,
                'idPrestamoPreseleccionado' => $idPrestamo,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar devolución',
            ], 'main');
            return;
        }

        $devolucionModel = $this->model('Devolucion');
        $detalleDevolucionModel = $this->model('DetalleDevolucion');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $prestamoModel->beginTransaction();

            // 1. Bloquear la cabecera del préstamo (serializa cualquier otra devolución concurrente
            // sobre este mismo préstamo — ver comentario en Prestamo::findByIdForUpdate()).
            $prestamo = $prestamoModel->findByIdForUpdate($idPrestamo);

            if ($prestamo === false) {
                throw new RuntimeException('El préstamo seleccionado no existe.');
            }

            if (!in_array($prestamo['estado_prestamo'], ['activo', 'parcial'], true)) {
                throw new RuntimeException('Este préstamo ya no admite devoluciones (Finalizado o Anulado).');
            }

            // 2-3. Por cada bien: bloquear su detalle y validar que siga pendiente, nunca confiando en
            // lo que llegó marcado en el checkbox del POST.
            $detallesValidados = [];

            foreach ($idsDetalle as $idDetalle) {
                $detalle = $detallePrestamoModel->findPendienteForUpdate($idDetalle, $idPrestamo);

                if ($detalle === false) {
                    throw new RuntimeException('Uno de los bienes seleccionados ya no está pendiente de devolución en este préstamo.');
                }

                $condicionDevolucion = trim((string) ($condicionesDevolucionPost[$idDetalle] ?? ''));

                // Mismo catálogo controlado que usa Bienes (Bien::CONDICIONES_VALIDAS), no texto libre:
                // rechaza tanto vacío como cualquier valor inventado o manipulado en el POST. Si un solo
                // bien seleccionado falla, se lanza aquí y toda la transacción se revierte — nada se
                // registra parcialmente.
                if (!in_array($condicionDevolucion, Bien::CONDICIONES_VALIDAS, true)) {
                    throw new RuntimeException('Debe indicar una condición de devolución válida para el bien ' . $detalle['codigo_interno_mostrado'] . '.');
                }

                $observacionBien = trim((string) ($observacionesPorBienPost[$idDetalle] ?? ''));

                $detallesValidados[$idDetalle] = [
                    'detalle' => $detalle,
                    'condicion_devolucion' => $condicionDevolucion,
                    'observaciones' => $observacionBien !== '' ? $observacionBien : null,
                ];
            }

            // Conteo de pendientes tomado ANTES de marcar nada como devuelto: con la cabecera del
            // préstamo ya bloqueada arriba, ningún otro proceso puede estar devolviendo detalles de
            // este mismo préstamo en paralelo, así que restarle la cantidad que se está devolviendo
            // ahora mismo da el número exacto de pendientes que quedarán tras esta operación.
            $totalPendientesAntes = $detallePrestamoModel->contarPendientesPorPrestamo($idPrestamo);
            $pendientesDespues = $totalPendientesAntes - count($detallesValidados);

            $estadoDevolucion = $pendientesDespues > 0 ? 'parcial' : 'completa';
            $estadoPrestamoNuevo = $pendientesDespues > 0 ? 'parcial' : 'finalizado';

            // 10-11. Cabecera de la devolución, numerada.
            $anio = (int) substr($fechaDevolucion, 0, 4);
            $numeroDevolucion = $devolucionModel->generarSiguienteNumero($anio);

            $idDevolucion = $devolucionModel->crear([
                'numero_devolucion' => $numeroDevolucion,
                'id_prestamo' => $idPrestamo,
                'id_usuario_recibe' => (int) $_SESSION['id_usuario'],
                'fecha_devolucion' => $fechaDevolucion,
                'estado_devolucion' => $estadoDevolucion,
                'motivo' => $motivoPost !== '' ? $motivoPost : null,
                'observaciones' => $observacionesPost !== '' ? $observacionesPost : null,
            ]);

            // 4-6. Detalle de la devolución por bien, y marcar el detalle del préstamo como devuelto.
            // No se toca bienes.id_responsable_actual/id_ubicacion_actual/id_asignacion_actual ni
            // detalle_asignacion: la custodia temporal simplemente termina, la asignación permanente
            // nunca cambió durante el préstamo (ver regla en PrestamosController).
            foreach ($detallesValidados as $idDetalle => $info) {
                $detalleDevolucionModel->insertar([
                    'id_devolucion' => $idDevolucion,
                    'id_detalle_prestamo' => $idDetalle,
                    'id_bien' => (int) $info['detalle']['id_bien'],
                    'condicion_devolucion' => $info['condicion_devolucion'],
                    'observaciones' => $info['observaciones'],
                ]);

                $detallePrestamoModel->marcarDevuelto($idDetalle);
            }

            // 12. Estado resultante del préstamo.
            $prestamoModel->actualizarEstado($idPrestamo, $estadoPrestamoNuevo);

            // 17. Bitácora: una sola entrada por operación.
            $descripcionBitacora = sprintf(
                'Se registró la devolución %s del préstamo %s con %d bien(es). Responsable temporal: %s. El préstamo queda %s.',
                $numeroDevolucion,
                $prestamo['numero_prestamo'],
                count($detallesValidados),
                $prestamo['responsable_destino_mostrado'],
                $estadoPrestamoNuevo === 'finalizado' ? 'Finalizado' : 'Parcialmente devuelto'
            );

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_DEVOLUCION',
                modulo: 'Movimientos',
                resultado: 'exitoso',
                descripcion: $descripcionBitacora,
                tablaAfectada: 'devoluciones',
                idRegistroAfectado: $idDevolucion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $prestamoModel->commit();

            setFlash(
                'success',
                'Devolución registrada correctamente',
                $estadoPrestamoNuevo === 'finalizado'
                    ? 'La devolución fue registrada y el préstamo quedó finalizado.'
                    : 'La devolución parcial fue registrada correctamente.'
            );

            header('Location: index.php?modulo=devoluciones&accion=ver&id=' . $idDevolucion);
            exit;
        } catch (Throwable $e) {
            if ($prestamoModel->inTransaction()) {
                $prestamoModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar la devolución: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar la devolución. Verifique los datos e intente nuevamente.';
            }

            $this->view('devoluciones/crear', [
                'prestamosPendientes' => $prestamosPendientes,
                'detallesPorPrestamo' => $detallesPorPrestamo,
                'idPrestamoPreseleccionado' => $idPrestamo,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar devolución',
            ], 'main');
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

        if (str_contains($detalleError, 'uq_devoluciones_numero')) {
            return 'No fue posible generar un número único para la devolución. Intente nuevamente.';
        }

        if (str_contains($detalleError, 'uq_detalle_devolucion_detalle_prestamo')) {
            return 'Uno de los bienes seleccionados ya fue devuelto por otra operación.';
        }

        return null;
    }
}
