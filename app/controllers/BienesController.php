<?php

require_once __DIR__ . '/../core/Controller.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\GdResult;

class BienesController extends Controller
{
    // El tiempo de garantía ya no se escribe libremente: solo se acepta un número entero de
    // meses entre 1 y 12, seleccionado desde un <select> cerrado en el formulario.
    private const MESES_GARANTIA_VALIDOS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

    // Regla institucional: todo bien que ingresa por Compra/Donación/Traslado queda inicialmente
    // resguardado en la Bodega de Almacén (Ubicacion::ID_UBICACION_ALMACEN_INSTITUCIONAL, único
    // punto centralizado de esa referencia — ver documentación junto a la constante). El
    // responsable SÍ se deriva por relación real (Responsable::findActivoPorUbicacion()), no por
    // un id de responsable fijo: si mañana cambia quién es el encargado de Almacén, no hay que
    // tocar este archivo. Devuelve false si el Almacén institucional no está configurado
    // correctamente o no tiene exactamente un responsable activo asociado (sin configurar o
    // ambiguo); el llamador debe rechazar el registro del bien en ese caso (ver crear()).
    private function resolverBodegaAlmacen(): array|false
    {
        $ubicacionModel = $this->model('Ubicacion');
        $responsableModel = $this->model('Responsable');

        $bodega = $ubicacionModel->findAlmacenInstitucional();

        if ($bodega === false) {
            return false;
        }

        $responsableBodega = $responsableModel->findActivoPorUbicacion((int) $bodega['id_ubicacion']);

        if ($responsableBodega === false) {
            return false;
        }

        return [
            'id_ubicacion' => (int) $bodega['id_ubicacion'],
            'nombre_ubicacion' => $bodega['nombre_ubicacion'],
            'id_responsable' => (int) $responsableBodega['id_responsable'],
            'nombre_responsable' => $responsableBodega['nombre_completo'],
        ];
    }

    // Deja al bien recién creado bajo la Bodega de Almacén usando el MISMO mecanismo que ya usan
    // Asignaciones/Traslados (Asignacion::crear()+agregarBien() + Bien::asignarActual()) — nunca
    // escribe id_responsable_actual/id_ubicacion_actual sueltos, porque eso dejaría el bien sin
    // id_asignacion_actual ni detalle_asignacion y lo excluiría de getActualesPorResponsable(),
    // findActualParaTrasladoForUpdate() y findPrestableForUpdate() (todas exigen una asignación
    // real). Reutiliza la asignación 'Asignada' vigente del responsable de Bodega si ya existe (para
    // no crear una asignación nueva por cada bien); si no existe, la crea directamente en estado
    // 'Asignada'. Debe ejecutarse dentro de la misma transacción del registro del bien.
    private function asignarBienABodega(int $idBien, array $bodega): void
    {
        $asignacionModel = $this->model('Asignacion');
        $bienModel = $this->model('Bien');
        $bitacoraModel = $this->model('Bitacora');

        $idResponsable = $bodega['id_responsable'];
        $idUbicacion = $bodega['id_ubicacion'];
        $fechaAsignacion = date('Y-m-d');

        // findAsignadaPorResponsableUbicacionForUpdate() solo puede devolver una 'Asignada' que
        // coincida con responsable Y ubicación de Bodega a la vez — una 'Pendiente' antigua ni siquiera
        // entra como candidata (ver comentario en Asignacion.php); no reutiliza, no bloquea el ingreso
        // de bienes, y el flujo cae directo a crear una 'Asignada' nueva.
        $asignacionVigente = $asignacionModel->findAsignadaPorResponsableUbicacionForUpdate($idResponsable, $idUbicacion);

        if ($asignacionVigente !== false) {
            $idAsignacion = (int) $asignacionVigente['id_asignacion'];
        } else {
            $anio = (int) substr($fechaAsignacion, 0, 4);
            $numeroAsignacion = $asignacionModel->generarSiguienteNumero($anio);

            $idAsignacion = $asignacionModel->crear([
                'numero_asignacion' => $numeroAsignacion,
                'id_responsable' => $idResponsable,
                'id_ubicacion' => $idUbicacion,
                'fecha_asignacion' => $fechaAsignacion,
                'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                'estado_asignacion' => 'Asignada',
                'observaciones' => 'Asignación creada automáticamente para el resguardo inicial de bienes en Bodega de Almacén.',
            ]);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_ASIGNACION',
                modulo: 'Asignaciones',
                resultado: 'exitoso',
                descripcion: 'Se creó automáticamente la asignación ' . $numeroAsignacion . ' a Bodega de Almacén para el resguardo inicial de bienes.',
                tablaAfectada: 'asignaciones',
                idRegistroAfectado: $idAsignacion,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );
        }

        // Defensivo (no debería ocurrir con el único punto de llamada actual, justo tras crear el
        // bien): mismo chequeo que ya hace AsignacionesController::agregarBien() antes de llamar a
        // Asignacion::agregarBien(), que por sí sola no evita duplicados.
        if ($asignacionModel->existeBienEnAsignacion($idAsignacion, $idBien)) {
            throw new RuntimeException(
                'El bien ya pertenece al detalle de la asignación de Bodega de Almacén.'
            );
        }

        $idDetalle = $asignacionModel->agregarBien($idAsignacion, $idBien, $fechaAsignacion, null);

        $bienModel->asignarActual($idBien, $idAsignacion, $idResponsable, $idUbicacion);

