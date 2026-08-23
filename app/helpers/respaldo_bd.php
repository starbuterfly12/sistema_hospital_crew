<?php

// Servicio del módulo Respaldos: encapsula todo lo relacionado con generar un dump de la base de
// datos vía mysqldump. RespaldosController solo coordina (sesión, rol, CSRF, metadata, bitácora);
// ninguna llamada a proc_open/credenciales/archivos temporales vive en el controlador.

if (!function_exists('resolverRutaMysqldump')) {
    // Deriva la ruta esperada de mysqldump.exe a partir de la propia ubicación del proyecto dentro
    // de XAMPP (sistema_hospital -> htdocs -> xampp), en vez de asumir una única ruta fija. Se deja
    // como segundo candidato la ruta estándar de esta instalación, ya auditada y aprobada.
    function resolverRutaMysqldump(): ?string
    {
        $raizProyecto = dirname(__DIR__, 2);
        $raizHtdocs = dirname($raizProyecto);
        $raizXampp = dirname($raizHtdocs);

        $candidatos = [
            $raizXampp . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        ];

        foreach ($candidatos as $candidato) {
            if (is_file($candidato) && is_executable($candidato)) {
                return $candidato;
            }
        }

        return null;
    }
}

if (!function_exists('generarNombreArchivoRespaldoBD')) {
    function generarNombreArchivoRespaldoBD(string $directorioDestino): string
    {
        $base = 'respaldo_sistema_hospital_' . date('Y-m-d_H-i-s');
        $nombre = $base . '.sql';
        $sufijo = 2;

        while (is_file($directorioDestino . DIRECTORY_SEPARATOR . $nombre)) {
            $nombre = $base . '_' . $sufijo . '.sql';
            $sufijo++;
        }

        return $nombre;
    }
}

if (!function_exists('crearArchivoCredencialesTemporal')) {
    // Archivo de opciones de MySQL/MariaDB ([client]) fuera del repo, para nunca pasar la contraseña
    // como argumento de línea de comandos (visible en la lista de procesos de Windows). El valor va
    // entre comillas dobles para tolerar espacios/'#'; se asume que la contraseña no contiene comillas
    // dobles, razonable para el tipo de credenciales de esta instalación.
    function crearArchivoCredencialesTemporal(array $config): string
    {
        $ruta = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/')
            . DIRECTORY_SEPARATOR . 'respaldo_cred_' . bin2hex(random_bytes(16)) . '.cnf';

        $contenido = "[client]\n"
            . 'user="' . $config['username'] . "\"\n"
            . 'password="' . $config['password'] . "\"\n"
            . 'host="' . $config['host'] . "\"\n"
            . "default-character-set=utf8mb4\n";

        if (file_put_contents($ruta, $contenido) === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal de credenciales.');
        }

        @chmod($ruta, 0600);

        return $ruta;
    }
}

if (!function_exists('ejecutarMysqldump')) {
    // Comando en forma de array (evita pasar por cmd.exe). --defaults-extra-file va inmediatamente
    // después del ejecutable porque MariaDB/MySQL exige que las opciones de "defaults" sean de las
    // primeras en la línea de comandos. El stdout de mysqldump (el propio dump SQL) se copia directo
    // al archivo temporal sin acumularlo en memoria; stdout y stderr se leen en paralelo mediante
    // sondeo no bloqueante (NO stream_select: en Windows stream_select no funciona de forma fiable
    // sobre pipes de proc_open —solo sobre sockets—, y probarlo aquí provocó un cuelgue real durante
    // la auditoría técnica). El sondeo evita el mismo deadlock clásico sin depender de esa función.
    function ejecutarMysqldump(
        string $rutaMysqldump,
        string $rutaCredenciales,
        string $baseDatos,
        string $rutaDestinoTemporal
    ): array {
        $comando = [
            $rutaMysqldump,
            '--defaults-extra-file=' . $rutaCredenciales,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--add-drop-table',
            '--default-character-set=utf8mb4',
            $baseDatos,
        ];

        $rutaStderr = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/')
            . DIRECTORY_SEPARATOR . 'respaldo_stderr_' . bin2hex(random_bytes(8)) . '.log';

        // Descriptores de archivo en vez de pipes leídos desde PHP: en pruebas reales sobre este
        // entorno (Windows + PHP 8.2), leer los pipes de stdout/stderr de proc_open en modo no
        // bloqueante (con o sin stream_select) se colgó de forma indefinida — fread() dejaba de
        // devolver datos nuevos aunque el proceso seguía vivo. Con ['file', ...], el propio sistema
        // operativo escribe stdout/stderr directo a disco sin que PHP tenga que copiarlos: evita el
        // problema por completo y de paso el dump nunca pasa por memoria de PHP.
        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['file', $rutaDestinoTemporal, 'w'],
            2 => ['file', $rutaStderr, 'w'],
        ];

        $proceso = proc_open($comando, $descriptores, $tuberias);

        if (!is_resource($proceso)) {
            @unlink($rutaStderr);

            return ['exito' => false, 'codigo_salida' => null, 'error' => 'No se pudo iniciar el proceso de respaldo.'];
        }

        fclose($tuberias[0]);

        $codigoSalida = proc_close($proceso);

        $stderr = is_file($rutaStderr) ? (string) file_get_contents($rutaStderr) : '';
        @unlink($rutaStderr);

        return [
            'exito' => $codigoSalida === 0,
            'codigo_salida' => $codigoSalida,
            'error' => $codigoSalida !== 0 ? trim($stderr) : null,
        ];
    }
}

