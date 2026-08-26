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

        $filtros = [
            'busqueda' => trim((string) ($_GET['busqueda'] ?? '')),
            'id_bien' => (int) ($_GET['id_bien'] ?? 0),
            'id_responsable_registrado' => (int) ($_GET['id_responsable'] ?? 0),
            'id_ubicacion_registrada' => (int) ($_GET['id_ubicacion'] ?? 0),
            'localizado' => (string) ($_GET['localizado'] ?? ''),
            'con_diferencias' => (string) ($_GET['con_diferencias'] ?? ''),
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
        header('Location: index.php?modulo=verificaciones&accion=ver&id=' . $idVerificacion);
        exit;
    }
}