        $bitacoraModel->registrar(
            idUsuario: (int) $_SESSION['id_usuario'],
            accion: 'AGREGAR_BIEN_ASIGNACION',
            modulo: 'Asignaciones',
            resultado: 'exitoso',
            descripcion: 'Se incorporó automáticamente el bien recién registrado a la asignación de Bodega de Almacén.',
            tablaAfectada: 'detalle_asignacion',
            idRegistroAfectado: $idDetalle,
            ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
            usuarioIntentado: null
        );
    }

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $bienModel = $this->model('Bien');

        $q = trim($_GET['q'] ?? '');
        $idCategoria = (int) ($_GET['id_categoria'] ?? 0);
        $estado = trim($_GET['estado'] ?? '');

        $bienes = $bienModel->getAll(
            $q !== '' ? $q : null,
            $idCategoria > 0 ? $idCategoria : null,
            $estado !== '' ? $estado : null
        );

        $this->view('bienes/index', [
            'bienes' => $bienes,
            'q' => $q,
            'idCategoria' => $idCategoria,
            'estado' => $estado,
            'categorias' => $this->model('CategoriaBien')->getActivas(),
            'estados' => $this->model('EstadoBien')->getActivos(),
            'tituloPagina' => 'Bienes institucionales',
        ], 'main');
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $formaIngresoModel = $this->model('FormaIngreso');
        $categoriaBienModel = $this->model('CategoriaBien');
        $estadoBienModel = $this->model('EstadoBien');
        $formaPagoModel = $this->model('FormaPago');

        $formasIngreso = $formaIngresoModel->getActivas();
        $categorias = $categoriaBienModel->getActivas();
        $estados = $estadoBienModel->getActivos();
        // Solo aplica a Compra. Catálogo de solo lectura (ver FormaPago.php).
        $formasPago = $formaPagoModel->getActivas();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('bienes/crear', [
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'formasPago' => $formasPago,
                'datos' => [],
                'error' => null,
                'bodegaConfigurada' => $this->resolverBodegaAlmacen() !== false,
                'tituloPagina' => 'Registrar bien',
            ], 'main');
            return;
        }

        verifyCsrf();

        $codigoInterno = trim($_POST['codigo_interno'] ?? '');
        $codigoSicoin = trim($_POST['codigo_sicoin'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $serie = trim($_POST['serie'] ?? '');
        $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
        $idEstadoBien = (int) ($_POST['id_estado_bien'] ?? 0);
        $condicionBien = trim($_POST['condicion_bien'] ?? '');
        $idFormaIngreso = (int) ($_POST['id_forma_ingreso'] ?? 0);
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
        $costo = trim($_POST['costo'] ?? '');
        $valorEstimado = trim($_POST['valor_estimado'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $proveedor = trim($_POST['proveedor'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $serieFactura = trim($_POST['serie_factura'] ?? '');
        $fechaFactura = trim($_POST['fecha_factura'] ?? '');
        $numeroLiquidacion = trim($_POST['numero_liquidacion'] ?? '');
        // Forma de pago: solo se procesa cuando la forma de ingreso es Compra. 0 = no enviada.
        $idFormaPago = (int) ($_POST['id_forma_pago'] ?? 0);
        $tieneGarantia = isset($_POST['tiene_garantia'])
            ? ((int) ($_POST['tiene_garantia'] === '1' || $_POST['tiene_garantia'] === 'on' ? 1 : 0))
            : 0;
        $tiempoGarantia = trim($_POST['tiempo_garantia'] ?? '');
        $procedencia = trim($_POST['procedencia'] ?? '');
        $entidadDonante = trim($_POST['entidad_donante'] ?? '');
        $numeroActa = trim($_POST['numero_acta'] ?? '');
        $fechaActa = trim($_POST['fecha_acta'] ?? '');
        $unidadEjecutoraOrigen = trim($_POST['unidad_ejecutora_origen'] ?? '');
        $codigoUnidadOrigen = trim($_POST['codigo_unidad_origen'] ?? '');

        $datos = [
            'codigo_interno' => $codigoInterno,
            'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
            'descripcion' => $descripcion,
            'marca' => $marca !== '' ? $marca : null,
            'modelo' => $modelo !== '' ? $modelo : null,
            'serie' => $serie !== '' ? $serie : null,
            'id_categoria' => $idCategoria,
            'id_estado_bien' => $idEstadoBien,
            'condicion_bien' => $condicionBien,
            'id_forma_ingreso' => $idFormaIngreso,
            'fecha_ingreso' => $fechaIngreso,
            'costo' => $costo !== '' ? $costo : null,
            'valor_estimado' => $valorEstimado !== '' ? $valorEstimado : null,
            'id_responsable_actual' => null,
            'id_ubicacion_actual' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoCompra = [
            'id_bien' => null,
            'proveedor' => $proveedor !== '' ? $proveedor : null,
            'numero_factura' => $numeroFactura !== '' ? $numeroFactura : null,
            'serie_factura' => $serieFactura !== '' ? $serieFactura : null,
            'fecha_factura' => $fechaFactura !== '' ? $fechaFactura : null,
            'numero_liquidacion' => $numeroLiquidacion !== '' ? $numeroLiquidacion : null,
            'id_forma_pago' => $idFormaPago > 0 ? $idFormaPago : null,
            'tiene_garantia' => $tieneGarantia,
            'tiempo_garantia' => $tieneGarantia === 1 && $tiempoGarantia !== '' ? $tiempoGarantia : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoDonacion = [
            'id_bien' => null,
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'entidad_donante' => $entidadDonante !== '' ? $entidadDonante : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoTraslado = [
            'id_bien' => null,
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'unidad_ejecutora_origen' => $unidadEjecutoraOrigen !== '' ? $unidadEjecutoraOrigen : null,
            'codigo_unidad_origen' => $codigoUnidadOrigen !== '' ? $codigoUnidadOrigen : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $error = null;
        $documentosRespaldo = [];
        $rutaFotografia = null;
        $bodegaAlmacen = false;

        if ($codigoInterno === '') {
            $error = 'El código interno es obligatorio.';
        } elseif ($descripcion === '') {
            $error = 'La descripción es obligatoria.';
        } elseif ($idCategoria <= 0) {
            $error = 'La categoría es obligatoria.';
        } elseif ($idEstadoBien <= 0) {
            $error = 'El estado del bien es obligatorio.';
        } elseif ($condicionBien === '') {
            $error = 'La condición del bien es obligatoria.';
        } elseif (!in_array($condicionBien, ['Bueno', 'Regular', 'Malo'], true)) {
            $error = 'La condición seleccionada no es válida.';
        } elseif ($idFormaIngreso <= 0) {
            $error = 'La forma de ingreso es obligatoria.';
        } elseif ($fechaIngreso === '') {
            $error = 'La fecha de ingreso es obligatoria.';
        } elseif (!isValidIsoDate($fechaIngreso)) {
            $error = 'La fecha de ingreso no es válida.';
        } else {
            // Regla institucional: todo ingreso por Compra/Donación/Traslado debe poder quedar
            // resguardado en Bodega de Almacén desde el registro. Si no está configurada, no se
            // guarda el bien (evita bienes huérfanos sin responsable/ubicación).
            $bodegaAlmacen = $this->resolverBodegaAlmacen();

            if ($bodegaAlmacen === false) {
                $error = 'No se puede registrar el bien: debe configurarse primero la Bodega de Almacén '
                    . '(una ubicación activa de tipo "Bodega") y su responsable activo asociado.';
            }

            $formaSeleccionada = null;
            foreach ($formasIngreso as $forma) {
                if ((int) $forma['id_forma_ingreso'] === $idFormaIngreso) {
                    $formaSeleccionada = $forma;
                    break;
                }
            }

            if ($error === null && $formaSeleccionada === null) {
                $error = 'La forma de ingreso seleccionada no es válida.';
            }

            if ($error === null) {
                $categoriaValida = false;
                foreach ($categorias as $categoria) {
                    if ((int) $categoria['id_categoria'] === $idCategoria) {
                        $categoriaValida = true;
                        break;
                    }
                }

                if (!$categoriaValida) {
                    $error = 'La categoría seleccionada no es válida.';
                }
            }

            if ($error === null) {
                $estadoValido = false;
                foreach ($estados as $estado) {
                    if ((int) $estado['id_estado_bien'] === $idEstadoBien) {
                        $estadoValido = true;
                        break;
                    }
                }

                if (!$estadoValido) {
                    $error = 'El estado seleccionado no es válido.';
                }
            }

            if ($error === null && $datos['costo'] !== null) {
                if (!is_numeric($datos['costo']) || (float) $datos['costo'] < 0) {
                    $error = 'El costo debe ser un número mayor o igual que 0.';
                }
            }

            if ($error === null && $datos['valor_estimado'] !== null) {
                if (!is_numeric($datos['valor_estimado']) || (float) $datos['valor_estimado'] < 0) {
                    $error = 'El valor estimado debe ser un número mayor o igual que 0.';
                }
            }

            if ($error === null && $formaSeleccionada !== null) {
                $nombreForma = mb_strtolower(trim($formaSeleccionada['nombre_forma'] ?? ''), 'UTF-8');
                $nombreForma = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombreForma);

                $formasPermitidas = ['compra', 'donacion', 'traslado'];

                if (!in_array($nombreForma, $formasPermitidas, true)) {
                    $error = 'La forma de ingreso seleccionada no está disponible para este registro.';
                } else {
                    if ($nombreForma === 'donacion') {
                        $datos['costo'] = null;
                    }

                    if ($nombreForma === 'compra' || $nombreForma === 'traslado') {
                        $datos['valor_estimado'] = null;
                    }

                    if ($nombreForma === 'compra') {
                        if ($proveedor === '') {
                            $error = 'El proveedor es obligatorio para la compra.';
                        } elseif ($numeroFactura === '') {
                            $error = 'El número de factura es obligatorio para la compra.';
                        } elseif ($fechaFactura === '') {
                            $error = 'La fecha de factura es obligatoria para la compra.';
                        } elseif (!isValidIsoDate($fechaFactura)) {
                            $error = 'La fecha de factura no es válida.';
                        } elseif ($datos['costo'] === null) {
                            $error = 'El costo es obligatorio para compra.';
                        } elseif ($idFormaPago <= 0) {
                            $error = 'La forma de pago es obligatoria para la compra.';
                        } elseif (!$this->formaPagoActivaValida($idFormaPago, $formasPago)) {
                            // No se confía en el <select>: se revalida contra el catálogo activo.
                            $error = 'La forma de pago seleccionada no es válida.';
                        } elseif ($tieneGarantia === 1 && $tiempoGarantia === '') {
                            $error = 'El tiempo de garantía es obligatorio cuando la compra tiene garantía.';
                        } elseif ($tieneGarantia === 1 && !in_array($tiempoGarantia, self::MESES_GARANTIA_VALIDOS, true)) {
                            $error = 'El tiempo de garantía debe ser un número entero de 1 a 12 meses.';
                        }
                    } elseif ($nombreForma === 'donacion') {
                        if ($procedencia === '') {
                            $error = 'La procedencia es obligatoria para la donación.';
                        } elseif ($entidadDonante === '') {
                            $error = 'La entidad donante es obligatoria para la donación.';
                        } elseif ($numeroActa === '') {
                            $error = 'El número de acta es obligatorio para la donación.';
                        } elseif ($fechaActa !== '' && !isValidIsoDate($fechaActa)) {
                            $error = 'La fecha de acta no es válida.';
                        } elseif ($datos['valor_estimado'] === null) {
                            $error = 'El valor estimado es obligatorio para donación.';
                        }
                    } elseif ($nombreForma === 'traslado') {
                        if ($procedencia === '') {
                            $error = 'La procedencia es obligatoria para el traslado.';
                        } elseif ($unidadEjecutoraOrigen === '') {
                            $error = 'La unidad ejecutora de origen es obligatoria para el traslado.';
                        } elseif ($codigoUnidadOrigen === '') {
                            $error = 'El código de la unidad de origen es obligatorio para el traslado.';
                        } elseif ($numeroActa === '') {
                            $error = 'El número de acta es obligatorio para el traslado.';
                        } elseif ($fechaActa !== '' && !isValidIsoDate($fechaActa)) {
                            $error = 'La fecha de acta no es válida.';
                        } elseif ($datos['costo'] === null) {
                            $error = 'El costo es obligatorio para traslado.';
                        }
                    }
                }
            }

            if ($error === null) {
                try {
                    // Documentos de respaldo: colección acumulativa (0..N). En "Registrar bien" el
                    // formulario admite un solo archivo inicial, pero el flujo de guardado ya es el
                    // mismo que en "Modificar bien" — cada documento se inserta en documentos_bien.
                    $documentosRespaldo = guardarDocumentosRespaldo($_FILES['documento_respaldo'] ?? null);
                    $rutaFotografia = guardarFotografiaBien($_FILES['fotografia_bien'] ?? null);
                } catch (RuntimeException $errorArchivo) {
                    $error = $errorArchivo->getMessage();
                    // Si algún archivo sí se guardó y otro falló, no dejar los que entraron
                    // huérfanos: se limpian y se re-renderiza el formulario con el mensaje de error.
                    eliminarDocumentosRespaldo($documentosRespaldo);
                    eliminarFotografiaBien($rutaFotografia);
                    $documentosRespaldo = [];
                    $rutaFotografia = null;
                }
            }
        }

        if ($error !== null) {
            $datosFormulario = array_merge(
                $datos,
                [
                    'proveedor' => $proveedor,
                    'numero_factura' => $numeroFactura,
                    'serie_factura' => $serieFactura,
                    'fecha_factura' => $fechaFactura,
                    'numero_liquidacion' => $numeroLiquidacion,
                    'id_forma_pago' => $idFormaPago,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'procedencia' => $procedencia,
                    'entidad_donante' => $entidadDonante,
                    'numero_acta' => $numeroActa,
                    'fecha_acta' => $fechaActa,
                    'unidad_ejecutora_origen' => $unidadEjecutoraOrigen,
                    'codigo_unidad_origen' => $codigoUnidadOrigen,
                ]
            );

            $this->view('bienes/crear', [
                'error' => $error,
                'datos' => $datosFormulario,
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'formasPago' => $formasPago,
                'bodegaConfigurada' => $bodegaAlmacen !== false,
                'tituloPagina' => 'Registrar bien',
            ], 'main');
            return;
        }

        // Las columnas `ingreso_*.documento_respaldo` quedan como legado: ya no se escriben (los
        // documentos viven en documentos_bien). Se dejan en NULL para los registros nuevos.
        $datos['imagen_bien'] = $rutaFotografia;

        $bienModel = $this->model('Bien');
        $ingresoCompraModel = $this->model('IngresoCompra');
        $ingresoDonacionModel = $this->model('IngresoDonacion');
        $ingresoTrasladoModel = $this->model('IngresoTraslado');
        $ingresoBienOriginalModel = $this->model('IngresoBienOriginal');
        $documentoBienModel = $this->model('DocumentoBien');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $bienModel->beginTransaction();

            $idBien = $bienModel->crear($datos);

            $nombreForma = mb_strtolower(trim($formaSeleccionada['nombre_forma'] ?? ''), 'UTF-8');
            $nombreForma = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombreForma);

            if ($nombreForma === 'compra') {
                $datosIngresoCompra['id_bien'] = $idBien;
                $ingresoCompraModel->crear($datosIngresoCompra);
            } elseif ($nombreForma === 'donacion') {
                $datosIngresoDonacion['id_bien'] = $idBien;
                $ingresoDonacionModel->crear($datosIngresoDonacion);
            } elseif ($nombreForma === 'traslado') {
                $datosIngresoTraslado['id_bien'] = $idBien;
                $ingresoTrasladoModel->crear($datosIngresoTraslado);
            }

            // Documento(s) de respaldo del ingreso -> colección acumulativa documentos_bien. Dentro
            // de la misma transacción: si el registro se revierte, estas filas también. Los archivos
            // físicos ya movidos se limpian en el catch. El documento inicial NO genera un evento de
            // "Modificación de información": forma parte del ingreso, no es una edición posterior.
            $fechaRegistroDocumentos = date('Y-m-d H:i:s');
            foreach ($documentosRespaldo as $documento) {
                $documentoBienModel->registrar([
                    'id_bien' => $idBien,
                    'tipo_ingreso' => $nombreForma,
                    'nombre_original' => $documento['nombre_original'],
                    'ruta_documento' => $documento['ruta'],
                    'fecha_registro' => $fechaRegistroDocumentos,
                    'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                ]);
            }

            $nombreFormaTexto = match ($nombreForma) {
                'compra' => 'Compra',
                'donacion' => 'Donación',
                'traslado' => 'Traslado',
                default => ucfirst($nombreForma),
            };

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_BIEN',
                modulo: 'Bienes',
                resultado: 'exitoso',
                descripcion: 'Se registró el bien con código interno ' . $datos['codigo_interno'] . ' mediante ' . $nombreFormaTexto . '.',
                tablaAfectada: 'bienes',
                idRegistroAfectado: $idBien,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            // Resguardo inicial en Bodega de Almacén, dentro de la misma transacción: si esto
            // falla (p. ej. asignación de Bodega pendiente sin confirmar), todo el registro del
            // bien se revierte — nunca queda un bien huérfano ni a medio resguardar.
            $this->asignarBienABodega($idBien, $bodegaAlmacen);

            // Snapshot congelado de los datos de ingreso (mismos que usa el evento "Ingreso del
            // bien"). Dentro de la transacción: si falla, se revierte todo el registro y no queda
            // un bien nuevo sin snapshot. Nunca se actualiza después: una edición posterior de
            // proveedor/procedencia/fecha se refleja solo en historial_modificaciones_bien.
            $snapshotIngreso = [
                'id_bien' => $idBien,
                'tipo_ingreso' => $nombreForma,
                'fecha_ingreso_original' => $datos['fecha_ingreso'] ?? null,
                'proveedor_original' => null,
                'entidad_donante_original' => null,
                'procedencia_donacion_original' => null,
                'unidad_ejecutora_origen_original' => null,
                'procedencia_traslado_original' => null,
            ];

            if ($nombreForma === 'compra') {
                $snapshotIngreso['proveedor_original'] = $datosIngresoCompra['proveedor'] ?? null;
            } elseif ($nombreForma === 'donacion') {
                $snapshotIngreso['entidad_donante_original'] = $datosIngresoDonacion['entidad_donante'] ?? null;
                $snapshotIngreso['procedencia_donacion_original'] = $datosIngresoDonacion['procedencia'] ?? null;
            } elseif ($nombreForma === 'traslado') {
                $snapshotIngreso['unidad_ejecutora_origen_original'] = $datosIngresoTraslado['unidad_ejecutora_origen'] ?? null;
                $snapshotIngreso['procedencia_traslado_original'] = $datosIngresoTraslado['procedencia'] ?? null;
            }

            $ingresoBienOriginalModel->crearSnapshot($snapshotIngreso);

            $bienModel->commit();

            try {
                $qr = $this->generarArchivoQr($idBien, $datos['codigo_sicoin'], $datos['codigo_interno']);

                $bienModel->actualizarQr($idBien, $qr['codigo_qr'], $qr['ruta_qr']);

                $bitacoraModel->registrar(
                    idUsuario: (int) $_SESSION['id_usuario'],
                    accion: 'GENERAR_QR_BIEN',
                    modulo: 'Bienes',
                    resultado: 'exitoso',
                    descripcion: 'Se generó el código QR del bien con código interno ' . $datos['codigo_interno'] . '.',
                    tablaAfectada: 'bienes',
                    idRegistroAfectado: $idBien,
                    ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                    usuarioIntentado: null
                );
            } catch (Throwable $qrError) {
                error_log(
                    'El bien ' . $idBien .
                    ' fue registrado, pero no se pudo generar su QR: ' .
                    $qrError->getMessage()
                );

                setFlash(
                    'warning',
                    'Bien registrado',
                    'El bien fue registrado, pero no fue posible generar el código QR. Puede generarlo desde la ficha del bien.'
                );
            }

            // El bien ya quedó confirmado ($bienModel->commit() arriba); el QR es un paso posterior de
            // mejor esfuerzo. Solo se marca éxito pleno si el bloque QR no dejó ya un flash de aviso.
            if (!isset($_SESSION['flash'])) {
                setFlash('success', 'Bien registrado correctamente', 'El bien fue registrado y quedó disponible en el sistema.');
            }

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            if ($bienModel->inTransaction()) {
                $bienModel->rollBack();
            }

            // Los documentos recién subidos se eliminan: la transacción se revirtió, así que ninguna
            // fila de documentos_bien apunta a ellos.
            eliminarDocumentosRespaldo($documentosRespaldo);

            // La fotografía recién subida se elimina también: la transacción se revirtió, así que
            // ningún registro apunta a ella.
            eliminarFotografiaBien($rutaFotografia);

            if ($e instanceof RuntimeException) {
                // Errores controlados propios (p. ej. asignarBienABodega() con la asignación de
                // Bodega pendiente sin confirmar): se muestran tal cual, no se ocultan detrás del
                // mensaje genérico.
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar el bien: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar el bien. Verifique los datos e intente nuevamente.';
            }

            $datosFormulario = array_merge(
                $datos,
                [
                    'proveedor' => $proveedor,
                    'numero_factura' => $numeroFactura,
                    'serie_factura' => $serieFactura,
                    'fecha_factura' => $fechaFactura,
                    'numero_liquidacion' => $numeroLiquidacion,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'procedencia' => $procedencia,
                    'entidad_donante' => $entidadDonante,
                    'numero_acta' => $numeroActa,
                    'fecha_acta' => $fechaActa,
                    'unidad_ejecutora_origen' => $unidadEjecutoraOrigen,
                    'codigo_unidad_origen' => $codigoUnidadOrigen,
                ]
            );

            $this->view('bienes/crear', [
                'error' => $error,
                'datos' => $datosFormulario,
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'bodegaConfigurada' => $bodegaAlmacen !== false,
                'tituloPagina' => 'Registrar bien',
            ], 'main');
            return;
        }
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            echo 'Bien no válido.';
            return;
        }

        $bienModel = $this->model('Bien');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        $formaNombre = mb_strtolower(trim($bien['nombre_forma'] ?? ''), 'UTF-8');
        $formaNombre = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $formaNombre);

        $datosIngreso = false;

        if ($formaNombre === 'compra') {
            $ingresoCompraModel = $this->model('IngresoCompra');
            $datosIngreso = $ingresoCompraModel->findByBienId($idBien);
        } elseif ($formaNombre === 'donacion') {
            $ingresoDonacionModel = $this->model('IngresoDonacion');
            $datosIngreso = $ingresoDonacionModel->findByBienId($idBien);
        } elseif ($formaNombre === 'traslado') {
            $ingresoTrasladoModel = $this->model('IngresoTraslado');
            $datosIngreso = $ingresoTrasladoModel->findByBienId($idBien);
        }

        $this->view('bienes/ver', [
            'bien' => $bien,
            'formaNombre' => $formaNombre,
            'datosIngreso' => $datosIngreso !== false ? $datosIngreso : [],
            'documentosBien' => $this->model('DocumentoBien')->listarPorBien($idBien),
            'tituloPagina' => 'Detalle del bien',
        ], 'main');
    }

    // Historial integral (trazabilidad funcional) de un bien: qué le ha pasado a lo largo del
    // tiempo — Ingreso, Requisiciones, Traslados, Préstamos, Devoluciones, Verificaciones, cambios
    // de SICOIN y Bajas — reconstruido de tablas existentes por HistorialBien. Solo lectura: sesión
    // obligatoria (igual que ver()), los tres roles pueden consultar, sin POST, sin cambios en BD.
    public function historial(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            echo 'Bien no válido.';
            return;
        }

        $bienModel = $this->model('Bien');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        $historialModel = $this->model('HistorialBien');
        $eventos = $historialModel->getEventos($idBien);

        // Filtro opcional por tipo (solo si el valor es uno de los tipos realmente presentes).
        $tiposPresentes = $historialModel->getTiposPresentes($eventos);
        $tipoFiltro = (string) ($_GET['tipo'] ?? '');
        if ($tipoFiltro !== '' && in_array($tipoFiltro, $tiposPresentes, true)) {
            $eventos = array_values(array_filter(
                $eventos,
                static fn (array $evento): bool => $evento['tipo'] === $tipoFiltro
            ));
        } else {
            $tipoFiltro = '';
        }

        $this->view('bienes/historial', [
            'bien' => $bien,
            'eventos' => $eventos,
            'tiposPresentes' => $tiposPresentes,
            'tipoFiltro' => $tipoFiltro,
            'tituloPagina' => 'Historial del bien',
        ], 'main');
    }

    // Sirve UN documento de respaldo del ingreso de un bien por su id de documentos_bien — nunca por
    // URL directa a storage/documentos/ (bloqueada por storage/.htaccess). Mismos permisos que
    // ver(): solo sesión activa (el router ya redirige a login sin sesión). GET puro, sin POST, sin
    // CSRF, sin cambios de datos. El navegador solo envía el id del DOCUMENTO; la ruta física se
    // obtiene de BD y se valida con realpath() contra storage/documentos/. 404 genérico ante
    // cualquier problema. Disposition 'inline' porque la acción es "Ver documento".
    public function verDocumento(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idDocumento = (int) ($_GET['id'] ?? 0);

        if ($idDocumento <= 0) {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $documento = $this->model('DocumentoBien')->buscarPorId($idDocumento);

        if ($documento === false || empty($documento['ruta_documento'])) {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $rutaFisica = resolverRutaArchivoStorage($documento['ruta_documento'], 'documentos');

        servirArchivoControlado($rutaFisica, 'inline');
    }

    // Sirve la FOTOGRAFÍA principal del bien (bienes.imagen_bien) — nunca por URL directa a
    // storage/fotos_bienes/ (bloqueada por storage/.htaccess). Mismos permisos y forma que
    // verDocumento(): solo sesión activa, GET puro, sin CSRF, sin cambios en BD. El navegador solo
    // envía el id del bien; la ruta física sale de BD y se valida con realpath() contra
    // storage/fotos_bienes/. 'inline' porque la imagen se muestra (miniatura + modal).
    public function imagen(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            http_response_code(404);
            echo 'Imagen no disponible.';
            return;
        }

        $bien = $this->model('Bien')->findById($idBien);

        if ($bien === false || empty($bien['imagen_bien'])) {
            http_response_code(404);
            echo 'Imagen no disponible.';
            return;
        }

        $rutaFisica = resolverRutaArchivoStorage($bien['imagen_bien'], 'fotos_bienes');

        servirArchivoControlado($rutaFisica, 'inline');
    }

    public function editar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            echo 'Bien no válido.';
            return;
        }

        $bienModel = $this->model('Bien');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        $ingresoCompraModel = $this->model('IngresoCompra');
        $ingresoDonacionModel = $this->model('IngresoDonacion');
        $ingresoTrasladoModel = $this->model('IngresoTraslado');
        $documentoBienModel = $this->model('DocumentoBien');
        $bitacoraModel = $this->model('Bitacora');
        $historialSicoinModel = $this->model('HistorialSicoin');
        $historialModificacionModel = $this->model('HistorialModificacionBien');
        $formaIngresoModel = $this->model('FormaIngreso');
        $categoriaBienModel = $this->model('CategoriaBien');
        $estadoBienModel = $this->model('EstadoBien');
        $formaPagoModel = $this->model('FormaPago');

        $formasIngreso = $formaIngresoModel->getActivas();
        $categorias = $categoriaBienModel->getActivas();
        $estados = $estadoBienModel->getActivos();
        // Solo se usa en la sección "Datos de compra".
        $formasPago = $formaPagoModel->getActivas();

        $idFormaIngresoOriginal = (int) ($bien['id_forma_ingreso'] ?? 0);

        $formaNombre = mb_strtolower(trim($bien['nombre_forma'] ?? ''), 'UTF-8');
        $formaNombre = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $formaNombre);

        if (!in_array($formaNombre, ['compra', 'donacion', 'traslado'], true)) {
            echo 'La forma de ingreso del bien no está disponible para edición.';
            return;
        }

        $datosEspecificos = false;

        if ($formaNombre === 'compra') {
            $datosEspecificos = $ingresoCompraModel->findByBienId($idBien);
        } elseif ($formaNombre === 'donacion') {
            $datosEspecificos = $ingresoDonacionModel->findByBienId($idBien);
        } elseif ($formaNombre === 'traslado') {
            $datosEspecificos = $ingresoTrasladoModel->findByBienId($idBien);
        }

        if ($datosEspecificos === false) {
            echo 'No se encontraron los datos de ingreso asociados al bien.';
            return;
        }

        // Valor legado de la columna vieja: NO se escribe ni se borra en esta edición; se conserva
        // tal cual para que el UPDATE de ingreso_* no lo altere.
        $rutaDocumentoActual = $datosEspecificos['documento_respaldo'] ?? null;
        $rutaFotografiaActual = $bien['imagen_bien'] ?? null;

        // Colección acumulativa de documentos de respaldo del bien (para mostrar la lista y para el
        // re-render tras error).
        $documentosBien = $documentoBienModel->listarPorBien($idBien);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $datosGenerales = [
                'codigo_interno' => $bien['codigo_interno'] ?? '',
                'codigo_sicoin' => $bien['codigo_sicoin'] ?? '',
                'descripcion' => $bien['descripcion'] ?? '',
                'marca' => $bien['marca'] ?? '',
                'modelo' => $bien['modelo'] ?? '',
                'serie' => $bien['serie'] ?? '',
                'id_categoria' => (int) ($bien['id_categoria'] ?? 0),
                'id_estado_bien' => (int) ($bien['id_estado_bien'] ?? 0),
                'condicion_bien' => $bien['condicion_bien'] ?? '',
                'id_forma_ingreso' => $idFormaIngresoOriginal,
                'fecha_ingreso' => $bien['fecha_ingreso'] ?? '',
                'costo' => $bien['costo'] ?? '',
                'valor_estimado' => $bien['valor_estimado'] ?? '',
                'observaciones' => $bien['observaciones'] ?? '',
            ];

            $datosEspecificosLimpios = $datosEspecificos;
            unset(
                $datosEspecificosLimpios['id_compra'],
                $datosEspecificosLimpios['id_donacion'],
                $datosEspecificosLimpios['id_traslado_ingreso'],
                $datosEspecificosLimpios['id_bien'],
                $datosEspecificosLimpios['observaciones']
            );

            $datos = array_merge($datosGenerales, $datosEspecificosLimpios);

            $this->view('bienes/editar', [
                'bien' => $bien,
                'datos' => $datos,
                'formaNombre' => $formaNombre,
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'formasPago' => $formasPago,
                'documentosBien' => $documentosBien,
                'error' => null,
                'tituloPagina' => 'Modificar bien',
            ], 'main');

            return;
        }

        verifyCsrf();

        // Código interno: se define SOLO al registrar el bien y queda bloqueado de forma permanente.
        // Se ignora por completo cualquier valor recibido en el POST (aunque se edite el HTML desde
        // DevTools) y se conserva SIEMPRE el que ya tiene la BD. Bien::actualizar() tampoco lo escribe.
        $codigoInterno = trim((string) ($bien['codigo_interno'] ?? ''));

        // Código SICOIN: si el bien YA tiene uno registrado, queda bloqueado igual que el código
        // interno (se ignora el POST y se conserva el de BD). Si está en NULL/vacío, puede
        // establecerse por primera vez desde esta edición (una sola vez; después queda bloqueado).
        $sicoinRegistrado = trim((string) ($bien['codigo_sicoin'] ?? ''));
        $sicoinYaBloqueado = $sicoinRegistrado !== '';
        $codigoSicoin = $sicoinYaBloqueado
            ? $sicoinRegistrado
            : trim((string) ($_POST['codigo_sicoin'] ?? ''));
        $descripcion = trim($_POST['descripcion'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $serie = trim($_POST['serie'] ?? '');
        $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
        // El estado del bien NO es editable desde "Modificar bien": se ignora por completo cualquier
        // id_estado_bien recibido por POST (aunque se manipule el HTML) y se conserva SIEMPRE el que
        // ya tiene la BD. El cambio de estado ocurre solo por los flujos formales (Baja).
        $idEstadoBien = (int) ($bien['id_estado_bien'] ?? 0);
        $condicionBien = trim($_POST['condicion_bien'] ?? '');
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
        $costo = trim($_POST['costo'] ?? '');
        $valorEstimado = trim($_POST['valor_estimado'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $proveedor = trim($_POST['proveedor'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $serieFactura = trim($_POST['serie_factura'] ?? '');
        $fechaFactura = trim($_POST['fecha_factura'] ?? '');
        $numeroLiquidacion = trim($_POST['numero_liquidacion'] ?? '');
        // Forma de pago (solo Compra). 0 = el <select> quedó en "Seleccione": para una compra
        // histórica sin forma de pago eso significa "no completar todavía" (se conserva NULL); si el
        // bien YA tiene una, un 0 se interpreta como "sin cambio" y se conserva la de BD.
        $idFormaPago = (int) ($_POST['id_forma_pago'] ?? 0);
        $tieneGarantia = isset($_POST['tiene_garantia'])
            ? ((int) ($_POST['tiene_garantia'] === '1' || $_POST['tiene_garantia'] === 'on' ? 1 : 0))
            : 0;
        $tiempoGarantia = trim($_POST['tiempo_garantia'] ?? '');
        $procedencia = trim($_POST['procedencia'] ?? '');
        $entidadDonante = trim($_POST['entidad_donante'] ?? '');
        $numeroActa = trim($_POST['numero_acta'] ?? '');
        $fechaActa = trim($_POST['fecha_acta'] ?? '');
        $unidadEjecutoraOrigen = trim($_POST['unidad_ejecutora_origen'] ?? '');
        $codigoUnidadOrigen = trim($_POST['codigo_unidad_origen'] ?? '');

        $datos = [
            'codigo_interno' => $codigoInterno,
            'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
            'descripcion' => $descripcion,
            'marca' => $marca !== '' ? $marca : null,
            'modelo' => $modelo !== '' ? $modelo : null,
            'serie' => $serie !== '' ? $serie : null,
            'id_categoria' => $idCategoria,
            'id_estado_bien' => $idEstadoBien,
            'condicion_bien' => $condicionBien,
            'id_forma_ingreso' => $idFormaIngresoOriginal,
            'fecha_ingreso' => $fechaIngreso,
            'costo' => $costo !== '' ? $costo : null,
            'valor_estimado' => $valorEstimado !== '' ? $valorEstimado : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        // Normalización únicamente para comparar el SICOIN anterior contra el nuevo (historial_sicoin).
        $sicoinAnteriorNormalizado = trim((string) ($bien['codigo_sicoin'] ?? ''));
        $sicoinAnteriorNormalizado = $sicoinAnteriorNormalizado !== '' ? $sicoinAnteriorNormalizado : null;
        $sicoinNuevoNormalizado = $datos['codigo_sicoin'];
        $huboCambioSicoin = $sicoinAnteriorNormalizado !== $sicoinNuevoNormalizado;

        // Mismo criterio que el SICOIN: la bitácora genérica de MODIFICAR_BIEN no distingue qué
        // campo cambió, así que un cambio de condición necesita su propia entrada dedicada con
        // valor anterior→nuevo explícito (reemplaza la trazabilidad que daba la extinta acción
        // independiente "Cambiar condición").
        $condicionAnterior = (string) ($bien['condicion_bien'] ?? '');
        $huboCambioCondicion = $condicionAnterior !== $condicionBien;

        // Forma de pago que se guardará en ingreso_compra (solo relevante si el bien es Compra):
        //  - si el usuario eligió una en el <select> (>0) se valida contra el catálogo activo y se usa;
        //  - si dejó "Seleccione" (0) se conserva la de BD (NULL o valor): no se obliga a inventar el
        //    dato en compras históricas ni se permite borrar una ya registrada desde el formulario.
        $idFormaPagoBd = ($formaNombre === 'compra' && ($datosEspecificos['id_forma_pago'] ?? null) !== null)
            ? (int) $datosEspecificos['id_forma_pago']
            : null;
        $idFormaPagoFinal = $idFormaPago > 0 ? $idFormaPago : $idFormaPagoBd;

        $datosIngresoCompra = [
            'proveedor' => $proveedor !== '' ? $proveedor : null,
            'numero_factura' => $numeroFactura !== '' ? $numeroFactura : null,
            'serie_factura' => $serieFactura !== '' ? $serieFactura : null,
            'fecha_factura' => $fechaFactura !== '' ? $fechaFactura : null,
            'numero_liquidacion' => $numeroLiquidacion !== '' ? $numeroLiquidacion : null,
            'id_forma_pago' => $idFormaPagoFinal,
            'tiene_garantia' => $tieneGarantia,
            'tiempo_garantia' => $tieneGarantia === 1 && $tiempoGarantia !== '' ? $tiempoGarantia : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoDonacion = [
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'entidad_donante' => $entidadDonante !== '' ? $entidadDonante : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoTraslado = [
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'unidad_ejecutora_origen' => $unidadEjecutoraOrigen !== '' ? $unidadEjecutoraOrigen : null,
            'codigo_unidad_origen' => $codigoUnidadOrigen !== '' ? $codigoUnidadOrigen : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $error = null;

        if ($codigoInterno === '') {
            $error = 'El código interno es obligatorio.';
        } elseif ($descripcion === '') {
            $error = 'La descripción es obligatoria.';
        } elseif ($idCategoria <= 0) {
            $error = 'La categoría es obligatoria.';
        } elseif ($idEstadoBien <= 0) {
            $error = 'El estado del bien es obligatorio.';
        } elseif ($condicionBien === '') {
            $error = 'La condición del bien es obligatoria.';
        } elseif (!in_array($condicionBien, ['Bueno', 'Regular', 'Malo'], true)) {
            $error = 'La condición seleccionada no es válida.';
        } elseif ($sicoinAnteriorNormalizado !== null && $sicoinNuevoNormalizado === null) {
            $error = 'No se puede eliminar un código SICOIN ya registrado.';
        } elseif ($fechaIngreso === '') {
            $error = 'La fecha de ingreso es obligatoria.';
        } elseif (!isValidIsoDate($fechaIngreso)) {
            $error = 'La fecha de ingreso no es válida.';
        } else {
            $categoriaValida = false;
            foreach ($categorias as $categoria) {
                if ((int) $categoria['id_categoria'] === $idCategoria) {
                    $categoriaValida = true;
                    break;
                }
            }

            if (!$categoriaValida) {
                $error = 'La categoría seleccionada no es válida.';
            }

            if ($error === null) {
                $estadoValido = false;
                foreach ($estados as $estado) {
                    if ((int) $estado['id_estado_bien'] === $idEstadoBien) {
                        $estadoValido = true;
                        break;
                    }
                }

                if (!$estadoValido) {
                    $error = 'El estado seleccionado no es válido.';
                }
            }

            if ($error === null && $datos['costo'] !== null) {
                if (!is_numeric($datos['costo']) || (float) $datos['costo'] < 0) {
                    $error = 'El costo debe ser un número mayor o igual que 0.';
                }
            }

            if ($error === null && $datos['valor_estimado'] !== null) {
                if (!is_numeric($datos['valor_estimado']) || (float) $datos['valor_estimado'] < 0) {
                    $error = 'El valor estimado debe ser un número mayor o igual que 0.';
                }
            }

            if ($error === null) {
                if ($formaNombre === 'donacion') {
                    $datos['costo'] = null;
                }

                if ($formaNombre === 'compra' || $formaNombre === 'traslado') {
                    $datos['valor_estimado'] = null;
                }

                if ($formaNombre === 'compra') {
                    if ($proveedor === '') {
                        $error = 'El proveedor es obligatorio para la compra.';
                    } elseif ($numeroFactura === '') {
                        $error = 'El número de factura es obligatorio para la compra.';
                    } elseif ($fechaFactura === '') {
                        $error = 'La fecha de factura es obligatoria para la compra.';
                    } elseif (!isValidIsoDate($fechaFactura)) {
                        $error = 'La fecha de factura no es válida.';
                    } elseif ($datos['costo'] === null) {
                        $error = 'El costo es obligatorio para compra.';
                    } elseif ($idFormaPago > 0 && !$this->formaPagoActivaValida($idFormaPago, $formasPago)) {
                        // En edición la forma de pago NO es obligatoria (una compra histórica puede
                        // seguir sin ella si solo se edita otro dato); pero si el usuario eligió una,
                        // se revalida contra el catálogo activo — nunca se confía en el <select>.
                        $error = 'La forma de pago seleccionada no es válida.';
                    } elseif ($tieneGarantia === 1 && $tiempoGarantia === '') {
                        $error = 'El tiempo de garantía es obligatorio cuando la compra tiene garantía.';
                    } elseif ($tieneGarantia === 1 && !in_array($tiempoGarantia, self::MESES_GARANTIA_VALIDOS, true)) {
                        $error = 'El tiempo de garantía debe ser un número entero de 1 a 12 meses.';
                    }
                } elseif ($formaNombre === 'donacion') {
                    if ($procedencia === '') {
                        $error = 'La procedencia es obligatoria para la donación.';
                    } elseif ($entidadDonante === '') {
                        $error = 'La entidad donante es obligatoria para la donación.';
                    } elseif ($numeroActa === '') {
                        $error = 'El número de acta es obligatorio para la donación.';
                    } elseif ($fechaActa !== '' && !isValidIsoDate($fechaActa)) {
                        $error = 'La fecha de acta no es válida.';
                    } elseif ($datos['valor_estimado'] === null) {
                        $error = 'El valor estimado es obligatorio para donación.';
                    }
                } elseif ($formaNombre === 'traslado') {
                    if ($procedencia === '') {
                        $error = 'La procedencia es obligatoria para el traslado.';
                    } elseif ($unidadEjecutoraOrigen === '') {
                        $error = 'La unidad ejecutora de origen es obligatoria para el traslado.';
                    } elseif ($codigoUnidadOrigen === '') {
                        $error = 'El código de la unidad de origen es obligatorio para el traslado.';
                    } elseif ($numeroActa === '') {
                        $error = 'El número de acta es obligatorio para el traslado.';
                    } elseif ($fechaActa !== '' && !isValidIsoDate($fechaActa)) {
                        $error = 'La fecha de acta no es válida.';
                    } elseif ($datos['costo'] === null) {
                        $error = 'El costo es obligatorio para traslado.';
                    }
                }
            }
        }

        $documentosNuevos = [];
        $rutaFotografiaNueva = null;

        if ($error === null) {
            try {
                // "Agregar documento de respaldo": NUNCA reemplaza los anteriores. Admite varios
                // archivos en un mismo guardado; todos se acumulan en documentos_bien.
                $documentosNuevos = guardarDocumentosRespaldo($_FILES['documento_respaldo'] ?? null);
                $rutaFotografiaNueva = guardarFotografiaBien($_FILES['fotografia_bien'] ?? null);
            } catch (RuntimeException $errorArchivo) {
                $error = $errorArchivo->getMessage();
                // Si algún archivo entró y otro falló, limpiar los que sí se guardaron.
                eliminarDocumentosRespaldo($documentosNuevos);
                eliminarFotografiaBien($rutaFotografiaNueva);
                $documentosNuevos = [];
                $rutaFotografiaNueva = null;
            }
        }

        if ($error !== null) {
            $datosFormulario = array_merge(
                $datos,
                [
                    'proveedor' => $proveedor,
                    'numero_factura' => $numeroFactura,
                    'serie_factura' => $serieFactura,
                    'fecha_factura' => $fechaFactura,
                    'numero_liquidacion' => $numeroLiquidacion,
                    'id_forma_pago' => $idFormaPagoFinal ?? 0,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'procedencia' => $procedencia,
                    'entidad_donante' => $entidadDonante,
                    'numero_acta' => $numeroActa,
                    'fecha_acta' => $fechaActa,
                    'unidad_ejecutora_origen' => $unidadEjecutoraOrigen,
                    'codigo_unidad_origen' => $codigoUnidadOrigen,
                ]
            );

            $this->view('bienes/editar', [
                'bien' => $bien,
                'error' => $error,
                'datos' => $datosFormulario,
                'formaNombre' => $formaNombre,
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'formasPago' => $formasPago,
                'documentosBien' => $documentosBien,
                'tituloPagina' => 'Modificar bien',
            ], 'main');

            return;
        }

        // Columna legado `ingreso_*.documento_respaldo`: se reescribe con SU MISMO valor actual para
        // que este UPDATE no la altere. Los documentos (nuevos y antiguos) viven en documentos_bien.
        $datosIngresoCompra['documento_respaldo'] = $rutaDocumentoActual;
        $datosIngresoDonacion['documento_respaldo'] = $rutaDocumentoActual;
        $datosIngresoTraslado['documento_respaldo'] = $rutaDocumentoActual;

        // ---------------------------------------------------------------------------------------
        // Diff para historial_modificaciones_bien: SOLO los campos que realmente cambiaron. Se
        // arma ANTES de la transacción (comparando el estado leído de BD contra los valores ya
        // validados) y se inserta DENTRO de ella, junto al UPDATE — si el commit no ocurre, el
        // rollback descarta también estas filas. NO incluye: código interno (inmutable), SICOIN
        // (historial_sicoin es su fuente única), estado del bien (no editable aquí),
        // responsable/ubicación/asignación (los mueven los flujos de movimientos), QR ni timestamps.
        $normTexto = static function ($valor): ?string {
            if ($valor === null) {
                return null;
            }
            $valor = trim((string) $valor);
            return $valor === '' ? null : $valor;
        };
        // Dinero: "368", "368.0" y "368.00" representan el mismo valor -> no es un cambio.
        $normDinero = static function ($valor) use ($normTexto): ?string {
            $limpio = $normTexto($valor);
            if ($limpio === null || !is_numeric($limpio)) {
                return $limpio;
            }
            return rtrim(rtrim(number_format((float) $limpio, 2, '.', ''), '0'), '.');
        };
        $nombreCategoriaPorId = static function (int $id) use ($categorias): ?string {
            foreach ($categorias as $categoria) {
                if ((int) $categoria['id_categoria'] === $id) {
                    return (string) $categoria['nombre_categoria'];
                }
            }
            return null;
        };
        $nombreFormaPagoPorId = static function (?int $id) use ($formasPago): ?string {
            if ($id === null || $id <= 0) {
                return null;
            }
            foreach ($formasPago as $forma) {
                if ((int) $forma['id_forma_pago'] === $id) {
                    return (string) $forma['nombre_forma_pago'];
                }
            }
            return null;
        };
        $siNo = static fn ($valor): string => ((int) $valor === 1) ? 'Sí' : 'No';

        $detallesHistorial = [];
        $agregarDetalle = static function (string $seccion, string $campo, ?string $anterior, ?string $nuevo) use (&$detallesHistorial): void {
            if ($anterior === $nuevo) {
                return;
            }
            $detallesHistorial[] = [
                'seccion' => $seccion,
                'campo' => $campo,
                'valor_anterior' => $anterior,
                'valor_nuevo' => $nuevo,
            ];
        };

        // Sección general (tabla bienes).
        $agregarDetalle('general', 'Descripción', $normTexto($bien['descripcion'] ?? null), $normTexto($descripcion));
        $agregarDetalle('general', 'Marca', $normTexto($bien['marca'] ?? null), $normTexto($marca));
        $agregarDetalle('general', 'Modelo', $normTexto($bien['modelo'] ?? null), $normTexto($modelo));
        $agregarDetalle('general', 'Serie', $normTexto($bien['serie'] ?? null), $normTexto($serie));
        $agregarDetalle('general', 'Categoría', $normTexto($bien['nombre_categoria'] ?? null), $normTexto($nombreCategoriaPorId($idCategoria)));
        $agregarDetalle('general', 'Condición', $normTexto($bien['condicion_bien'] ?? null), $normTexto($condicionBien));
        $agregarDetalle('general', 'Observaciones', $normTexto($bien['observaciones'] ?? null), $normTexto($observaciones));
        $agregarDetalle('general', 'Fecha de ingreso', $normTexto($bien['fecha_ingreso'] ?? null), $normTexto($fechaIngreso));
        $agregarDetalle('general', 'Costo', $normDinero($bien['costo'] ?? null), $normDinero($datos['costo']));
        $agregarDetalle('general', 'Valor estimado', $normDinero($bien['valor_estimado'] ?? null), $normDinero($datos['valor_estimado']));

        // Sección de ingreso, según la forma (la forma de ingreso está bloqueada y no cambia).
        if ($formaNombre === 'compra') {
            $agregarDetalle('compra', 'Proveedor', $normTexto($datosEspecificos['proveedor'] ?? null), $normTexto($proveedor));
            $agregarDetalle('compra', 'Número de factura', $normTexto($datosEspecificos['numero_factura'] ?? null), $normTexto($numeroFactura));
            $agregarDetalle('compra', 'Serie de factura', $normTexto($datosEspecificos['serie_factura'] ?? null), $normTexto($serieFactura));
            $agregarDetalle('compra', 'Fecha de factura', $normTexto($datosEspecificos['fecha_factura'] ?? null), $normTexto($fechaFactura));
            $agregarDetalle('compra', 'Número de liquidación', $normTexto($datosEspecificos['numero_liquidacion'] ?? null), $normTexto($numeroLiquidacion));
            $agregarDetalle('compra', 'Forma de pago', $normTexto($datosEspecificos['forma_pago_nombre'] ?? null), $normTexto($nombreFormaPagoPorId($idFormaPagoFinal)));
            $agregarDetalle('compra', 'Garantía', $siNo($datosEspecificos['tiene_garantia'] ?? 0), $siNo($tieneGarantia));
            $agregarDetalle('compra', 'Tiempo de garantía (meses)', $normTexto($datosEspecificos['tiempo_garantia'] ?? null), $normTexto($datosIngresoCompra['tiempo_garantia']));
        } elseif ($formaNombre === 'donacion') {
            $agregarDetalle('donacion', 'Entidad donante', $normTexto($datosEspecificos['entidad_donante'] ?? null), $normTexto($entidadDonante));
            $agregarDetalle('donacion', 'Procedencia', $normTexto($datosEspecificos['procedencia'] ?? null), $normTexto($procedencia));
            $agregarDetalle('donacion', 'Número de acta', $normTexto($datosEspecificos['numero_acta'] ?? null), $normTexto($numeroActa));
            $agregarDetalle('donacion', 'Fecha de acta', $normTexto($datosEspecificos['fecha_acta'] ?? null), $normTexto($fechaActa));
        } elseif ($formaNombre === 'traslado') {
            $agregarDetalle('traslado', 'Unidad ejecutora de origen', $normTexto($datosEspecificos['unidad_ejecutora_origen'] ?? null), $normTexto($unidadEjecutoraOrigen));
            $agregarDetalle('traslado', 'Código de unidad de origen', $normTexto($datosEspecificos['codigo_unidad_origen'] ?? null), $normTexto($codigoUnidadOrigen));
            $agregarDetalle('traslado', 'Procedencia', $normTexto($datosEspecificos['procedencia'] ?? null), $normTexto($procedencia));
            $agregarDetalle('traslado', 'Número de acta', $normTexto($datosEspecificos['numero_acta'] ?? null), $normTexto($numeroActa));
            $agregarDetalle('traslado', 'Fecha de acta', $normTexto($datosEspecificos['fecha_acta'] ?? null), $normTexto($fechaActa));
        }

        // Documento de respaldo: los documentos se AGREGAN y se conservan — ya no existe la
        // sustitución. Se registra una sola entrada agrupada por guardado (nunca rutas ni nombres
        // internos), dentro del mismo evento "Modificación de información".
        if ($documentosNuevos !== []) {
            $totalDocsNuevos = count($documentosNuevos);
            $detallesHistorial[] = [
                'seccion' => 'documento',
                'campo' => 'Documento de respaldo',
                'valor_anterior' => null,
                'valor_nuevo' => $totalDocsNuevos === 1
                    ? 'Documento agregado'
                    : $totalDocsNuevos . ' documentos agregados',
            ];
        }

        // Fotografía del bien: igual criterio que el documento — solo "hubo archivo nuevo", nunca
        // rutas ni nombres internos. Sin flujo de eliminación: solo alta o sustitución.
        if ($rutaFotografiaNueva !== null) {
            $teniaFotografia = $normTexto($rutaFotografiaActual) !== null;
            $detallesHistorial[] = [
                'seccion' => 'imagen',
                'campo' => 'Fotografía del bien',
                'valor_anterior' => $teniaFotografia ? 'Fotografía anterior' : 'Sin fotografía',
                'valor_nuevo' => $teniaFotografia ? 'Fotografía sustituida' : 'Fotografía agregada',
            ];
        }

        // Un cambio real = al menos un detalle en historial_modificaciones_bien, o un alta de SICOIN
        // (que vive en su propia tabla). Si NO hubo ningún cambio real no se escribe ni el historial
        // ni la bitácora genérica MODIFICAR_BIEN.
        $grupoCambio = bin2hex(random_bytes(16));
        $fechaHoraCambio = date('Y-m-d H:i:s');
        $huboCambioReal = $detallesHistorial !== [] || $huboCambioSicoin;

        try {
            $bienModel->beginTransaction();

            $bienModel->actualizar($idBien, $datos);

            if ($formaNombre === 'compra') {
                $ingresoCompraModel->actualizarPorBienId($idBien, $datosIngresoCompra);
            } elseif ($formaNombre === 'donacion') {
                $ingresoDonacionModel->actualizarPorBienId($idBien, $datosIngresoDonacion);
            } elseif ($formaNombre === 'traslado') {
                $ingresoTrasladoModel->actualizarPorBienId($idBien, $datosIngresoTraslado);
            }

            $historialModificacionModel->registrarGrupo(
                $idBien,
                $grupoCambio,
                (int) $_SESSION['id_usuario'],
                $fechaHoraCambio,
                $detallesHistorial
            );

            if ($rutaFotografiaNueva !== null) {
                $bienModel->actualizarImagen($idBien, $rutaFotografiaNueva);
            }

            // Documento(s) agregado(s) -> documentos_bien, dentro de la transacción. Nunca se
            // sustituye ni se borra ninguno de los anteriores.
            if ($documentosNuevos !== []) {
                $fechaRegistroDocumentos = date('Y-m-d H:i:s');
                foreach ($documentosNuevos as $documento) {
                    $documentoBienModel->registrar([
                        'id_bien' => $idBien,
                        'tipo_ingreso' => $formaNombre,
                        'nombre_original' => $documento['nombre_original'],
                        'ruta_documento' => $documento['ruta'],
                        'fecha_registro' => $fechaRegistroDocumentos,
                        'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                    ]);
                }
            }

            if ($huboCambioSicoin) {
                $historialSicoinModel->registrar([
                    'id_bien' => $idBien,
                    'sicoin_anterior' => $sicoinAnteriorNormalizado,
                    'sicoin_nuevo' => $sicoinNuevoNormalizado,
                    'fecha_cambio' => date('Y-m-d H:i:s'),
                    'id_usuario' => (int) $_SESSION['id_usuario'],
                ]);

                $bitacoraModel->registrar(
                    idUsuario: (int) $_SESSION['id_usuario'],
                    accion: 'CAMBIAR_SICOIN_BIEN',
                    modulo: 'Bienes',
                    resultado: 'exitoso',
                    descripcion: 'Se actualizó el código SICOIN del bien con código interno ' . $datos['codigo_interno']
                        . ': ' . ($sicoinAnteriorNormalizado ?? 'Sin SICOIN') . ' → ' . $sicoinNuevoNormalizado . '.',
                    tablaAfectada: 'bienes',
                    idRegistroAfectado: $idBien,
                    ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                    usuarioIntentado: null
                );
            }

            if ($huboCambioCondicion) {
                $bitacoraModel->registrar(
                    idUsuario: (int) $_SESSION['id_usuario'],
                    accion: 'CAMBIAR_CONDICION_BIEN',
                    modulo: 'Bienes',
                    resultado: 'exitoso',
                    descripcion: 'Se actualizó la condición del bien con código interno ' . $datos['codigo_interno']
                        . ': ' . $condicionAnterior . ' → ' . $condicionBien . '.',
                    tablaAfectada: 'bienes',
                    idRegistroAfectado: $idBien,
                    ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                    usuarioIntentado: null
                );
            }

            // Resumen en bitácora SOLO si hubo algún cambio real. El detalle campo por campo ya
            // quedó estructurado en historial_modificaciones_bien; aquí no se repite el diff.
            if ($huboCambioReal) {
                $bitacoraModel->registrar(
                    idUsuario: (int) $_SESSION['id_usuario'],
                    accion: 'MODIFICAR_BIEN',
                    modulo: 'Bienes',
                    resultado: 'exitoso',
                    descripcion: 'Se modificó la información del bien con código interno ' . $datos['codigo_interno'] . '.',
                    tablaAfectada: 'bienes',
                    idRegistroAfectado: $idBien,
                    ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                    usuarioIntentado: null
                );
            }

            $bienModel->commit();

            // La fotografía anterior se borra DESPUÉS del commit (nunca antes): si la transacción
            // hubiera fallado, ese archivo seguiría siendo la evidencia válida del bien.
            if ($rutaFotografiaNueva !== null && $rutaFotografiaActual !== null && $rutaFotografiaActual !== $rutaFotografiaNueva) {
                eliminarFotografiaBien($rutaFotografiaActual);
            }

            setFlash('success', 'Cambios guardados correctamente', 'La información del bien fue actualizada.');

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            if ($bienModel->inTransaction()) {
                $bienModel->rollBack();
            }

            error_log('Error al actualizar el bien: ' . $e->getMessage());

            // Los documentos recién subidos se descartan: la transacción se revirtió y ninguna fila
            // de documentos_bien apunta a ellos.
            eliminarDocumentosRespaldo($documentosNuevos);

            // La fotografía recién subida se descarta: la transacción se revirtió y bienes.imagen_bien
            // sigue apuntando a la anterior (que no se tocó).
            eliminarFotografiaBien($rutaFotografiaNueva);

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible actualizar el bien. Verifique los datos e intente nuevamente.';

            $datosFormulario = array_merge(
                $datos,
                [
                    'proveedor' => $proveedor,
                    'numero_factura' => $numeroFactura,
                    'serie_factura' => $serieFactura,
                    'fecha_factura' => $fechaFactura,
                    'numero_liquidacion' => $numeroLiquidacion,
                    'id_forma_pago' => $idFormaPagoFinal ?? 0,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'procedencia' => $procedencia,
                    'entidad_donante' => $entidadDonante,
                    'numero_acta' => $numeroActa,
                    'fecha_acta' => $fechaActa,
                    'unidad_ejecutora_origen' => $unidadEjecutoraOrigen,
                    'codigo_unidad_origen' => $codigoUnidadOrigen,
                ]
            );

            $this->view('bienes/editar', [
                'bien' => $bien,
                'error' => $error,
                'datos' => $datosFormulario,
                'formaNombre' => $formaNombre,
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'formasPago' => $formasPago,
                'documentosBien' => $documentosBien,
                'tituloPagina' => 'Modificar bien',
            ], 'main');

            return;
        }
    }

    public function generarQr(): void
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

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            echo 'Bien no válido.';
            return;
        }

        $bienModel = $this->model('Bien');
        $bitacoraModel = $this->model('Bitacora');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        if (empty($bien['codigo_qr'])) {
            $accionBitacora = 'GENERAR_QR_BIEN';
            $descripcionBitacora = 'Se generó el código QR del bien con código interno ' . $bien['codigo_interno'] . '.';
        } else {
            $accionBitacora = 'REGENERAR_QR_BIEN';
            $descripcionBitacora = 'Se regeneró el código QR del bien con código interno ' . $bien['codigo_interno'] . '.';
        }

        try {
            $qr = $this->generarArchivoQr($idBien, $bien['codigo_sicoin'], $bien['codigo_interno']);

            $bienModel->actualizarQr($idBien, $qr['codigo_qr'], $qr['ruta_qr']);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: $accionBitacora,
                modulo: 'Bienes',
                resultado: 'exitoso',
                descripcion: $descripcionBitacora,
                tablaAfectada: 'bienes',
                idRegistroAfectado: $idBien,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            setFlash('success', 'Código QR actualizado', 'El código QR del bien fue generado correctamente.');

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            error_log('Error al generar QR del bien ' . $idBien . ': ' . $e->getMessage());

            setFlash('error', 'No fue posible completar la operación', 'No fue posible generar el código QR.');

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        }
    }

    public function imprimirQr(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idBien = (int) ($_GET['id'] ?? 0);

        if ($idBien <= 0) {
            echo 'Bien no válido.';
            return;
        }

        $bienModel = $this->model('Bien');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        $this->view('bienes/imprimir_qr', [
            'bien' => $bien,
        ]);
    }

    public function crearCategoria(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            $this->responderJson(401, [
                'ok' => false,
                'mensaje' => 'Debe iniciar sesión para realizar esta acción.',
            ]);
        }

        if (!tieneRol(['Administrador', 'Operativo'])) {
            $this->responderJson(403, [
                'ok' => false,
                'mensaje' => 'No tiene permisos para realizar esta acción.',
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, [
                'ok' => false,
                'mensaje' => 'Método no permitido.',
            ]);
        }

        $tokenEnviado = $_POST['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';

        if ($tokenEnviado === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $tokenEnviado)) {
            $this->responderJson(403, [
                'ok' => false,
                'mensaje' => 'Solicitud no válida.',
            ]);
        }

        $nombreCategoria = trim($_POST['nombre_categoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nombreCategoria === '') {
            $this->responderJson(422, [
                'ok' => false,
                'mensaje' => 'El nombre de la categoría es obligatorio.',
            ]);
        }

        if (strlen($nombreCategoria) > 100) {
            $this->responderJson(422, [
                'ok' => false,
                'mensaje' => 'El nombre de la categoría no puede superar los 100 caracteres.',
            ]);
        }

        $categoriaModel = $this->model('CategoriaBien');

        if ($categoriaModel->existeNombre($nombreCategoria)) {
            $this->responderJson(422, [
                'ok' => false,
                'mensaje' => 'Ya existe una categoría con ese nombre.',
            ]);
        }

        $bitacoraModel = $this->model('Bitacora');

        try {
            $categoriaModel->beginTransaction();

            $idCategoria = $categoriaModel->crear([
                'nombre_categoria' => $nombreCategoria,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
            ]);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_CATEGORIA_BIEN',
                modulo: 'Bienes',
                resultado: 'exitoso',
                descripcion: 'Se registró la categoría de bien "' . $nombreCategoria . '".',
                tablaAfectada: 'categorias_bien',
                idRegistroAfectado: $idCategoria,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $categoriaModel->commit();

            $this->responderJson(200, [
                'ok' => true,
                'mensaje' => 'Categoría registrada correctamente.',
                'categoria' => [
                    'id_categoria' => $idCategoria,
                    'nombre_categoria' => $nombreCategoria,
                ],
            ]);
        } catch (Throwable $e) {
            if ($categoriaModel->inTransaction()) {
                $categoriaModel->rollBack();
            }

            error_log('Error al registrar la categoría de bien: ' . $e->getMessage());

            if (
                $e instanceof PDOException
                && ($e->errorInfo[0] ?? null) === '23000'
                && str_contains($e->errorInfo[2] ?? '', 'uq_categorias_bien_nombre')
            ) {
                $this->responderJson(422, [
                    'ok' => false,
                    'mensaje' => 'Ya existe una categoría con ese nombre.',
                ]);
            }

            $this->responderJson(500, [
                'ok' => false,
                'mensaje' => 'No se pudo registrar la categoría.',
            ]);
        }
    }

    private function responderJson(int $codigoHttp, array $payload): void
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function generarArchivoQr(int $idBien, ?string $codigoSicoin, string $codigoInterno): array
    {
        $codigoQr = url('index.php?modulo=bienes&accion=ver&id=' . $idBien);

        $rutaQr = 'storage/qr/bien_' . $idBien . '.png';
        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaQr);

        // El contenido codificado en el QR es siempre la URL estable del bien — nunca el
        // código visible. La etiqueta bajo el QR es puramente visual (ver guardarQrConEtiqueta).
        $resultado = (new Builder())->build(
            writer: new PngWriter(),
            data: $codigoQr,
            size: 300,
            margin: 10
        );

        $codigoSicoinLimpio = trim((string) ($codigoSicoin ?? ''));
        $codigoMostrado = $codigoSicoinLimpio !== '' ? $codigoSicoinLimpio : $codigoInterno;

        // Builder::build() está tipado para devolver ResultInterface (que no declara getImage());
        // PngWriter en concreto siempre produce un GdResult/PngResult, pero se verifica en vez de
        // asumirlo para que sea seguro tanto en tiempo de ejecución como para el análisis estático.
        if (!$resultado instanceof GdResult) {
            throw new RuntimeException('El resultado del generador de QR no expone una imagen GD.');
        }

        $this->guardarQrConEtiqueta($resultado->getImage(), $codigoMostrado, $rutaAbsoluta);

        if (!file_exists($rutaAbsoluta) || filesize($rutaAbsoluta) <= 0) {
            if (file_exists($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
            }

            throw new RuntimeException('El archivo del QR no se generó correctamente.');
        }

        $info = getimagesize($rutaAbsoluta);

        if ($info === false || ($info['mime'] ?? '') !== 'image/png') {
            unlink($rutaAbsoluta);

            throw new RuntimeException('El archivo generado no es un PNG válido.');
        }

        return [
            'codigo_qr' => $codigoQr,
            'ruta_qr' => $rutaQr,
            'ruta_absoluta' => $rutaAbsoluta,
        ];
    }

    /**
     * Compone el PNG final: copia el QR generado por endroid/qr-code TAL CUAL (misma escala,
     * sin recorte ni redimensionado, preservando su quiet zone) sobre un lienzo más alto, y
     * agrega debajo la etiqueta "Código: XXXXX" — únicamente visual, no altera el contenido
     * codificado ni la ruta del archivo. Usa la fuente bitmap integrada de GD (sin TTF) para
     * no depender de una fuente externa que pueda no existir en otra instalación.
     */
    private function guardarQrConEtiqueta(\GdImage $imagenQr, string $codigoMostrado, string $rutaAbsoluta): void
    {
        $anchoQr = imagesx($imagenQr);
        $altoQr = imagesy($imagenQr);

        $fuente = 5;
        $anchoCaracter = imagefontwidth($fuente);
        $altoCaracter = imagefontheight($fuente);

        // Sanitiza el código antes de dibujarlo (quita caracteres de control) y lo convierte a
        // Latin-1 porque las fuentes bitmap integradas de GD no interpretan UTF-8 multibyte.
        $codigoSanitizado = preg_replace('/[\x00-\x1F\x7F]/', '', $codigoMostrado) ?? '';
        $texto = mb_convert_encoding('Código: ' . $codigoSanitizado, 'ISO-8859-1', 'UTF-8');
        // +1 por el desplazamiento horizontal de la negrita simulada (ver más abajo), para que
        // el centrado y el ancho del lienzo contemplen el trazo completo.
        $anchoTexto = $anchoCaracter * strlen($texto) + 1;

        $margenSeparacion = 10;
        $margenInferior = 10;
        $paddingLateral = 20;

        $anchoLienzo = max($anchoQr, $anchoTexto + $paddingLateral);
        $altoLienzo = $altoQr + $margenSeparacion + $altoCaracter + $margenInferior;

        $lienzo = imagecreatetruecolor($anchoLienzo, $altoLienzo);
        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
        $negro = imagecolorallocate($lienzo, 0, 0, 0);
        imagefill($lienzo, 0, 0, $blanco);

        // El QR se copia a escala 1:1 (imagecopy, no imagecopyresampled) — nunca se deforma
        // ni se reduce, y su quiet zone (margen de 10px ya incluido por el builder) queda intacta.
        $offsetXQr = (int) round(($anchoLienzo - $anchoQr) / 2);
        imagecopy($lienzo, $imagenQr, $offsetXQr, 0, 0, 0, $anchoQr, $altoQr); //no tocar tamaños

        $offsetXTexto = (int) round(($anchoLienzo - $anchoTexto) / 2);
        $offsetYTexto = $altoQr + $margenSeparacion;

        // Negrita simulada: imagestring() no soporta peso de fuente real. Se dibuja el mismo
        // texto dos veces con 1px de desplazamiento horizontal para engrosar el trazo sin
        // perder legibilidad ni introducir una fuente TTF externa.
        imagestring($lienzo, $fuente, $offsetXTexto, $offsetYTexto, $texto, $negro);
        imagestring($lienzo, $fuente, $offsetXTexto + 1, $offsetYTexto, $texto, $negro);

        imagepng($lienzo, $rutaAbsoluta);
        imagedestroy($lienzo);
        imagedestroy($imagenQr);
    }

    // ¿El id enviado corresponde a una forma de pago del catálogo ACTIVO? $formasPago es la lista
    // que ya devolvió FormaPago::getActivas() (solo activas). Mismo patrón que la validación de
    // categoría / estado / forma de ingreso más arriba: nunca se confía solo en el <select>.
    private function formaPagoActivaValida(int $idFormaPago, array $formasPago): bool
    {
        if ($idFormaPago <= 0) {
            return false;
        }

        foreach ($formasPago as $formaPago) {
            if ((int) $formaPago['id_forma_pago'] === $idFormaPago) {
                return true;
            }
        }

        return false;
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
        //unicos datos tomados en consideracion para determinar un bien duplicado

        if (str_contains($detalleError, 'uq_bienes_codigo_interno')) {
            return 'El código interno ingresado ya está registrado.';
        }

        if (str_contains($detalleError, 'uq_bienes_codigo_sicoin')) {
            return 'El código SICOIN ingresado ya está registrado.';
        }

        if (str_contains($detalleError, 'uq_bienes_serie')) {
            return 'La serie ingresada ya está registrada.';
        }

        return null;
    }
}

