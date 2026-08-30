<?php

require_once __DIR__ . '/../core/Controller.php';
// Reutiliza Bien::CONDICIONES_VALIDAS (solo lectura) para el select de condición observada y para
// validarla en backend, en vez de duplicar el catálogo — mismo criterio que Préstamos/Devoluciones.
require_once __DIR__ . '/../models/Bien.php';

class VerificacionesController extends Controller
{
    private const ROLES_REGISTRO = ['Administrador', 'Operativo'];

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $verificacionModel = $this->model('VerificacionFisica');
        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');

        $tipoFiltro = (string) ($_GET['tipo'] ?? '');
        if (!in_array($tipoFiltro, ['individual', 'asignacion'], true)) {
            $tipoFiltro = '';
        }

        $filtros = [
            'busqueda' => trim((string) ($_GET['busqueda'] ?? '')),
            'id_bien' => (int) ($_GET['id_bien'] ?? 0),
            'id_responsable_registrado' => (int) ($_GET['id_responsable'] ?? 0),
            'id_ubicacion_registrada' => (int) ($_GET['id_ubicacion'] ?? 0),
            'localizado' => (string) ($_GET['localizado'] ?? ''),
            'con_diferencias' => (string) ($_GET['con_diferencias'] ?? ''),
            'tipo' => $tipoFiltro,
        ];

        // Únicamente para la tarjeta de identificación cuando la pantalla se usa como historial de
        // un bien puntual (id_bien en el filtro) — de lo contrario la lista de verificaciones podría
        // venir vacía (bien sin verificaciones aún) y no habría ninguna fila de la que tomar código
        // interno/descripción. No afecta la consulta principal ni el resultado filtrado.
        $bienModel = $filtros['id_bien'] > 0 ? $this->model('Bien') : null;
        $bienFiltro = $bienModel !== null ? $bienModel->findById($filtros['id_bien']) : false;

        $this->view('verificaciones/index', [
            'verificaciones' => $verificacionModel->getAll($filtros),
            'responsables' => $responsableModel->getActivos(),
            'ubicaciones' => $ubicacionModel->getActivas(),
            'filtros' => $filtros,
            'bienFiltro' => $bienFiltro !== false ? $bienFiltro : null,
            'tituloPagina' => $filtros['id_bien'] > 0 ? 'Historial de verificaciones' : 'Verificaciones físicas',
        ], 'main');
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idVerificacion = (int) ($_GET['id'] ?? 0);

        if ($idVerificacion <= 0) {
            echo 'Verificación no válida.';
            return;
        }

        $verificacionModel = $this->model('VerificacionFisica');
        $verificacion = $verificacionModel->findById($idVerificacion);

        if ($verificacion === false) {
            echo 'Verificación no encontrada.';
            return;
        }

