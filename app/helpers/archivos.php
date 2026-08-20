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