if (!function_exists('validarCabeceraDump')) {
    function validarCabeceraDump(string $rutaArchivo): bool
    {
        $manejador = fopen($rutaArchivo, 'rb');

        if ($manejador === false) {
            return false;
        }

        $inicio = fread($manejador, 200);
        fclose($manejador);

        if ($inicio === false) {
            return false;
        }

        return str_contains($inicio, '-- MySQL dump') || str_contains($inicio, '-- MariaDB dump');
    }
}

if (!function_exists('resolverRutaFisicaRespaldo')) {
    // Nunca confía en la ruta guardada en BD por sí sola: resuelve con realpath() y confirma que el
    // resultado cae dentro de storage/respaldos. Comparación en minúsculas porque Windows no
    // distingue mayúsculas en rutas de archivo.
    function resolverRutaFisicaRespaldo(string $rutaRelativa): ?string
    {
        $directorioRespaldos = realpath(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'respaldos'
        );

        if ($directorioRespaldos === false) {
            return null;
        }

        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        $rutaReal = realpath($rutaAbsoluta);

        if ($rutaReal === false) {
            return null;
        }

        $directorioNormalizado = strtolower(rtrim($directorioRespaldos, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR;
        $rutaNormalizada = strtolower($rutaReal);

        if (!str_starts_with($rutaNormalizada, $directorioNormalizado)) {
            return null;
        }

        return $rutaReal;
    }
}

if (!function_exists('eliminarArchivoRespaldoFisico')) {
    function eliminarArchivoRespaldoFisico(string $rutaRelativa): void
    {
        $rutaAbsoluta = resolverRutaFisicaRespaldo($rutaRelativa);

        if ($rutaAbsoluta !== null && is_file($rutaAbsoluta)) {
            @unlink($rutaAbsoluta);
        }
    }
}

if (!function_exists('formatearTamanoArchivo')) {
    function formatearTamanoArchivo(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}

if (!function_exists('generarArchivoRespaldoBaseDatos')) {
    // Orquesta todo el proceso a nivel de sistema operativo: resuelve mysqldump, arma credenciales
    // temporales, ejecuta el dump a un archivo temporal dentro de storage/respaldos, valida, y solo
    // si todo es correcto lo promueve (rename) al nombre final. No toca la tabla `respaldos` ni la
    // bitácora: eso es responsabilidad del controlador, que decide qué hacer con el resultado.
    function generarArchivoRespaldoBaseDatos(): array
    {
        $directorioRespaldos = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'respaldos';

        if (!is_dir($directorioRespaldos) || !is_writable($directorioRespaldos)) {
            error_log('Respaldos: el directorio storage/respaldos no existe o no tiene permisos de escritura.');

            return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
        }

        $rutaMysqldump = resolverRutaMysqldump();

        if ($rutaMysqldump === null) {
            error_log('Respaldos: no se encontró un ejecutable de mysqldump utilizable en este entorno.');

            return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
        }

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Database.php';

        $nombreFinal = generarNombreArchivoRespaldoBD($directorioRespaldos);
        $rutaFinal = $directorioRespaldos . DIRECTORY_SEPARATOR . $nombreFinal;
        $rutaTemporalDump = $directorioRespaldos . DIRECTORY_SEPARATOR . '.tmp_respaldo_' . bin2hex(random_bytes(8)) . '.sql';

        $rutaCredenciales = null;

        try {
            $rutaCredenciales = crearArchivoCredencialesTemporal($config);

            $resultado = ejecutarMysqldump($rutaMysqldump, $rutaCredenciales, $config['database'], $rutaTemporalDump);

            if (!$resultado['exito']) {
                error_log(
                    'Respaldos: mysqldump finalizó con error. Código: '
                    . ($resultado['codigo_salida'] ?? 'desconocido')
                    . '. Detalle: ' . ($resultado['error'] ?? '')
                );

                return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
            }

            if (!is_file($rutaTemporalDump) || filesize($rutaTemporalDump) <= 0) {
                error_log('Respaldos: el archivo temporal de respaldo no existe o está vacío tras ejecutar mysqldump.');

                return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
            }

            if (!validarCabeceraDump($rutaTemporalDump)) {
                error_log('Respaldos: el archivo generado no tiene una cabecera de dump reconocible.');

                return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
            }

            if (!rename($rutaTemporalDump, $rutaFinal)) {
                error_log('Respaldos: no se pudo mover el archivo temporal de respaldo a su ubicación final.');

                return ['exito' => false, 'error' => 'No fue posible generar el respaldo. Intente nuevamente.'];
            }

            return [
                'exito' => true,
                'nombre_archivo' => $nombreFinal,
                'ruta_relativa' => 'storage/respaldos/' . $nombreFinal,
            ];
        } finally {
            if ($rutaCredenciales !== null && is_file($rutaCredenciales)) {
                @unlink($rutaCredenciales);
            }

            if (is_file($rutaTemporalDump)) {
                @unlink($rutaTemporalDump);
            }
        }
    }
}