        $this->view('verificaciones/ver', [
            'verificacion' => $verificacion,
            'tituloPagina' => 'Detalle de verificación física',
        ], 'main');
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_REGISTRO);

        $bienModel = $this->model('Bien');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $resultadosBusqueda = $busqueda !== '' ? $bienModel->buscar($busqueda) : [];

            $idBienPrecargado = (int) ($_GET['id_bien'] ?? 0);
            $bienSeleccionado = null;
            $errorBien = null;

            if ($idBienPrecargado > 0) {
                $bienEncontrado = $bienModel->findById($idBienPrecargado);

                if ($bienEncontrado === false) {
                    $errorBien = 'El bien indicado no existe.';
                } else {
                    $bienSeleccionado = $bienEncontrado;
                }
            }

            $this->view('verificaciones/crear', [
                'busqueda' => $busqueda,
                'resultadosBusqueda' => $resultadosBusqueda,
                'bienSeleccionado' => $bienSeleccionado,
                'errorBien' => $errorBien,
                'error' => null,
                'datosFormulario' => [],
                'tituloPagina' => 'Registrar verificación física',
            ], 'main');
            return;
        }

        verifyCsrf();

        $idBien = (int) ($_POST['id_bien'] ?? 0);
        $bienLocalizadoPost = (string) ($_POST['bien_localizado'] ?? '');
        $responsableCorrectoPost = (string) ($_POST['responsable_correcto'] ?? '');
        $ubicacionCorrectaPost = (string) ($_POST['ubicacion_correcta'] ?? '');
        $condicionObservadaPost = trim((string) ($_POST['condicion_observada'] ?? ''));
        $observacionesPost = trim((string) ($_POST['observaciones'] ?? ''));

        $datosFormulario = [
            'bien_localizado' => $bienLocalizadoPost,
            'responsable_correcto' => $responsableCorrectoPost,
            'ubicacion_correcta' => $ubicacionCorrectaPost,
            'condicion_observada' => $condicionObservadaPost,
            'observaciones' => $observacionesPost,
        ];

        // El Bien SIEMPRE se vuelve a consultar en backend: los snapshots nunca salen de campos
        // ocultos del formulario, precisamente para que nadie pueda manipular el POST y registrar
        // un responsable/ubicación/condición distintos a los que el sistema tiene en este instante.
        $bien = $idBien > 0 ? $bienModel->findById($idBien) : false;

        if ($bien === false) {
            $this->view('verificaciones/crear', [
                'busqueda' => '',
                'resultadosBusqueda' => [],
                'bienSeleccionado' => null,
                'errorBien' => 'Debe seleccionar un bien válido.',
                'error' => null,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar verificación física',
            ], 'main');
            return;
        }

        // Snapshots: se toman AQUÍ, del bien recién recargado, nunca del POST.
        $idResponsableRegistrado = $bien['id_responsable_actual'] !== null ? (int) $bien['id_responsable_actual'] : null;
        $idUbicacionRegistrada = $bien['id_ubicacion_actual'] !== null ? (int) $bien['id_ubicacion_actual'] : null;
        $condicionRegistrada = $bien['condicion_bien'];

        $error = null;

        if (!in_array($bienLocalizadoPost, ['0', '1'], true)) {
            $error = 'Debe indicar si el bien fue localizado físicamente.';
        }

        $bienLocalizado = $bienLocalizadoPost === '1';

        // Backend impone estos NULL sin importar lo que haya llegado en el POST: si el bien no fue
        // localizado, estas tres variables se quedan en null porque nunca se les asigna nada — no
        // se confía en que el frontend haya deshabilitado/vaciado los campos correctamente.
        $responsableCorrecto = null;
        $ubicacionCorrecta = null;
        $condicionObservada = null;

        if ($error === null && $bienLocalizado) {
            if (!in_array($responsableCorrectoPost, ['0', '1'], true)) {
                $error = 'Debe indicar si el responsable coincide con el registrado.';
            } elseif (!in_array($ubicacionCorrectaPost, ['0', '1'], true)) {
                $error = 'Debe indicar si la ubicación coincide con la registrada.';
            } elseif (!in_array($condicionObservadaPost, Bien::CONDICIONES_VALIDAS, true)) {
                $error = 'Debe indicar una condición física observada válida.';
            } else {
                $responsableCorrecto = $responsableCorrectoPost === '1';
                $ubicacionCorrecta = $ubicacionCorrectaPost === '1';
                $condicionObservada = $condicionObservadaPost;
            }
        }

        // tiene_diferencias NUNCA se toma del POST: se calcula siempre aquí, con las mismas reglas,
        // sin importar lo que el frontend haya enviado o dejado de deshabilitar.
        $tieneDiferencias = true;

        if ($error === null) {
            if (!$bienLocalizado) {
                $tieneDiferencias = true;
            } else {
                $tieneDiferencias = ($responsableCorrecto === false)
                    || ($ubicacionCorrecta === false)
                    || ($condicionObservada !== $condicionRegistrada);
            }
        }

        if ($error === null) {
            if (!$bienLocalizado && $observacionesPost === '') {
                $error = 'Debe indicar una observación cuando el bien no fue localizado.';
            } elseif ($bienLocalizado && $tieneDiferencias && $observacionesPost === '') {
                $error = 'Debe indicar una observación cuando existen diferencias en la verificación.';
            } elseif (mb_strlen($observacionesPost, 'UTF-8') > 2000) {
                $error = 'Las observaciones no pueden superar 2000 caracteres.';
            }
        }

        if ($error !== null) {
            $this->view('verificaciones/crear', [
                'busqueda' => '',
                'resultadosBusqueda' => [],
                'bienSeleccionado' => $bien,
                'errorBien' => null,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'tituloPagina' => 'Registrar verificación física',
            ], 'main');
            return;
        }

        $verificacionModel = $this->model('VerificacionFisica');
        $bitacoraModel = $this->model('Bitacora');

        $idVerificacion = $verificacionModel->crear([
            'id_bien' => $bien['id_bien'],
            'id_responsable_registrado' => $idResponsableRegistrado,
            'id_ubicacion_registrada' => $idUbicacionRegistrada,
            'condicion_registrada' => $condicionRegistrada,
            'id_usuario_verifica' => (int) $_SESSION['id_usuario'],
            'bien_localizado' => $bienLocalizado ? 1 : 0,
            'responsable_correcto' => $responsableCorrecto === null ? null : ($responsableCorrecto ? 1 : 0),
            'ubicacion_correcta' => $ubicacionCorrecta === null ? null : ($ubicacionCorrecta ? 1 : 0),
            'condicion_observada' => $condicionObservada,
            'tiene_diferencias' => $tieneDiferencias ? 1 : 0,
            'observaciones' => $observacionesPost !== '' ? $observacionesPost : null,
        ]);

        // Una sola entrada de bitácora por verificación, no por cada respuesta del formulario.
        $bitacoraModel->registrar(
            idUsuario: (int) $_SESSION['id_usuario'],
            accion: 'REGISTRAR_VERIFICACION_FISICA',
            modulo: 'Movimientos',
            resultado: 'exitoso',
            descripcion: sprintf(
                'Se registró la verificación física del bien %s. Localizado: %s. Resultado: %s.',
                $bien['codigo_interno'],
                $bienLocalizado ? 'Sí' : 'No',
                $tieneDiferencias ? 'Con diferencias' : 'Sin diferencias'
            ),
            tablaAfectada: 'verificaciones_fisicas',
            idRegistroAfectado: $idVerificacion,
            ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
            usuarioIntentado: null
        );

        // NUNCA se ejecuta ningún UPDATE sobre `bienes` en este flujo: la verificación documenta lo
        // encontrado, no corrige el registro. Ver auditoría — cualquier corrección real debe pasar
        // por el flujo administrativo correspondiente (editar bien, cambiar condición, traslado, etc).
        setFlash('success', 'Verificación registrada correctamente', 'El resultado de la verificación física fue guardado en el historial del bien.');

        header('Location: index.php?modulo=verificaciones&accion=ver&id=' . $idVerificacion);
        exit;
    }

    // =====================================================================================
    // VERIFICACIÓN FÍSICA POR ASIGNACIÓN (coexiste con la individual de arriba, no la reemplaza).
    // Revisa en una sola jornada todos los bienes vigentes cargados a una asignación 'Asignada',
    // reutilizando bien por bien la MISMA semántica de la individual. NO modifica el bien: solo
    // guarda el snapshot observado (verificaciones_fisicas) + una cabecera de jornada
    // (verificaciones_asignacion) con el resumen.
    // =====================================================================================

    // GET. Sin ?id_asignacion => pantalla de selección de asignación elegible. Con ?id_asignacion
    // válido => pantalla de jornada con los bienes vigentes ya cargados. Entrada compartida por el
    // botón "Verificación por asignación" del módulo y por la acción "Verificar" de Asignaciones.
    public function porAsignacion(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_REGISTRO);

        $verificacionAsignacionModel = $this->model('VerificacionAsignacion');
        $idAsignacion = (int) ($_GET['id_asignacion'] ?? 0);

        if ($idAsignacion <= 0) {
            $q = trim((string) ($_GET['q'] ?? ''));

            $this->view('verificaciones/por_asignacion', [
                'modo' => 'seleccion',
                'asignacionesElegibles' => $verificacionAsignacionModel->getAsignacionesElegibles($q !== '' ? $q : null),
                'q' => $q,
                'asignacion' => null,
                'bienes' => [],
                'error' => null,
                'datosFormulario' => [],
                'tituloPagina' => 'Verificación física por asignación',
            ], 'main');
            return;
        }

        $asignacion = $verificacionAsignacionModel->findElegible($idAsignacion);

        if ($asignacion === false) {
            setFlash(
                'error',
                'Asignación no disponible para verificación',
                'La asignación seleccionada no existe, no está activa o ya no tiene bienes vigentes que verificar.'
            );
            header('Location: index.php?modulo=verificaciones&accion=por_asignacion');
            exit;
        }

        $this->view('verificaciones/por_asignacion', [
            'modo' => 'jornada',
            'asignacion' => $asignacion,
            'bienes' => $verificacionAsignacionModel->getBienesVigentes($idAsignacion),
            'error' => null,
            'datosFormulario' => [],
            'asignacionesElegibles' => [],
            'q' => '',
            'tituloPagina' => 'Verificación física por asignación',
        ], 'main');
    }

    // POST. Finalización ATÓMICA de la jornada: bloquea la asignación, revalida server-side los
    // bienes realmente esperados (nunca se confía en los IDs del navegador), valida cada bien con
    // las reglas de la individual, guarda cabecera + una verificación por bien + una sola entrada
    // de bitácora, y confirma. Cualquier fallo => rollback completo.
    public function guardarAsignacion(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(self::ROLES_REGISTRO);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?modulo=verificaciones&accion=por_asignacion');
            exit;
        }

        verifyCsrf();

        $verificacionAsignacionModel = $this->model('VerificacionAsignacion');
        $asignacionModel = $this->model('Asignacion');
        $verificacionModel = $this->model('VerificacionFisica');
        $bitacoraModel = $this->model('Bitacora');

        $idAsignacion = (int) ($_POST['id_asignacion'] ?? 0);
        $idsEnviados = array_values(array_unique(array_map('intval', (array) ($_POST['bienes_esperados'] ?? []))));
        sort($idsEnviados);

        // Respuestas por bien, tal cual llegaron (para revalidar y para re-pintar el formulario si
        // hay error). Las claves son id_bien.
        $datosFormulario = $this->extraerRespuestasJornada();

        $asignacion = $idAsignacion > 0 ? $verificacionAsignacionModel->findElegible($idAsignacion) : false;

        if ($asignacion === false) {
            setFlash(
                'error',
                'Asignación no disponible para verificación',
                'La asignación seleccionada ya no existe, no está activa o ya no tiene bienes vigentes que verificar.'
            );
            header('Location: index.php?modulo=verificaciones&accion=por_asignacion');
            exit;
        }

        $total = 0;
        $totLoc = 0;
        $totDif = 0;
        $pctLoc = 0.0;
        $pctSinDif = 0.0;
        $idCabecera = 0;

        try {
            $verificacionModel->beginTransaction();

            $asignacionBloqueada = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacionBloqueada === false || $asignacionBloqueada['estado_asignacion'] !== 'Asignada') {
                throw new RuntimeException('La asignación ya no está activa. No se registró ninguna verificación.');
            }

            $bienesVigentes = $verificacionAsignacionModel->getBienesVigentesForUpdate($idAsignacion);

            if ($bienesVigentes === []) {
                throw new RuntimeException('La asignación ya no tiene bienes vigentes para verificar. No se registró ninguna verificación.');
            }

            $idsVigentes = array_map(static fn (array $b): int => (int) $b['id_bien'], $bienesVigentes);
            sort($idsVigentes);

            if ($idsVigentes !== $idsEnviados) {
                throw new RuntimeException(
                    'La composición de la asignación cambió mientras realizaba la verificación. Se recargó la lista con los bienes vigentes: revise nuevamente y vuelva a finalizar.'
                );
            }

            $filas = [];

            foreach ($bienesVigentes as $bien) {
                $idBien = (int) $bien['id_bien'];
                $codigo = (string) $bien['codigo_interno'];
                $respuesta = $datosFormulario[$idBien] ?? null;

                if ($respuesta === null) {
                    throw new RuntimeException(sprintf('Falta la revisión del bien %s.', $codigo));
                }

                $localizadoPost = (string) $respuesta['bien_localizado'];

                if (!in_array($localizadoPost, ['0', '1'], true)) {
                    throw new RuntimeException(sprintf('Debe indicar si el bien %s fue localizado físicamente.', $codigo));
                }

                $localizado = $localizadoPost === '1';

                $responsableCorrecto = null;
                $ubicacionCorrecta = null;
                $condicionObservada = null;

                if ($localizado) {
                    if (!in_array((string) $respuesta['responsable_correcto'], ['0', '1'], true)) {
                        throw new RuntimeException(sprintf('Debe indicar si el responsable del bien %s coincide con el registrado.', $codigo));
                    }

                    if (!in_array((string) $respuesta['ubicacion_correcta'], ['0', '1'], true)) {
                        throw new RuntimeException(sprintf('Debe indicar si la ubicación del bien %s coincide con la registrada.', $codigo));
                    }

                    if (!in_array((string) $respuesta['condicion_observada'], Bien::CONDICIONES_VALIDAS, true)) {
                        throw new RuntimeException(sprintf('Debe indicar una condición física observada válida para el bien %s.', $codigo));
                    }

                    $responsableCorrecto = (string) $respuesta['responsable_correcto'] === '1';
                    $ubicacionCorrecta = (string) $respuesta['ubicacion_correcta'] === '1';
                    $condicionObservada = (string) $respuesta['condicion_observada'];
                }

                // Snapshots: SIEMPRE del bien recién bloqueado, nunca del POST (idéntico criterio
                // que la verificación individual).
                $idResponsableRegistrado = $bien['id_responsable_actual'] !== null ? (int) $bien['id_responsable_actual'] : null;
                $idUbicacionRegistrada = $bien['id_ubicacion_actual'] !== null ? (int) $bien['id_ubicacion_actual'] : null;
                $condicionRegistrada = $bien['condicion_bien'];

                if (!$localizado) {
                    $tieneDiferencias = true;
                } else {
                    $tieneDiferencias = ($responsableCorrecto === false)
                        || ($ubicacionCorrecta === false)
                        || ($condicionObservada !== $condicionRegistrada);
                }

                $observaciones = trim((string) $respuesta['observaciones']);

                if (!$localizado && $observaciones === '') {
                    throw new RuntimeException(sprintf('Debe indicar una observación para el bien %s (no localizado).', $codigo));
                }

                if ($localizado && $tieneDiferencias && $observaciones === '') {
                    throw new RuntimeException(sprintf('Debe indicar una observación para el bien %s (presenta diferencias).', $codigo));
                }

                if (mb_strlen($observaciones, 'UTF-8') > 2000) {
                    throw new RuntimeException(sprintf('Las observaciones del bien %s no pueden superar 2000 caracteres.', $codigo));
                }

                if ($localizado) {
                    $totLoc++;
                }

                if ($tieneDiferencias) {
                    $totDif++;
                }

                $filas[] = [
                    'id_bien' => $idBien,
                    'id_responsable_registrado' => $idResponsableRegistrado,
                    'id_ubicacion_registrada' => $idUbicacionRegistrada,
                    'condicion_registrada' => $condicionRegistrada,
                    'bien_localizado' => $localizado ? 1 : 0,
                    'responsable_correcto' => $responsableCorrecto === null ? null : ($responsableCorrecto ? 1 : 0),
                    'ubicacion_correcta' => $ubicacionCorrecta === null ? null : ($ubicacionCorrecta ? 1 : 0),
                    'condicion_observada' => $condicionObservada,
                    'tiene_diferencias' => $tieneDiferencias ? 1 : 0,
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                ];
            }

            $total = count($filas);
            $totNoLoc = $total - $totLoc;
            $totSinDif = $total - $totDif;
            $pctLoc = $total > 0 ? round($totLoc / $total * 100, 2) : 0.0;
            $pctSinDif = $total > 0 ? round($totSinDif / $total * 100, 2) : 0.0;

            $idResponsableCabecera = (int) $asignacionBloqueada['id_responsable'];
            $idUbicacionCabecera = (int) $asignacionBloqueada['id_ubicacion'];

            $idCabecera = $verificacionAsignacionModel->crear([
                'id_asignacion' => $idAsignacion,
                'numero_asignacion' => $asignacionBloqueada['numero_asignacion'],
                'id_responsable_registrado' => $idResponsableCabecera > 0 ? $idResponsableCabecera : null,
                'id_ubicacion_registrada' => $idUbicacionCabecera > 0 ? $idUbicacionCabecera : null,
                'id_usuario_verifica' => (int) $_SESSION['id_usuario'],
                'total_esperado' => $total,
                'total_revisado' => $total,
                'total_localizados' => $totLoc,
                'total_no_localizados' => $totNoLoc,
                'total_con_diferencias' => $totDif,
                'total_sin_diferencias' => $totSinDif,
                'porcentaje_localizacion' => number_format($pctLoc, 2, '.', ''),
                'porcentaje_sin_diferencias' => number_format($pctSinDif, 2, '.', ''),
                'observaciones' => null,
            ]);

            foreach ($filas as $fila) {
                $fila['id_usuario_verifica'] = (int) $_SESSION['id_usuario'];
                $fila['id_verificacion_asignacion'] = $idCabecera;
                $verificacionModel->crear($fila);
            }

            // Una sola entrada de bitácora por jornada (no una por bien) — el detalle por bien vive
            // en verificaciones_fisicas para el historial del bien.
            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'FINALIZAR_VERIFICACION_ASIGNACION',
                modulo: 'Movimientos',
                resultado: 'exitoso',
                descripcion: sprintf(
                    'Verificación física por asignación %s. Responsable: %s. Bienes revisados: %d. Localizados: %d. No localizados: %d. Con diferencias: %d. Localización: %s %%. Sin diferencias: %s %%.',
                    $asignacionBloqueada['numero_asignacion'],
                    $asignacion['responsable_nombre'] ?? '—',
                    $total,
                    $totLoc,
                    $totNoLoc,
                    $totDif,
                    $this->formatoPorcentaje($pctLoc),
                    $this->formatoPorcentaje($pctSinDif)
                ),
                tablaAfectada: 'verificaciones_asignacion',
                idRegistroAfectado: $idCabecera,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $verificacionModel->commit();
        } catch (Throwable $e) {
            if ($verificacionModel->inTransaction()) {
                $verificacionModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar la verificación por asignación: ' . $e->getMessage());
                $error = 'No fue posible registrar la verificación por asignación. Revise los datos e intente nuevamente.';
            }

            // Se re-pinta la jornada con la composición VIGENTE (no la que trajo el POST) y se
            // conservan las respuestas ya tecleadas para los bienes que siguen presentes.
            $this->view('verificaciones/por_asignacion', [
                'modo' => 'jornada',
                'asignacion' => $asignacion,
                'bienes' => $verificacionAsignacionModel->getBienesVigentes($idAsignacion),
                'error' => $error,
                'datosFormulario' => $datosFormulario,
                'asignacionesElegibles' => [],
                'q' => '',
                'tituloPagina' => 'Verificación física por asignación',
            ], 'main');
            return;
        }

        setFlash(
            'success',
            'Verificación por asignación finalizada',
            sprintf(
                'Se registraron %d verificaciones. Localización: %s %%. Sin diferencias: %s %%.',
                $total,
                $this->formatoPorcentaje($pctLoc),
                $this->formatoPorcentaje($pctSinDif)
            )
        );

        header('Location: index.php?modulo=verificaciones&accion=ver_asignacion&id=' . $idCabecera);
        exit;
    }

    // Detalle de una jornada ya registrada: cabecera + porcentajes + tabla de todos los bienes
    // revisados con su resultado. Solo consulta (sesión activa, sin requireRole — igual que ver()).
    public function verAsignacion(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idVerificacionAsignacion = (int) ($_GET['id'] ?? 0);

        if ($idVerificacionAsignacion <= 0) {
            echo 'Verificación por asignación no válida.';
            return;
        }

        $verificacionAsignacionModel = $this->model('VerificacionAsignacion');
        $cabecera = $verificacionAsignacionModel->findById($idVerificacionAsignacion);

        if ($cabecera === false) {
            echo 'Verificación por asignación no encontrada.';
            return;
        }

        $this->view('verificaciones/ver_asignacion', [
            'cabecera' => $cabecera,
            'bienesRevisados' => $verificacionAsignacionModel->getBienesRevisados($idVerificacionAsignacion),
            'tituloPagina' => 'Detalle de verificación por asignación',
        ], 'main');
    }

    // Normaliza las respuestas de la jornada que llegan como arrays indexados por id_bien:
    // bien_localizado[ID], responsable_correcto[ID], ubicacion_correcta[ID], condicion_observada[ID],
    // observaciones[ID]. Devuelve [idBien => ['bien_localizado' => ..., ...]] con strings crudos;
    // toda la validación real ocurre en guardarAsignacion() contra los bienes revalidados.
    private function extraerRespuestasJornada(): array
    {
        $localizado = (array) ($_POST['bien_localizado'] ?? []);
        $responsable = (array) ($_POST['responsable_correcto'] ?? []);
        $ubicacion = (array) ($_POST['ubicacion_correcta'] ?? []);
        $condicion = (array) ($_POST['condicion_observada'] ?? []);
        $observaciones = (array) ($_POST['observaciones'] ?? []);

        $ids = array_map('intval', array_keys($localizado + $responsable + $ubicacion + $condicion + $observaciones));

        // Cualquier valor no escalar (POST manipulado con arrays anidados) se descarta como cadena
        // vacía: nunca debe llegar un "Array" a la validación ni provocar un warning de conversión.
        $escalar = static fn ($valor): string => is_scalar($valor) ? (string) $valor : '';

        $resultado = [];

        foreach (array_unique($ids) as $idBien) {
            if ($idBien <= 0) {
                continue;
            }

            $resultado[$idBien] = [
                'bien_localizado' => $escalar($localizado[$idBien] ?? ''),
                'responsable_correcto' => $escalar($responsable[$idBien] ?? ''),
                'ubicacion_correcta' => $escalar($ubicacion[$idBien] ?? ''),
                'condicion_observada' => trim($escalar($condicion[$idBien] ?? '')),
                'observaciones' => $escalar($observaciones[$idBien] ?? ''),
            ];
        }

        return $resultado;
    }

    // Porcentaje con hasta 2 decimales, sin ceros de relleno (90, 87.5, 33.33).
    private function formatoPorcentaje(float $valor): string
    {
        $texto = number_format($valor, 2, '.', '');

        if (str_contains($texto, '.')) {
            $texto = rtrim(rtrim($texto, '0'), '.');
        }

        return $texto;
    }
}
