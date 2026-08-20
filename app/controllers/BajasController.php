<?php

require_once __DIR__ . '/../core/Controller.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BajasController extends Controller
{
    private const ROLES_GESTION = ['Administrador', 'Operativo'];
    private const ROLES_SOLICITUDES = ['Administrador'];

    // Comprobante institucional de Baja: única plantilla, cargada fresca en cada descarga y NUNCA
    // modificada en disco — mismo criterio que RequisicionesController/PrestamosController/
    // TrasladosController. Coordenadas confirmadas por inspección real del master limpio
    // (storage/templates/bajas.xlsx).
    private const PLANTILLA_ARCHIVO = 'bajas.xlsx';
    private const PLANTILLA_TITULO = 'A8';
    private const PLANTILLA_FECHA = 'B10';
    private const PLANTILLA_SERVICIO = 'B11';
    private const PLANTILLA_FILA_ENCABEZADO_TABLA = 14;
    private const PLANTILLA_FILA_INICIO_TABLA = 15;
    private const PLANTILLA_FILA_FIN_TABLA_BASE = 24;
    private const PLANTILLA_FILA_ENTREGA_NOMBRE = 29;
    private const PLANTILLA_FILA_AUXILIAR_NOMBRE = 34;
    private const PLANTILLA_FILA_VOBO_NOMBRE = 37;
    private const PLANTILLA_FILA_OBSERVACIONES = 39;

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $bajaModel = $this->model('Baja');

        $this->view('bajas/index', [
            'bajas' => $bajaModel->getAll(),
        ]);
    }

    // Bandeja administrativa de solicitudes de Baja — SOLO Administrador (nunca Operativo ni
    // Visualizador, a diferencia del listado normal de index()). No confía únicamente en que el
    // menú de Movimientos oculte el enlace: requireRole() bloquea también el acceso directo por URL.
    // Reutiliza Baja::getAll() (mismo modelo/consulta que index()), solo reordena para priorizar
    // 'pendiente'. La decisión (Aceptar/Rechazar) se toma desde revisar(), no desde aquí.
    public function solicitudes(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_SOLICITUDES);

        $bajaModel = $this->model('Baja');
        $bajas = $bajaModel->getAll();

        usort($bajas, static function (array $a, array $b): int {
            $prioridadA = ($a['estado_baja'] ?? '') === 'pendiente' ? 0 : 1;
            $prioridadB = ($b['estado_baja'] ?? '') === 'pendiente' ? 0 : 1;

            return $prioridadA <=> $prioridadB;
        });

        $this->view('bajas/solicitudes', [
            'bajas' => $bajas,
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $baja = $bajaModel->findById($idBaja);

        if ($baja === false) {
            echo 'Baja no encontrada.';
            return;
        }

        $detalleModel = $this->model('DetalleBaja');

        $this->view('bajas/ver', [
            'baja' => $baja,
            'detalles' => $detalleModel->listarPorBaja($idBaja),
        ]);
    }

    // Detalle en modo administrativo de SOLO LECTURA, exclusivo para el contexto Solicitudes de baja
    // (BajasController::solicitudes()). No procesa POST, no exige CSRF, no ofrece Editar bajo ningún
    // rol ni estado — la vista se lo indica pasando 'origenSolicitudes' => true. Es una acción GET
    // separada de ver() (en vez de un parámetro ?origen=solicitudes) para que la protección de rol
    // quede en el backend de forma explícita y no dependa de un valor manipulable en la URL.
    public function revisar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_SOLICITUDES);

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $baja = $bajaModel->findById($idBaja);

        if ($baja === false) {
            echo 'Baja no encontrada.';
            return;
        }

        $detalleModel = $this->model('DetalleBaja');

        $this->view('bajas/ver', [
            'baja' => $baja,
            'detalles' => $detalleModel->listarPorBaja($idBaja),
            'origenSolicitudes' => true,
        ]);
    }

    // Decisión "Aceptar solicitud" — SOLO Administrador, SOLO POST+CSRF, exige reverificar su propia
    // contraseña (password_verify() contra su password_hash actual; nunca se guarda ni se registra en
    // bitácora). No toca bienes, asignaciones ni Tarjeta — únicamente la cabecera de la propia Baja.
    // "Rechazar solicitud" (rechazar()) todavía NO está implementado: el esquema actual de `bajas` no
    // tiene columnas propias para registrar usuario/fecha/motivo de rechazo (ver auditoría 2026-08-19)
    // y no deben reutilizarse observaciones/justificaciones para simular ese dato.
    public function autorizar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_SOLICITUDES);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $passwordInput = (string) ($_POST['password'] ?? '');

        if ($passwordInput === '') {
            echo 'Debe ingresar su contraseña para aceptar la solicitud.';
            return;
        }

        $usuarioModel = $this->model('Usuario');
        $usuarioActual = $usuarioModel->findActivoConPasswordById((int) $_SESSION['id_usuario']);

        if ($usuarioActual === false || !password_verify($passwordInput, $usuarioActual['password_hash'])) {
            echo 'Contraseña incorrecta. No fue posible aceptar la solicitud.';
            return;
        }

        unset($passwordInput, $usuarioActual);

        $bajaModel = $this->model('Baja');
        $detalleModel = $this->model('DetalleBaja');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $bajaModel->beginTransaction();

            $baja = $bajaModel->findByIdForUpdate($idBaja);

            if ($baja === false) {
                throw new RuntimeException('Baja no encontrada.');
            }

            if ($baja['estado_baja'] !== Baja::ESTADO_PENDIENTE) {
                throw new RuntimeException('Solo se pueden aceptar solicitudes en estado Pendiente.');
            }

            $detalles = $detalleModel->listarPorBaja($idBaja);

            if (empty($detalles)) {
                throw new RuntimeException('No se puede aceptar una solicitud sin bienes.');
            }

            $bajaModel->autorizar($idBaja, (int) $_SESSION['id_usuario']);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'AUTORIZAR_BAJA',
                modulo: 'Bajas',
                resultado: 'exitoso',
                descripcion: 'Se aceptó la baja ' . $baja['numero_baja'] . '.',
                tablaAfectada: 'bajas',
                idRegistroAfectado: $idBaja,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bajaModel->commit();

            header('Location: index.php?modulo=bajas&accion=revisar&id=' . $idBaja);
            exit;
        } catch (Throwable $e) {
            if ($bajaModel->inTransaction()) {
                $bajaModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al aceptar la baja ' . $idBaja . ': ' . $e->getMessage());
                echo 'No fue posible aceptar la solicitud. Intente nuevamente.';
            }
            return;
        }
    }

    // Decisión "Rechazar solicitud" — SOLO Administrador, SOLO POST+CSRF. A diferencia de
    // autorizar(), NO exige contraseña (regla funcional aprobada 2026-08-19: el rechazo solo
    // requiere sesión + confirmación visual). No toca bienes, asignaciones ni Tarjeta — únicamente
    // la cabecera de la propia Baja. Sin motivo: el proceso aprobado no exige justificación de
    // rechazo, por eso no se lee ni se guarda ningún campo de texto adicional.
    public function rechazar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_SOLICITUDES);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        verifyCsrf();

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $bajaModel->beginTransaction();

            $baja = $bajaModel->findByIdForUpdate($idBaja);

            if ($baja === false) {
                throw new RuntimeException('Baja no encontrada.');
            }

            if ($baja['estado_baja'] !== Baja::ESTADO_PENDIENTE) {
                throw new RuntimeException('Solo se pueden rechazar solicitudes en estado Pendiente.');
            }

            $bajaModel->rechazar($idBaja, (int) $_SESSION['id_usuario']);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'RECHAZAR_BAJA',
                modulo: 'Bajas',
                resultado: 'exitoso',
                descripcion: 'Se rechazó la baja ' . $baja['numero_baja'] . '.',
                tablaAfectada: 'bajas',
                idRegistroAfectado: $idBaja,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bajaModel->commit();

            header('Location: index.php?modulo=bajas&accion=revisar&id=' . $idBaja);
            exit;
        } catch (Throwable $e) {
            if ($bajaModel->inTransaction()) {
                $bajaModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al rechazar la baja ' . $idBaja . ': ' . $e->getMessage());
                echo 'No fue posible rechazar la solicitud. Intente nuevamente.';
            }
            return;
        }
    }

    // Decisión "Finalizar baja" — SOLO Administrador, SOLO POST+CSRF, SOLO sobre una Baja
    // 'autorizada'. Aquí sí ocurre el impacto permanente: retiro de asignación + estado Baja + sin
    // responsable/asignación + ubicación = Bodega destino, para CADA bien de la solicitud, de forma
    // atómica (si uno falla, rollback completo — ningún bien queda finalizado a medias). No exige
    // contraseña adicional (esa regla es exclusiva de Autorizar). No genera Tarjeta ni Excel — el
    // evento BAJA_SALIDA se resuelve más adelante, solo cuando el usuario genere/reimprima la
    // Tarjeta del responsable (ver TarjetasController::resolverSalidaDetalle()).
    // "Finalizar" NO es una decisión administrativa: la Administradora ya terminó su participación
    // al Aceptar/Rechazar. Finalizar corresponde exclusivamente a quien registró la solicitud
    // originalmente (id_usuario_registra) — puede ser Operativo o Administrador, pero debe ser LA
    // MISMA persona, sin importar su rol. Por eso se exige rol de gestión (Administrador u
    // Operativo) Y coincidencia exacta de usuario; ninguna de las dos condiciones basta sola
    // (revisión funcional 2026-08-19).
    public function finalizar(): void
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

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $detalleModel = $this->model('DetalleBaja');
        $bienModel = $this->model('Bien');
        $asignacionModel = $this->model('Asignacion');
        $ubicacionModel = $this->model('Ubicacion');
        $responsableModel = $this->model('Responsable');
        $estadoBienModel = $this->model('EstadoBien');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $bajaModel->beginTransaction();

            $baja = $bajaModel->findByIdForUpdate($idBaja);

            if ($baja === false) {
                throw new RuntimeException('Baja no encontrada.');
            }

            if ($baja['estado_baja'] !== Baja::ESTADO_AUTORIZADA) {
                throw new RuntimeException('Solo se pueden finalizar bajas en estado Autorizada.');
            }

            // No confiar en que el botón esté oculto: revalidar la autoría dentro de la transacción,
            // con la fila ya bloqueada. No importa si el usuario actual es Administrador — si no es
            // quien registró esta Baja, no puede finalizarla.
            if ((int) $baja['id_usuario_registra'] !== (int) $_SESSION['id_usuario']) {
                throw new RuntimeException('Solo el usuario que registró esta baja puede finalizarla.');
            }

            $idUbicacionBodegaDestino = (int) $baja['id_ubicacion_bodega_destino'];
            $bodega = $ubicacionModel->findActivaByIdForUpdate($idUbicacionBodegaDestino);

            if ($bodega === false || $bodega['tipo_ubicacion'] !== 'Bodega') {
                throw new RuntimeException('La Bodega destino de esta baja ya no es válida. No es posible finalizar.');
            }

            $detalles = $detalleModel->listarPorBajaForUpdate($idBaja);

            if (empty($detalles)) {
                throw new RuntimeException('Esta baja no tiene bienes registrados.');
            }

            $idResponsableDescarga = (int) $baja['id_responsable_descarga'];
            $responsable = $responsableModel->findById($idResponsableDescarga);

            $estadoBajaCatalogo = $estadoBienModel->findByNombre('Baja');

            if ($estadoBajaCatalogo === false) {
                throw new RuntimeException('El catálogo de estados de bien no tiene configurado el estado "Baja".');
            }

            $idEstadoBienBaja = (int) $estadoBajaCatalogo['id_estado_bien'];
            $fechaBaja = date('Y-m-d');

            foreach ($detalles as $detalle) {
                $idBien = (int) $detalle['id_bien'];
                $etiqueta = $detalle['codigo_interno_mostrado'];

                $bien = $bienModel->findParaFinalizarBajaForUpdate(
                    $idBien,
                    $idResponsableDescarga,
                    (int) $detalle['id_ubicacion_anterior'],
                    $idBaja
                );

                if ($bien === false) {
                    throw new RuntimeException(
                        'El bien ' . $etiqueta . ' ya no se encuentra en las condiciones esperadas '
                        . '(responsable, ubicación, estado o disponibilidad cambiaron) y no se puede finalizar.'
                    );
                }

                $idAsignacionActual = (int) $bien['id_asignacion_actual'];

                $asignacionActual = $asignacionModel->findByIdForUpdate($idAsignacionActual);

                if ($asignacionActual === false) {
                    throw new RuntimeException('No se pudo verificar la asignación actual del bien ' . $etiqueta . '.');
                }

                $detalleAsignacionActivo = $asignacionModel->findDetalleActivoPorBienForUpdate($idAsignacionActual, $idBien);

                if ($detalleAsignacionActivo === false) {
                    throw new RuntimeException('El bien ' . $etiqueta . ' no tiene un detalle de asignación activo identificable.');
                }

                $asignacionModel->retirarBien((int) $detalleAsignacionActivo['id_detalle_asignacion'], $fechaBaja);

                $actualizado = $bienModel->finalizarBaja($idBien, $idAsignacionActual, $idEstadoBienBaja, $idUbicacionBodegaDestino);

                if (!$actualizado) {
                    throw new RuntimeException(
                        'No se pudo actualizar el bien ' . $etiqueta . ' porque su información cambió durante el proceso. Intente nuevamente.'
                    );
                }
            }

            $bajaModel->finalizar($idBaja, $fechaBaja);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'FINALIZAR_BAJA',
                modulo: 'Bajas',
                resultado: 'exitoso',
                descripcion: sprintf(
                    'Se finalizó la baja %s con %d bien(es), Bodega destino %s, responsable anterior %s.',
                    $baja['numero_baja'],
                    count($detalles),
                    $bodega['nombre_ubicacion'],
                    $responsable !== false ? $responsable['nombre_completo'] : ('id ' . $idResponsableDescarga)
                ),
                tablaAfectada: 'bajas',
                idRegistroAfectado: $idBaja,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bajaModel->commit();

            // Redirige al Ver NORMAL (no a revisar()): Finalizar ya no pertenece al flujo
            // administrativo de Solicitudes, sino al flujo normal de Baja del propio solicitante.
            header('Location: index.php?modulo=bajas&accion=ver&id=' . $idBaja);
            exit;
        } catch (Throwable $e) {
            if ($bajaModel->inTransaction()) {
                $bajaModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                echo $e->getMessage();
            } else {
                error_log('Error al finalizar la baja ' . $idBaja . ': ' . $e->getMessage());
                echo 'No fue posible finalizar la baja. Intente nuevamente.';
            }
            return;
        }
    }

    // Comprobante de solo lectura: mismos permisos que ver() (solo sesión activa, sin requireRole),
    // porque descargar no modifica nada — "si puede ver() legítimamente esta Baja, puede descargar
    // su comprobante" (regla funcional 2026-08-19). Solo se permite si estado_baja='finalizada': el
    // documento representa un retiro físico ya ejecutado, nunca una solicitud pendiente/autorizada/
    // rechazada.
    public function descargarComprobante(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $baja = $bajaModel->findById($idBaja);

        if ($baja === false) {
            echo 'Baja no encontrada.';
            return;
        }

        if ($baja['estado_baja'] !== Baja::ESTADO_FINALIZADA) {
            echo 'El comprobante solo puede descargarse cuando la baja está Finalizada.';
            return;
        }

        $detalleModel = $this->model('DetalleBaja');
        $detalles = $detalleModel->listarPorBaja($idBaja);

        if (empty($detalles)) {
            echo 'Esta baja no tiene bienes registrados.';
            return;
        }

        $ubicacionModel = $this->model('Ubicacion');
        $responsableModel = $this->model('Responsable');

        // Vo. Bo. jefe de Inventarios: resuelto institucionalmente en el momento de generar, NUNCA
        // desde id_usuario_autoriza (regla funcional 2026-08-19, aunque hoy puedan coincidir en la
        // misma persona). Mismo par de métodos que ya usa RequisicionesController/PrestamosController
        // para su "Autoriza". Si no se resuelve de forma única, se rechaza la generación sin inventar
        // ningún nombre.
        $ubicacionInventarios = $ubicacionModel->findInventariosInstitucional();

        if ($ubicacionInventarios === false) {
            echo 'No fue posible generar el comprobante: la ubicación institucional de Inventarios no está configurada.';
            return;
        }

        $responsableInventarios = $responsableModel->findActivoPorUbicacion((int) $ubicacionInventarios['id_ubicacion']);

        if ($responsableInventarios === false) {
            echo 'No fue posible generar el comprobante: no se identificó de forma única a la Encargada de Inventarios.';
            return;
        }

        try {
            $spreadsheet = $this->generarWorkbookComprobante($baja, $detalles, $responsableInventarios['nombre_completo']);
        } catch (Throwable $e) {
            error_log('Error al generar el comprobante de la baja ' . $idBaja . ': ' . $e->getMessage());
            echo 'No fue posible generar el comprobante de baja.';
            return;
        }

        $nombreArchivo = 'Baja_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $baja['numero_baja']) . '.xlsx';

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
     * Reconstruye el comprobante de Baja como workbook XLSX a partir de la plantilla maestra ya
     * limpia (storage/templates/bajas.xlsx), SIN modificarla: se carga en memoria y se retorna el
     * objeto Spreadsheet ya listo para escribir. Misma filosofía que
     * RequisicionesController::generarWorkbookConstancia() / PrestamosController::
     * generarWorkbookConstanciaPrestamo(). Usa exclusivamente snapshots de bajas/detalle_baja —
     * nunca datos actuales de bienes/responsables/ubicaciones. Título único fijo porque una misma
     * Baja puede mezclar varios tipos por bien (Mal estado, Inservible, Obsoleto...) y no existe un
     * tipo de cabecera del que derivarlo (revisión funcional 2026-08-19). No imprime Bodega destino,
     * SICOIN, marca, modelo, serie, valor, tipo de baja, justificación, numero_baja, numero_acta,
     * usuario_autoriza ni usuario_rechaza — la plantilla real no contempla esos campos.
     */
    private function generarWorkbookComprobante(array $baja, array $detalles, string $nombreVoBo): Spreadsheet
    {
        $rutaPlantilla = dirname(__DIR__, 2) . '/storage/templates/' . self::PLANTILLA_ARCHIVO;

        if (!is_file($rutaPlantilla)) {
            throw new RuntimeException('No se encontró la plantilla maestra del comprobante de baja.');
        }

        $spreadsheet = IOFactory::load($rutaPlantilla);
        $hoja = $spreadsheet->getSheet(0);

        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(
            self::PLANTILLA_FILA_ENCABEZADO_TABLA,
            self::PLANTILLA_FILA_ENCABEZADO_TABLA
        );

        // Campos por ENCIMA de la tabla (filas 1-14): posición fija, no se desplazan al insertar filas.
        $hoja->setCellValueExplicit(self::PLANTILLA_TITULO, 'Retiro de bienes institucionales.', DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(
            self::PLANTILLA_FECHA,
            $this->formatearFechaLarga((string) $baja['fecha_baja']),
            DataType::TYPE_STRING
        );

        // Servicio: snapshot histórico de detalle_baja.id_ubicacion_anterior (todos los bienes de una
        // misma Baja comparten responsable/servicio, así que el primer detalle basta), NUNCA el join
        // en vivo de la ubicación actual del responsable ni la Bodega destino (revisión funcional
        // 2026-08-19). Con fallback al dato en vivo solo si el snapshot quedara NULL (ubicación
        // histórica eliminada), para no dejar la celda vacía.
        $servicioMostrado = $detalles[0]['ubicacion_anterior'] ?? $baja['ubicacion_responsable_descarga'] ?? '';
        $hoja->setCellValueExplicit(self::PLANTILLA_SERVICIO, (string) $servicioMostrado, DataType::TYPE_STRING);

        // Tabla de bienes: la plantilla trae 10 filas base (15-24). Si hay más bienes, se insertan
        // filas adicionales justo después de la última fila base (antes de la sección de firmas, que
        // PhpSpreadsheet desplaza hacia abajo junto con su contenido y estilos) — mismo criterio que
        // TrasladosController/PrestamosController/RequisicionesController. Solo B:F se fusiona por
        // fila (Descripción); A y G quedan sin fusionar (No. y No. de Bien).
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

                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $columna) {
                    $hoja->duplicateStyle($hoja->getStyle($columna . $filaFinBase), $columna . $filaNueva);
                }

                $hoja->getRowDimension($filaNueva)->setRowHeight($hoja->getRowDimension($filaFinBase)->getRowHeight());
                $hoja->mergeCells('B' . $filaNueva . ':F' . $filaNueva);
            }
        }

        $fila = $filaInicio;
        $numero = 1;

        // No. de Bien = codigo_interno_mostrado (confirmado institucionalmente, ver
        // DetalleBaja::insertar()) — nunca SICOIN. Descripción SIN concatenar marca/modelo/serie/
        // tipo/justificación: la plantilla real de Bajas no contempla esas columnas (a diferencia de
        // Requisiciones/Préstamos), esos datos permanecen solo en el sistema.
        foreach ($detalles as $detalle) {
            $hoja->setCellValue('A' . $fila, $numero);
            $hoja->setCellValueExplicit('B' . $fila, (string) $detalle['descripcion_mostrada'], DataType::TYPE_STRING);
            $hoja->setCellValueExplicit('G' . $fila, (string) $detalle['codigo_interno_mostrado'], DataType::TYPE_STRING);

            $fila++;
            $numero++;
        }

        // Limpiar filas base de ejemplo que no se usaron.
        $filaFinReal = $filaFinBase + $filasExtra;

        for ($filaLimpiar = $fila; $filaLimpiar <= $filaFinReal; $filaLimpiar++) {
            $hoja->setCellValue('A' . $filaLimpiar, null);
            $hoja->setCellValue('B' . $filaLimpiar, null);
            $hoja->setCellValue('G' . $filaLimpiar, null);
        }

        // Campos por DEBAJO de la tabla: PhpSpreadsheet ya desplazó fila/estilo/merge originales al
        // insertar filas extra: se escribe el VALOR en la posición YA DESPLAZADA (original + filasExtra),
        // nunca en la posición original de la plantilla si hubo inserción.
        $hoja->setCellValueExplicit(
            'B' . (self::PLANTILLA_FILA_ENTREGA_NOMBRE + $filasExtra),
            (string) $baja['responsable_descarga'],
            DataType::TYPE_STRING
        );

        $hoja->setCellValueExplicit(
            'D' . (self::PLANTILLA_FILA_AUXILIAR_NOMBRE + $filasExtra),
            (string) $baja['auxiliar_encargado'],
            DataType::TYPE_STRING
        );

        $hoja->setCellValueExplicit(
            'D' . (self::PLANTILLA_FILA_VOBO_NOMBRE + $filasExtra),
            $nombreVoBo,
            DataType::TYPE_STRING
        );

        $observaciones = trim((string) ($baja['observaciones'] ?? ''));

        if ($observaciones !== '') {
            $hoja->setCellValueExplicit(
                'B' . (self::PLANTILLA_FILA_OBSERVACIONES + $filasExtra),
                $observaciones,
                DataType::TYPE_STRING
            );
        }

        return $spreadsheet;
    }

    /**
     * "16 de agosto de 2026" — controlado explícitamente en español, sin depender de
     * setlocale()/LC_TIME del sistema operativo. Misma implementación que
     * TrasladosController/PrestamosController/RequisicionesController::formatearFechaLarga().
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

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_GESTION);

        $usuarioModelReferencia = $this->model('Usuario');
        $usuarioActual = $usuarioModelReferencia->findById((int) $_SESSION['id_usuario']);
        $nombreAuxiliarActual = $usuarioActual !== false ? $usuarioActual['nombre_completo'] : '';

        [$responsables, $tiposBaja, $bodegas] = $this->cargarDatosReferencia();
        $bienesPorResponsable = $this->construirBienesPorResponsable($responsables, 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('bajas/crear', [
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'nombreAuxiliarActual' => $nombreAuxiliarActual,
                'bienesPorResponsable' => $bienesPorResponsable,
                'error' => null,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        [$datosFormulario, $idUbicacionBodegaDestino, $idResponsableDescarga, $observaciones, $bienesSeleccionadosPost] = $this->leerFormulario();

        $error = $this->validarFormulario($idUbicacionBodegaDestino, $idResponsableDescarga, $bienesSeleccionadosPost);

        $imagenesPorBien = [];

        if ($error === null) {
            try {
                foreach (array_keys($bienesSeleccionadosPost) as $idBien) {
                    $archivo = $this->extraerArchivoIndexado($_FILES['imagen_bien'] ?? null, $idBien);
                    $imagenesPorBien[$idBien] = guardarImagenBien($archivo);
                }
            } catch (RuntimeException $errorArchivo) {
                $error = $errorArchivo->getMessage();
            }
        }

        if ($error !== null) {
            $this->view('bajas/crear', [
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'nombreAuxiliarActual' => $nombreAuxiliarActual,
                'bienesPorResponsable' => $bienesPorResponsable,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $bajaModel = $this->model('Baja');
        $detalleModel = $this->model('DetalleBaja');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $usuarioModel = $this->model('Usuario');
        $tipoBajaModel = $this->model('TipoBaja');
        $bienModel = $this->model('Bien');
        $estadoBienModel = $this->model('EstadoBien');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $bajaModel->beginTransaction();

            // El Auxiliar de Inventarios NUNCA se toma del formulario: es, por definición, quien
            // ejecuta el registro. Se revalida que el usuario autenticado siga activo antes de guardar.
            $idAuxiliarEncargado = (int) $_SESSION['id_usuario'];
            $usuarioRegistraActivo = $usuarioModel->findActivoByIdForUpdate($idAuxiliarEncargado);

            if ($usuarioRegistraActivo === false) {
                throw new RuntimeException('Su usuario ya no está activo. No es posible registrar la baja.');
            }

            $responsable = $responsableModel->findActivoByIdForUpdate($idResponsableDescarga);

            if ($responsable === false) {
                throw new RuntimeException('El responsable del área no está activo.');
            }

            $bodega = $ubicacionModel->findActivaByIdForUpdate($idUbicacionBodegaDestino);

            if ($bodega === false || $bodega['tipo_ubicacion'] !== 'Bodega') {
                throw new RuntimeException('La Bodega destino seleccionada no es válida.');
            }

            $estadoBajaCatalogo = $estadoBienModel->findByNombre('Baja');

            if ($estadoBajaCatalogo === false) {
                throw new RuntimeException('El catálogo de estados de bien no tiene configurado el estado "Baja".');
            }

            $idEstadoBienBaja = (int) $estadoBajaCatalogo['id_estado_bien'];

            // Cada bien valida su propio tipo de baja y justificación aquí dentro — ver
            // validarBienesForUpdate(). Si UNO falla, toda la solicitud se rechaza (rollback completo).
            $bienesValidados = $this->validarBienesForUpdate($bienesSeleccionadosPost, $bienModel, $detalleModel, $tipoBajaModel, $idResponsableDescarga, 0);

            $numeroBaja = $bajaModel->generarSiguienteNumero((int) date('Y'));

            $idBaja = $bajaModel->crear([
                'numero_baja' => $numeroBaja,
                'id_ubicacion_bodega_destino' => $idUbicacionBodegaDestino,
                'id_responsable_descarga' => $idResponsableDescarga,
                'id_auxiliar_encargado' => $idAuxiliarEncargado,
                'id_usuario_registra' => $idAuxiliarEncargado,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            foreach ($bienesValidados as $idBien => $bien) {
                $detalleModel->insertar([
                    'id_baja' => $idBaja,
                    'id_bien' => $idBien,
                    'codigo_interno_mostrado' => $bien['codigo_interno'],
                    'codigo_sicoin_mostrado' => $bien['codigo_sicoin'] !== '' ? $bien['codigo_sicoin'] : null,
                    'descripcion_mostrada' => $bien['descripcion'],
                    'marca_mostrada' => $bien['marca'],
                    'modelo_mostrado' => $bien['modelo'],
                    'serie_mostrada' => $bien['serie'],
                    'valor_mostrado' => $bien['valor'],
                    'id_tipo_baja' => $bien['id_tipo_baja'],
                    'justificacion' => $bien['justificacion'],
                    'id_responsable_anterior' => $bien['id_responsable_actual'],
                    'id_ubicacion_anterior' => $bien['id_ubicacion_actual'],
                    'id_estado_bien_anterior' => $bien['id_estado_bien'],
                    'id_estado_bien_nuevo' => $idEstadoBienBaja,
                    'imagen_bien' => $imagenesPorBien[$idBien] ?? null,
                    'observaciones' => null,
                ]);
            }

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_BAJA',
                modulo: 'Bajas',
                resultado: 'exitoso',
                descripcion: sprintf(
                    'Se registró la baja %s para %s con %d bien(es).',
                    $numeroBaja,
                    $responsable['nombre_completo'],
                    count($bienesValidados)
                ),
                tablaAfectada: 'bajas',
                idRegistroAfectado: $idBaja,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bajaModel->commit();

            header('Location: index.php?modulo=bajas&accion=ver&id=' . $idBaja);
            exit;
        } catch (Throwable $e) {
            if ($bajaModel->inTransaction()) {
                $bajaModel->rollBack();
            }

            // Las imágenes ya se guardaron en disco antes de la transacción: si la BD falla, se
            // eliminan para no dejar huérfanas sin ningún registro que las referencie.
            foreach ($imagenesPorBien as $ruta) {
                eliminarImagenBien($ruta);
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar la baja: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar la baja. Verifique los datos e intente nuevamente.';
            }

            $bienesPorResponsable = $this->construirBienesPorResponsable($responsables, 0);

            $this->view('bajas/crear', [
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'nombreAuxiliarActual' => $nombreAuxiliarActual,
                'bienesPorResponsable' => $bienesPorResponsable,
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

        $idBaja = (int) ($_GET['id'] ?? 0);

        if ($idBaja <= 0) {
            echo 'Baja no válida.';
            return;
        }

        $bajaModel = $this->model('Baja');
        $baja = $bajaModel->findById($idBaja);

        if ($baja === false) {
            echo 'Baja no encontrada.';
            return;
        }

        if ($baja['estado_baja'] !== 'pendiente') {
            echo 'Solo se pueden modificar bajas en estado Pendiente.';
            return;
        }

        [$responsables, $tiposBaja, $bodegas] = $this->cargarDatosReferencia();
        $bienesPorResponsable = $this->construirBienesPorResponsable($responsables, $idBaja);

        $detalleModel = $this->model('DetalleBaja');
        $detallesActuales = $detalleModel->listarPorBaja($idBaja);

        $bienesFormularioBase = [];

        foreach ($detallesActuales as $detalle) {
            $bienesFormularioBase[(int) $detalle['id_bien']] = [
                'id_tipo_baja' => (int) $detalle['id_tipo_baja'],
                'justificacion' => $detalle['justificacion'],
            ];
        }

        $datosFormularioBase = [
            'id_ubicacion_bodega_destino' => (int) $baja['id_ubicacion_bodega_destino'],
            'id_responsable_descarga' => (int) $baja['id_responsable_descarga'],
            'observaciones' => $baja['observaciones'] ?? '',
            'bienes' => $bienesFormularioBase,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('bajas/editar', [
                'baja' => $baja,
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'bienesPorResponsable' => $bienesPorResponsable,
                'detallesActuales' => $detallesActuales,
                'error' => null,
                'datosFormulario' => $datosFormularioBase,
            ]);
            return;
        }

        verifyCsrf();

        [$datosFormulario, $idUbicacionBodegaDestino, $idResponsableDescarga, $observaciones, $bienesSeleccionadosPost] = $this->leerFormulario();

        $error = $this->validarFormulario($idUbicacionBodegaDestino, $idResponsableDescarga, $bienesSeleccionadosPost);

        $imagenesNuevasPorBien = [];

        if ($error === null) {
            try {
                foreach (array_keys($bienesSeleccionadosPost) as $idBien) {
                    $archivo = $this->extraerArchivoIndexado($_FILES['imagen_bien'] ?? null, $idBien);
                    $rutaImagen = guardarImagenBien($archivo);

                    if ($rutaImagen !== null) {
                        $imagenesNuevasPorBien[$idBien] = $rutaImagen;
                    }
                }
            } catch (RuntimeException $errorArchivo) {
                $error = $errorArchivo->getMessage();
            }
        }

        if ($error !== null) {
            $this->view('bajas/editar', [
                'baja' => $baja,
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'bienesPorResponsable' => $bienesPorResponsable,
                'detallesActuales' => $detallesActuales,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $tipoBajaModel = $this->model('TipoBaja');
        $bienModel = $this->model('Bien');
        $estadoBienModel = $this->model('EstadoBien');
        $bitacoraModel = $this->model('Bitacora');

        $archivosAEliminarSiExito = [];

        try {
            $bajaModel->beginTransaction();

            $bajaBloqueada = $bajaModel->findByIdForUpdate($idBaja);

            if ($bajaBloqueada === false) {
                throw new RuntimeException('Baja no encontrada.');
            }

            if ($bajaBloqueada['estado_baja'] !== 'pendiente') {
                throw new RuntimeException('Solo se pueden modificar bajas en estado Pendiente.');
            }

            $responsable = $responsableModel->findActivoByIdForUpdate($idResponsableDescarga);

            if ($responsable === false) {
                throw new RuntimeException('El responsable del área no está activo.');
            }

            $bodega = $ubicacionModel->findActivaByIdForUpdate($idUbicacionBodegaDestino);

            if ($bodega === false || $bodega['tipo_ubicacion'] !== 'Bodega') {
                throw new RuntimeException('La Bodega destino seleccionada no es válida.');
            }

            $estadoBajaCatalogo = $estadoBienModel->findByNombre('Baja');

            if ($estadoBajaCatalogo === false) {
                throw new RuntimeException('El catálogo de estados de bien no tiene configurado el estado "Baja".');
            }

            $idEstadoBienBaja = (int) $estadoBajaCatalogo['id_estado_bien'];

            // Diff contra el estado real en BD, no contra lo que mostraba el formulario. Si cambió el
            // responsable, los bienes del responsable anterior que ya no vengan marcados simplemente no
            // aparecen en $bienesSeleccionadosPost y se eliminan — "no conservar detalles incompatibles
            // silenciosamente".
            $detallesExistentes = $detalleModel->listarPorBaja($idBaja);
            $idsExistentesPorBien = [];

            foreach ($detallesExistentes as $detalle) {
                $idsExistentesPorBien[(int) $detalle['id_bien']] = $detalle;
            }

            $idsExistentes = array_keys($idsExistentesPorBien);
            $idsSubmit = array_keys($bienesSeleccionadosPost);
            $idsAEliminar = array_diff($idsExistentes, $idsSubmit);
            $idsAAgregar = array_diff($idsSubmit, $idsExistentes);
            $idsAConservar = array_intersect($idsSubmit, $idsExistentes);

            $bienesNuevosPost = [];

            foreach ($idsAAgregar as $idBien) {
                $bienesNuevosPost[$idBien] = $bienesSeleccionadosPost[$idBien];
            }

            $bienesNuevosValidados = empty($bienesNuevosPost)
                ? []
                : $this->validarBienesForUpdate($bienesNuevosPost, $bienModel, $detalleModel, $tipoBajaModel, $idResponsableDescarga, $idBaja);

            // Bienes conservados: revalidar elegibilidad + tipo/justificación enviados (pueden haber
            // cambiado en este mismo envío).
            foreach ($idsAConservar as $idBien) {
                if ($bienModel->findElegibleParaBajaForUpdate($idBien, $idResponsableDescarga, $idBaja) === false) {
                    throw new RuntimeException(
                        'El bien ' . $idsExistentesPorBien[$idBien]['codigo_interno_mostrado']
                        . ' ya no es elegible para esta Baja (responsable, asignación o disponibilidad cambiaron) y debe quitarse.'
                    );
                }

                $tipoBajaConservado = $tipoBajaModel->findActivoByIdForUpdate($bienesSeleccionadosPost[$idBien]['id_tipo_baja']);

                if ($tipoBajaConservado === false) {
                    throw new RuntimeException(
                        'El tipo de baja indicado para el bien ' . $idsExistentesPorBien[$idBien]['codigo_interno_mostrado']
                        . ' no es válido o no está activo.'
                    );
                }

                if ($bienesSeleccionadosPost[$idBien]['justificacion'] === '') {
                    throw new RuntimeException('Debe indicar la justificación del bien ' . $idsExistentesPorBien[$idBien]['codigo_interno_mostrado'] . '.');
                }
            }

            foreach ($idsAEliminar as $idBien) {
                $detalle = $idsExistentesPorBien[$idBien];
                $detalleModel->eliminar((int) $detalle['id_detalle_baja']);

                if (!empty($detalle['imagen_bien'])) {
                    $archivosAEliminarSiExito[] = $detalle['imagen_bien'];
                }
            }

            foreach ($bienesNuevosValidados as $idBien => $bien) {
                $detalleModel->insertar([
                    'id_baja' => $idBaja,
                    'id_bien' => $idBien,
                    'codigo_interno_mostrado' => $bien['codigo_interno'],
                    'codigo_sicoin_mostrado' => $bien['codigo_sicoin'] !== '' ? $bien['codigo_sicoin'] : null,
                    'descripcion_mostrada' => $bien['descripcion'],
                    'marca_mostrada' => $bien['marca'],
                    'modelo_mostrado' => $bien['modelo'],
                    'serie_mostrada' => $bien['serie'],
                    'valor_mostrado' => $bien['valor'],
                    'id_tipo_baja' => $bien['id_tipo_baja'],
                    'justificacion' => $bien['justificacion'],
                    'id_responsable_anterior' => $bien['id_responsable_actual'],
                    'id_ubicacion_anterior' => $bien['id_ubicacion_actual'],
                    'id_estado_bien_anterior' => $bien['id_estado_bien'],
                    'id_estado_bien_nuevo' => $idEstadoBienBaja,
                    'imagen_bien' => $imagenesNuevasPorBien[$idBien] ?? null,
                    'observaciones' => null,
                ]);
            }

            // Bienes conservados: siempre se actualizan tipo/justificación (pudieron cambiar), y la
            // imagen solo si se envió un archivo nuevo en este envío.
            foreach ($idsAConservar as $idBien) {
                $detalle = $idsExistentesPorBien[$idBien];

                $detalleModel->actualizarTipoJustificacion(
                    (int) $detalle['id_detalle_baja'],
                    $bienesSeleccionadosPost[$idBien]['id_tipo_baja'],
                    $bienesSeleccionadosPost[$idBien]['justificacion']
                );

                if (isset($imagenesNuevasPorBien[$idBien])) {
                    $detalleModel->actualizarImagen((int) $detalle['id_detalle_baja'], $imagenesNuevasPorBien[$idBien]);

                    if (!empty($detalle['imagen_bien'])) {
                        $archivosAEliminarSiExito[] = $detalle['imagen_bien'];
                    }
                }
            }

            $bajaModel->actualizarCabecera($idBaja, [
                'id_ubicacion_bodega_destino' => $idUbicacionBodegaDestino,
                'id_responsable_descarga' => $idResponsableDescarga,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'MODIFICAR_BAJA',
                modulo: 'Bajas',
                resultado: 'exitoso',
                descripcion: 'Se modificó la baja ' . $bajaBloqueada['numero_baja'] . '.',
                tablaAfectada: 'bajas',
                idRegistroAfectado: $idBaja,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bajaModel->commit();

            foreach ($archivosAEliminarSiExito as $ruta) {
                eliminarImagenBien($ruta);
            }

            header('Location: index.php?modulo=bajas&accion=ver&id=' . $idBaja);
            exit;
        } catch (Throwable $e) {
            if ($bajaModel->inTransaction()) {
                $bajaModel->rollBack();
            }

            foreach ($imagenesNuevasPorBien as $ruta) {
                eliminarImagenBien($ruta);
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al modificar la baja ' . $idBaja . ': ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible modificar la baja. Verifique los datos e intente nuevamente.';
            }

            $bienesPorResponsable = $this->construirBienesPorResponsable($responsables, $idBaja);

            $this->view('bajas/editar', [
                'baja' => $baja,
                'responsables' => $responsables,
                'tiposBaja' => $tiposBaja,
                'bodegas' => $bodegas,
                'bienesPorResponsable' => $bienesPorResponsable,
                'detallesActuales' => $detallesActuales,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    /**
     * @return array{0: array, 1: array, 2: array}
     */
    private function cargarDatosReferencia(): array
    {
        $responsableModel = $this->model('Responsable');
        $tipoBajaModel = $this->model('TipoBaja');
        $ubicacionModel = $this->model('Ubicacion');

        return [
            $responsableModel->getActivosConBienesAsignados(),
            $tipoBajaModel->getActivos(),
            $ubicacionModel->getBodegasActivas(),
        ];
    }

    /**
     * Precalcula, para cada responsable, sus bienes elegibles para Baja — mismo patrón que
     * PrestamosController::crear() con $bienesPorResponsable, para alternar el listado por JS sin
     * recargar la página. $idBajaExcluir permite reutilizarla en editar() (ver Bien::
     * getElegiblesParaBajaPorResponsable()). El tipo de baja y la justificación NO se precalculan
     * aquí: son independientes por bien y se capturan en el propio formulario al marcarlo.
     */
    private function construirBienesPorResponsable(array $responsables, int $idBajaExcluir): array
    {
        $bienModel = $this->model('Bien');
        $bienesPorResponsable = [];

        $mapearBien = static function (array $bien): array {
            $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));
            $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

            return [
                'id_bien' => (int) $bien['id_bien'],
                'codigo_interno' => $bien['codigo_interno'],
                'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
                'descripcion' => $bien['descripcion'],
                'marca' => $bien['marca'],
                'modelo' => $bien['modelo'],
                'serie' => $bien['serie'],
                'condicion_bien' => $bien['condicion_bien'],
                'valor' => $valor,
                'ubicacion_actual' => $bien['nombre_ubicacion'],
            ];
        };

        foreach ($responsables as $responsable) {
            $idResponsable = (int) $responsable['id_responsable'];
            $bienesPorResponsable[$idResponsable] = array_map(
                $mapearBien,
                $bienModel->getElegiblesParaBajaPorResponsable($idResponsable, $idBajaExcluir)
            );
        }

        return $bienesPorResponsable;
    }

    /**
     * Lee los datos generales (Bodega destino, responsable, observaciones) y la estructura por-bien
     * enviada como bienes[idBien][seleccionado|id_tipo_baja|justificacion]. Solo se consideran
     * "seleccionados" los bienes con seleccionado === '1' — el resto del sub-array se ignora aunque
     * llegue manipulado en el POST.
     *
     * @return array{0: array, 1: int, 2: int, 3: string, 4: array<int, array{id_tipo_baja: int, justificacion: string}>}
     */
    private function leerFormulario(): array
    {
        $idUbicacionBodegaDestino = (int) ($_POST['id_ubicacion_bodega_destino'] ?? 0);
        $idResponsableDescarga = (int) ($_POST['id_responsable_descarga'] ?? 0);
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

        $bienesPost = is_array($_POST['bienes'] ?? null) ? $_POST['bienes'] : [];
        $bienesSeleccionados = [];

        foreach ($bienesPost as $idBienRaw => $datosBien) {
            $idBien = (int) $idBienRaw;

            if ($idBien <= 0 || !is_array($datosBien)) {
                continue;
            }

            if (($datosBien['seleccionado'] ?? '') !== '1') {
                continue;
            }

            $bienesSeleccionados[$idBien] = [
                'id_tipo_baja' => (int) ($datosBien['id_tipo_baja'] ?? 0),
                'justificacion' => trim((string) ($datosBien['justificacion'] ?? '')),
            ];
        }

        $datosFormulario = [
            'id_ubicacion_bodega_destino' => $idUbicacionBodegaDestino,
            'id_responsable_descarga' => $idResponsableDescarga,
            'observaciones' => $observaciones,
            'bienes' => $bienesSeleccionados,
        ];

        return [
            $datosFormulario,
            $idUbicacionBodegaDestino,
            $idResponsableDescarga,
            $observaciones,
            $bienesSeleccionados,
        ];
    }

    /**
     * @param array<int, array{id_tipo_baja: int, justificacion: string}> $bienesSeleccionados
     */
    private function validarFormulario(int $idUbicacionBodegaDestino, int $idResponsableDescarga, array $bienesSeleccionados): ?string
    {
        if ($idUbicacionBodegaDestino <= 0) {
            return 'Debe seleccionar la Bodega destino.';
        }

        if ($idResponsableDescarga <= 0) {
            return 'Debe seleccionar el responsable del área.';
        }

        if (empty($bienesSeleccionados)) {
            return 'Debe seleccionar al menos un bien.';
        }

        foreach ($bienesSeleccionados as $datosBien) {
            if ($datosBien['id_tipo_baja'] <= 0) {
                return 'Debe indicar el tipo de baja de todos los bienes seleccionados.';
            }

            if ($datosBien['justificacion'] === '') {
                return 'Debe indicar la justificación de todos los bienes seleccionados.';
            }
        }

        return null;
    }

    /**
     * Valida y bloquea (FOR UPDATE) cada bien seleccionado contra el responsable indicado, su propio
     * tipo de baja y justificación, y confirma que no quedó atrapado en otra Baja vigente creada en
     * paralelo. Debe ejecutarse dentro de una transacción activa. Si CUALQUIER bien falla, lanza
     * RuntimeException y el llamador debe hacer rollback completo — no se registra nada parcialmente.
     *
     * @param array<int, array{id_tipo_baja: int, justificacion: string}> $bienesSeleccionados
     * @return array<int, array>
     */
    private function validarBienesForUpdate(
        array $bienesSeleccionados,
        Bien $bienModel,
        DetalleBaja $detalleModel,
        TipoBaja $tipoBajaModel,
        int $idResponsableDescarga,
        int $idBajaExcluir
    ): array {
        $bienesValidados = [];

        foreach ($bienesSeleccionados as $idBien => $datosBien) {
            $bien = $bienModel->findElegibleParaBajaForUpdate($idBien, $idResponsableDescarga, $idBajaExcluir);

            if ($bien === false) {
                throw new RuntimeException(
                    'Uno de los bienes seleccionados ya no es elegible para Baja (debe pertenecer al responsable, '
                    . 'estar Activo, con asignación vigente, sin préstamo ni reserva activa, y sin estar en otra Baja).'
                );
            }

            if ($detalleModel->existeEnBajaVigentePorBienForUpdate($idBien, $idBajaExcluir)) {
                throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' ya está en otra Baja vigente.');
            }

            $tipoBaja = $tipoBajaModel->findActivoByIdForUpdate($datosBien['id_tipo_baja']);

            if ($tipoBaja === false) {
                throw new RuntimeException('El tipo de baja indicado para el bien ' . $bien['codigo_interno'] . ' no es válido o no está activo.');
            }

            if ($datosBien['justificacion'] === '') {
                throw new RuntimeException('Debe indicar la justificación del bien ' . $bien['codigo_interno'] . '.');
            }

            $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

            if ($valor === null) {
                throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' no tiene costo ni valor estimado registrado y no puede incluirse en una baja.');
            }

            $bienesValidados[$idBien] = [
                'codigo_interno' => $bien['codigo_interno'],
                'codigo_sicoin' => trim((string) ($bien['codigo_sicoin'] ?? '')),
                'descripcion' => $bien['descripcion'],
                'marca' => $bien['marca'],
                'modelo' => $bien['modelo'],
                'serie' => $bien['serie'],
                'valor' => $valor,
                'id_tipo_baja' => (int) $tipoBaja['id_tipo_baja'],
                'justificacion' => $datosBien['justificacion'],
                'id_responsable_actual' => (int) $bien['id_responsable_actual'],
                'id_ubicacion_actual' => $bien['id_ubicacion_actual'] !== null ? (int) $bien['id_ubicacion_actual'] : null,
                'id_estado_bien' => (int) $bien['id_estado_bien'],
            ];
        }

        return $bienesValidados;
    }

    /**
     * Reconstruye un array de archivo individual (['name','type','tmp_name','error','size']) a partir
     * de la estructura anidada que PHP genera para un input tipo file con name="imagen_bien[idBien]".
     * Devuelve null si no hay campo de archivo para ese índice.
     */
    private function extraerArchivoIndexado(?array $camposFiles, int $indice): ?array
    {
        if ($camposFiles === null || !isset($camposFiles['name'][$indice])) {
            return null;
        }

        if (($camposFiles['error'][$indice] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return [
            'name' => $camposFiles['name'][$indice],
            'type' => $camposFiles['type'][$indice],
            'tmp_name' => $camposFiles['tmp_name'][$indice],
            'error' => $camposFiles['error'][$indice],
            'size' => $camposFiles['size'][$indice],
        ];
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

        if (str_contains($detalleError, 'uq_bajas_numero_baja')) {
            return 'No fue posible generar un número único para la baja. Intente nuevamente.';
        }

        if (str_contains($detalleError, 'uq_detalle_baja_bien')) {
            return 'Un bien fue seleccionado más de una vez en la baja.';
        }

        return null;
    }
}
