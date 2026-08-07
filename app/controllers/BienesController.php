<?php

require_once __DIR__ . '/../core/Controller.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

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

        requireRole(['Administrador', 'Operativo']);

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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('bienes/crear', [
                'formasIngreso' => $formasIngreso,
                'categorias' => $categorias,
                'estados' => $estados,
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
                'datos' => [],
                'error' => null,
            ]);
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
        $idResponsableActual = trim($_POST['id_responsable_actual'] ?? '');
        $idUbicacionActual = (int) ($_POST['id_ubicacion_actual'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        $proveedor = trim($_POST['proveedor'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $serieFactura = trim($_POST['serie_factura'] ?? '');
        $fechaFactura = trim($_POST['fecha_factura'] ?? '');
        $numeroLiquidacion = trim($_POST['numero_liquidacion'] ?? '');
        $formaCompra = trim($_POST['forma_compra'] ?? '');
        $tieneGarantia = isset($_POST['tiene_garantia'])
            ? ((int) ($_POST['tiene_garantia'] === '1' || $_POST['tiene_garantia'] === 'on' ? 1 : 0))
            : 0;
        $tiempoGarantia = trim($_POST['tiempo_garantia'] ?? '');
        $documentoRespaldo = trim($_POST['documento_respaldo'] ?? '');
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
            'id_responsable_actual' => $idResponsableActual !== '' ? (int) $idResponsableActual : null,
            'id_ubicacion_actual' => $idUbicacionActual,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoCompra = [
            'id_bien' => null,
            'proveedor' => $proveedor !== '' ? $proveedor : null,
            'numero_factura' => $numeroFactura !== '' ? $numeroFactura : null,
            'serie_factura' => $serieFactura !== '' ? $serieFactura : null,
            'fecha_factura' => $fechaFactura !== '' ? $fechaFactura : null,
            'numero_liquidacion' => $numeroLiquidacion !== '' ? $numeroLiquidacion : null,
            'forma_compra' => $formaCompra !== '' ? $formaCompra : null,
            'tiene_garantia' => $tieneGarantia,
            'tiempo_garantia' => $tieneGarantia === 1 && $tiempoGarantia !== '' ? $tiempoGarantia : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoDonacion = [
            'id_bien' => null,
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'entidad_donante' => $entidadDonante !== '' ? $entidadDonante : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoTraslado = [
            'id_bien' => null,
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'unidad_ejecutora_origen' => $unidadEjecutoraOrigen !== '' ? $unidadEjecutoraOrigen : null,
            'codigo_unidad_origen' => $codigoUnidadOrigen !== '' ? $codigoUnidadOrigen : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
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
        } elseif ($idFormaIngreso <= 0) {
            $error = 'La forma de ingreso es obligatoria.';
        } elseif ($fechaIngreso === '') {
            $error = 'La fecha de ingreso es obligatoria.';
        } elseif (!$this->esFechaValida($fechaIngreso)) {
            $error = 'La fecha de ingreso no es válida.';
        } elseif ($idUbicacionActual <= 0) {
            $error = 'La ubicación actual es obligatoria.';
        } else {
            $formaSeleccionada = null;
            foreach ($formasIngreso as $forma) {
                if ((int) $forma['id_forma_ingreso'] === $idFormaIngreso) {
                    $formaSeleccionada = $forma;
                    break;
                }
            }

            if ($formaSeleccionada === null) {
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

            if ($error === null) {
                $ubicacionValida = false;
                foreach ($ubicaciones as $ubicacion) {
                    if ((int) $ubicacion['id_ubicacion'] === $idUbicacionActual) {
                        $ubicacionValida = true;
                        break;
                    }
                }

                if (!$ubicacionValida) {
                    $error = 'La ubicación seleccionada no es válida.';
                }
            }

            if ($error === null && $datos['id_responsable_actual'] !== null) {
                $responsableValido = false;
                foreach ($responsables as $responsable) {
                    if ((int) $responsable['id_responsable'] === $datos['id_responsable_actual']) {
                        $responsableValido = true;
                        break;
                    }
                }

                if (!$responsableValido) {
                    $error = 'El responsable seleccionado no es válido.';
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
                        } elseif (!$this->esFechaValida($fechaFactura)) {
                            $error = 'La fecha de factura no es válida.';
                        } elseif ($datos['costo'] === null) {
                            $error = 'El costo es obligatorio para compra.';
                        } elseif ($tieneGarantia === 1 && $tiempoGarantia === '') {
                            $error = 'El tiempo de garantía es obligatorio cuando la compra tiene garantía.';
                        }
                    } elseif ($nombreForma === 'donacion') {
                        if ($procedencia === '') {
                            $error = 'La procedencia es obligatoria para la donación.';
                        } elseif ($entidadDonante === '') {
                            $error = 'La entidad donante es obligatoria para la donación.';
                        } elseif ($numeroActa === '') {
                            $error = 'El número de acta es obligatorio para la donación.';
                        } elseif ($fechaActa !== '' && !$this->esFechaValida($fechaActa)) {
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
                        } elseif ($fechaActa !== '' && !$this->esFechaValida($fechaActa)) {
                            $error = 'La fecha de acta no es válida.';
                        } elseif ($datos['costo'] === null) {
                            $error = 'El costo es obligatorio para traslado.';
                        }
                    }
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
                    'forma_compra' => $formaCompra,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'documento_respaldo' => $documentoRespaldo,
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
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
            ]);
            return;
        }

        $bienModel = $this->model('Bien');
        $ingresoCompraModel = $this->model('IngresoCompra');
        $ingresoDonacionModel = $this->model('IngresoDonacion');
        $ingresoTrasladoModel = $this->model('IngresoTraslado');
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

            $bienModel->commit();

            try {
                $qr = $this->generarArchivoQr($idBien);

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

                $_SESSION['mensaje_error'] =
                    'El bien fue registrado correctamente, pero no fue posible generar el código QR. Puede generarlo desde la ficha del bien.';
            }

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            if ($bienModel->inTransaction()) {
                $bienModel->rollBack();
            }

            error_log('Error al registrar el bien: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible registrar el bien. Verifique los datos e intente nuevamente.';

            $datosFormulario = array_merge(
                $datos,
                [
                    'proveedor' => $proveedor,
                    'numero_factura' => $numeroFactura,
                    'serie_factura' => $serieFactura,
                    'fecha_factura' => $fechaFactura,
                    'numero_liquidacion' => $numeroLiquidacion,
                    'forma_compra' => $formaCompra,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'documento_respaldo' => $documentoRespaldo,
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
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
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

        $this->view('bienes/ver', [
            'bien' => $bien,
        ]);
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
        $bitacoraModel = $this->model('Bitacora');
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
                'id_responsable_actual' => $bien['id_responsable_actual'] !== null ? (int) $bien['id_responsable_actual'] : null,
                'id_ubicacion_actual' => (int) ($bien['id_ubicacion_actual'] ?? 0),
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
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
                'error' => null,
            ]);

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
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
        $costo = trim($_POST['costo'] ?? '');
        $valorEstimado = trim($_POST['valor_estimado'] ?? '');
        $idResponsableActual = trim($_POST['id_responsable_actual'] ?? '');
        $idUbicacionActual = (int) ($_POST['id_ubicacion_actual'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        $proveedor = trim($_POST['proveedor'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $serieFactura = trim($_POST['serie_factura'] ?? '');
        $fechaFactura = trim($_POST['fecha_factura'] ?? '');
        $numeroLiquidacion = trim($_POST['numero_liquidacion'] ?? '');
        $formaCompra = trim($_POST['forma_compra'] ?? '');
        $tieneGarantia = isset($_POST['tiene_garantia'])
            ? ((int) ($_POST['tiene_garantia'] === '1' || $_POST['tiene_garantia'] === 'on' ? 1 : 0))
            : 0;
        $tiempoGarantia = trim($_POST['tiempo_garantia'] ?? '');
        $documentoRespaldo = trim($_POST['documento_respaldo'] ?? '');
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
            'id_responsable_actual' => $idResponsableActual !== '' ? (int) $idResponsableActual : null,
            'id_ubicacion_actual' => $idUbicacionActual,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoCompra = [
            'proveedor' => $proveedor !== '' ? $proveedor : null,
            'numero_factura' => $numeroFactura !== '' ? $numeroFactura : null,
            'serie_factura' => $serieFactura !== '' ? $serieFactura : null,
            'fecha_factura' => $fechaFactura !== '' ? $fechaFactura : null,
            'numero_liquidacion' => $numeroLiquidacion !== '' ? $numeroLiquidacion : null,
            'forma_compra' => $formaCompra !== '' ? $formaCompra : null,
            'tiene_garantia' => $tieneGarantia,
            'tiempo_garantia' => $tieneGarantia === 1 && $tiempoGarantia !== '' ? $tiempoGarantia : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoDonacion = [
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'entidad_donante' => $entidadDonante !== '' ? $entidadDonante : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $datosIngresoTraslado = [
            'procedencia' => $procedencia !== '' ? $procedencia : null,
            'unidad_ejecutora_origen' => $unidadEjecutoraOrigen !== '' ? $unidadEjecutoraOrigen : null,
            'codigo_unidad_origen' => $codigoUnidadOrigen !== '' ? $codigoUnidadOrigen : null,
            'numero_acta' => $numeroActa !== '' ? $numeroActa : null,
            'fecha_acta' => $fechaActa !== '' ? $fechaActa : null,
            'documento_respaldo' => $documentoRespaldo !== '' ? $documentoRespaldo : null,
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
        } elseif ($fechaIngreso === '') {
            $error = 'La fecha de ingreso es obligatoria.';
        } elseif (!$this->esFechaValida($fechaIngreso)) {
            $error = 'La fecha de ingreso no es válida.';
        } elseif ($idUbicacionActual <= 0) {
            $error = 'La ubicación actual es obligatoria.';
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

            if ($error === null) {
                $ubicacionValida = false;
                foreach ($ubicaciones as $ubicacion) {
                    if ((int) $ubicacion['id_ubicacion'] === $idUbicacionActual) {
                        $ubicacionValida = true;
                        break;
                    }
                }

                if (!$ubicacionValida) {
                    $error = 'La ubicación seleccionada no es válida.';
                }
            }

            if ($error === null && $datos['id_responsable_actual'] !== null) {
                $responsableValido = false;
                foreach ($responsables as $responsable) {
                    if ((int) $responsable['id_responsable'] === $datos['id_responsable_actual']) {
                        $responsableValido = true;
                        break;
                    }
                }

                if (!$responsableValido) {
                    $error = 'El responsable seleccionado no es válido.';
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
                    } elseif (!$this->esFechaValida($fechaFactura)) {
                        $error = 'La fecha de factura no es válida.';
                    } elseif ($datos['costo'] === null) {
                        $error = 'El costo es obligatorio para compra.';
                    } elseif ($tieneGarantia === 1 && $tiempoGarantia === '') {
                        $error = 'El tiempo de garantía es obligatorio cuando la compra tiene garantía.';
                    }
                } elseif ($formaNombre === 'donacion') {
                    if ($procedencia === '') {
                        $error = 'La procedencia es obligatoria para la donación.';
                    } elseif ($entidadDonante === '') {
                        $error = 'La entidad donante es obligatoria para la donación.';
                    } elseif ($numeroActa === '') {
                        $error = 'El número de acta es obligatorio para la donación.';
                    } elseif ($fechaActa !== '' && !$this->esFechaValida($fechaActa)) {
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
                    } elseif ($fechaActa !== '' && !$this->esFechaValida($fechaActa)) {
                        $error = 'La fecha de acta no es válida.';
                    } elseif ($datos['costo'] === null) {
                        $error = 'El costo es obligatorio para traslado.';
                    }
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
                    'forma_compra' => $formaCompra,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'documento_respaldo' => $documentoRespaldo,
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
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
            ]);

            return;
        }

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

            $bienModel->commit();

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            if ($bienModel->inTransaction()) {
                $bienModel->rollBack();
            }

            error_log('Error al actualizar el bien: ' . $e->getMessage());

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
                    'forma_compra' => $formaCompra,
                    'tiene_garantia' => $tieneGarantia,
                    'tiempo_garantia' => $tiempoGarantia,
                    'documento_respaldo' => $documentoRespaldo,
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
                'responsables' => $responsables,
                'ubicaciones' => $ubicaciones,
            ]);

            return;
        }
    }

    public function cambiarCondicion(): void
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
        $bitacoraModel = $this->model('Bitacora');
        $bien = $bienModel->findById($idBien);

        if ($bien === false) {
            echo 'Bien no encontrado.';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('bienes/cambiar_condicion', [
                'bien' => $bien,
                'error' => null,
            ]);
            return;
        }

        verifyCsrf();

        $condicion = trim($_POST['condicion_bien'] ?? '');
        $condicionesValidas = ['Bueno', 'Regular', 'Malo'];

        if (!in_array($condicion, $condicionesValidas, true)) {
            $this->view('bienes/cambiar_condicion', [
                'bien' => $bien,
                'error' => 'La condición seleccionada no es válida.',
            ]);
            return;
        }

        $condicionAnterior = $bien['condicion_bien'] ?? '';

        try {
            $bienModel->beginTransaction();

            $bienModel->cambiarCondicion($idBien, $condicion);

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'CAMBIAR_CONDICION_BIEN',
                modulo: 'Bienes',
                resultado: 'exitoso',
                descripcion: 'Cambio de condición: ' . $condicionAnterior . ' → ' . $condicion . '.',
                tablaAfectada: 'bienes',
                idRegistroAfectado: $idBien,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $bienModel->commit();
        } catch (Throwable $e) {
            if ($bienModel->inTransaction()) {
                $bienModel->rollBack();
            }

            $this->view('bienes/cambiar_condicion', [
                'bien' => $bien,
                'error' => 'No fue posible cambiar la condición del bien. Intente nuevamente.',
            ]);
            return;
        }

        header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
        exit;
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
            $qr = $this->generarArchivoQr($idBien);

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

            $_SESSION['mensaje_exito'] = 'Código QR generado correctamente.';

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        } catch (Throwable $e) {
            error_log('Error al generar QR del bien ' . $idBien . ': ' . $e->getMessage());

            $_SESSION['mensaje_error'] = 'No fue posible generar el código QR.';

            header('Location: index.php?modulo=bienes&accion=ver&id=' . $idBien);
            exit;
        }
    }

    private function generarArchivoQr(int $idBien): array
    {
        $codigoQr = url('index.php?modulo=bienes&accion=ver&id=' . $idBien);

        $rutaQr = 'storage/qr/bien_' . $idBien . '.png';
        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaQr);

        $resultado = (new Builder())->build(
            writer: new PngWriter(),
            data: $codigoQr,
            size: 300,
            margin: 10
        );

        $resultado->saveToFile($rutaAbsoluta);

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

    private function esFechaValida(string $fecha): bool
    {
        $fechaObjeto = DateTime::createFromFormat('!Y-m-d', $fecha);

        return $fechaObjeto !== false && $fechaObjeto->format('Y-m-d') === $fecha;
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

