<?php

if (!function_exists('guardarDocumentoRespaldo')) {
    function guardarDocumentoRespaldo(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo cargar el documento de respaldo.');
        }

        $tamano = (int) ($archivo['size'] ?? 0);

        if ($tamano <= 0) {
            throw new RuntimeException('El documento de respaldo está vacío.');
        }

        if ($tamano > 5 * 1024 * 1024) {
            throw new RuntimeException('El documento de respaldo no puede superar los 5 MB.');
        }

        $tmpName = $archivo['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('No se pudo cargar el documento de respaldo.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($tmpName);

        $extensionesPorMime = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if ($mimeReal === false || !isset($extensionesPorMime[$mimeReal])) {
            throw new RuntimeException('El documento de respaldo debe ser un archivo PDF, JPG o PNG.');
        }

        $extension = $extensionesPorMime[$mimeReal];
        $nombreFisico = 'documento_' . bin2hex(random_bytes(16)) . '.' . $extension;

        $rutaRelativa = 'storage/documentos/' . $nombreFisico;
        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        $directorioDestino = dirname($rutaAbsoluta);

        if (!is_dir($directorioDestino) || !is_writable($directorioDestino)) {
            throw new RuntimeException('No se pudo guardar el documento de respaldo.');
        }

        if (!move_uploaded_file($tmpName, $rutaAbsoluta)) {
            throw new RuntimeException('No se pudo guardar el documento de respaldo.');
        }

        return $rutaRelativa;
    }
}

if (!function_exists('guardarImagenBien')) {
    function guardarImagenBien(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo cargar la imagen del bien.');
        }

        $tamano = (int) ($archivo['size'] ?? 0);

        if ($tamano <= 0) {
            throw new RuntimeException('La imagen del bien está vacía.');
        }

        if ($tamano > 5 * 1024 * 1024) {
            throw new RuntimeException('La imagen del bien no puede superar los 5 MB.');
        }

        $tmpName = $archivo['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('No se pudo cargar la imagen del bien.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($tmpName);

        $extensionesPorMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if ($mimeReal === false || !isset($extensionesPorMime[$mimeReal])) {
            throw new RuntimeException('La imagen del bien debe ser un archivo JPG o PNG.');
        }

        $extension = $extensionesPorMime[$mimeReal];
        $nombreFisico = 'foto_baja_' . bin2hex(random_bytes(16)) . '.' . $extension;

        $rutaRelativa = 'storage/fotos_baja/' . $nombreFisico;
        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        $directorioDestino = dirname($rutaAbsoluta);

        if (!is_dir($directorioDestino) || !is_writable($directorioDestino)) {
            throw new RuntimeException('No se pudo guardar la imagen del bien.');
        }

        if (!move_uploaded_file($tmpName, $rutaAbsoluta)) {
            throw new RuntimeException('No se pudo guardar la imagen del bien.');
        }

        return $rutaRelativa;
    }
}

if (!function_exists('eliminarDocumentoRespaldo')) {
    function eliminarDocumentoRespaldo(?string $rutaRelativa): void
    {
        if ($rutaRelativa === null || $rutaRelativa === '') {
            return;
        }

        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        if (is_file($rutaAbsoluta)) {
            @unlink($rutaAbsoluta);
        }
    }
}

if (!function_exists('eliminarImagenBien')) {
    // Alias semántico de eliminarDocumentoRespaldo(): misma lógica genérica de borrado por ruta
    // relativa, con nombre propio para no confundir el borrado de imagen_bien con documento_respaldo.
    function eliminarImagenBien(?string $rutaRelativa): void
    {
        eliminarDocumentoRespaldo($rutaRelativa);
    }
}

if (!function_exists('resolverRutaArchivoStorage')) {
    // Resuelve una ruta relativa GUARDADA EN BD (nunca provista por el navegador) hacia su ruta
    // física absoluta, confirmando con realpath() que el archivo cae DENTRO de storage/<subcarpeta>/.
    // Mismo criterio que resolverRutaFisicaRespaldo() (app/helpers/respaldo_bd.php): comparación en
    // minúsculas porque Windows no distingue mayúsculas en rutas. Devuelve null si la ruta está
    // vacía, el archivo no existe/no es legible, o queda fuera de la subcarpeta permitida
    // (protección de path traversal aunque la ruta no venga del usuario).
    //
    // $subcarpetaPermitida: nombre simple, p. ej. 'documentos', 'fotos_baja', 'qr'.
    function resolverRutaArchivoStorage(?string $rutaRelativa, string $subcarpetaPermitida): ?string
    {
        if ($rutaRelativa === null || trim($rutaRelativa) === '') {
            return null;
        }

        $baseStorage = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $subcarpetaPermitida;
        $directorioPermitido = realpath($baseStorage);

        if ($directorioPermitido === false) {
            return null;
        }

        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim(trim($rutaRelativa), '/\\'));

        $rutaReal = realpath($rutaAbsoluta);

        if ($rutaReal === false || !is_file($rutaReal) || !is_readable($rutaReal)) {
            return null;
        }

        $prefijoPermitido = strtolower(rtrim($directorioPermitido, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR;

        if (!str_starts_with(strtolower($rutaReal), $prefijoPermitido)) {
            return null;
        }

        return $rutaReal;
    }
}

if (!function_exists('servirArchivoControlado')) {
    // Entrega por HTTP un archivo YA validado por resolverRutaArchivoStorage(). Determina el tipo
    // con el MIME REAL del contenido (finfo), no con la extensión almacenada, y solo acepta
    // PDF / JPEG / PNG. El nombre de salida se saca con basename() de la propia ruta física. Nunca
    // expone rutas del servidor: ante cualquier problema responde un 404 genérico y corta.
    //
    // $disposition: 'inline' (ver en el navegador, es el caso de "Ver documento" / "Ver foto") o
    // 'attachment' (forzar descarga).
    function servirArchivoControlado(?string $rutaFisica, string $disposition = 'inline'): void
    {
        $mimesPermitidos = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if ($rutaFisica === null || !is_file($rutaFisica) || !is_readable($rutaFisica)) {
            http_response_code(404);
            echo 'Archivo no disponible.';
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($rutaFisica);

        if ($mimeReal === false || !isset($mimesPermitidos[$mimeReal])) {
            http_response_code(404);
            echo 'Archivo no disponible.';
            exit;
        }

        $disposition = $disposition === 'attachment' ? 'attachment' : 'inline';
        $nombreSalida = basename($rutaFisica);

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mimeReal);
        header('Content-Disposition: ' . $disposition . '; filename="' . $nombreSalida . '"');
        header('Content-Length: ' . filesize($rutaFisica));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($rutaFisica);
        exit;
    }
}
