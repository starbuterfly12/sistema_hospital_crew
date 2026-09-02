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

if (!function_exists('normalizarArchivosSubidos')) {
    // Reduce la entrada de $_FILES[campo] a una lista uniforme de archivos individuales (cada uno
    // con claves name/type/tmp_name/error/size), sin importar si el <input> era simple
    // (name="campo") o `multiple` (name="campo[]"). Descarta los slots sin archivo
    // (UPLOAD_ERR_NO_FILE) y el caso null/estructura inválida.
    function normalizarArchivosSubidos(?array $archivos): array
    {
        if ($archivos === null || !isset($archivos['error'])) {
            return [];
        }

        // <input multiple>: cada campo llega como array indexado por posición.
        if (is_array($archivos['error'])) {
            $lista = [];

            foreach (array_keys($archivos['error']) as $i) {
                if (($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $lista[] = [
                    'name' => $archivos['name'][$i] ?? '',
                    'type' => $archivos['type'][$i] ?? '',
                    'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                    'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $archivos['size'][$i] ?? 0,
                ];
            }

            return $lista;
        }

        // <input> simple: un único archivo con campos escalares.
        if (($archivos['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [[
            'name' => $archivos['name'] ?? '',
            'type' => $archivos['type'] ?? '',
            'tmp_name' => $archivos['tmp_name'] ?? '',
            'error' => $archivos['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $archivos['size'] ?? 0,
        ]];
    }
}

if (!function_exists('nombreOriginalSeguro')) {
    // Nombre "para mostrar" de un archivo subido: se queda solo con basename(), quita caracteres de
    // control y separadores de ruta, colapsa espacios y recorta a 255. NUNCA se usa para el archivo
    // físico (ese siempre lleva nombre aleatorio); si tras sanear queda vacío o inservible, devuelve
    // $fallback.
    function nombreOriginalSeguro(string $nombre, string $fallback): string
    {
        $nombre = basename(str_replace('\\', '/', $nombre));
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre) ?? '';
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');

        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return $fallback;
        }

        if (mb_strlen($nombre, 'UTF-8') > 255) {
            $nombre = mb_substr($nombre, 0, 255, 'UTF-8');
        }

        return $nombre;
    }
}

if (!function_exists('guardarDocumentosRespaldo')) {
    // Versión ACUMULATIVA del documento de respaldo del ingreso de un bien: acepta el <input
    // type="file"> tal como llega en $_FILES['documento_respaldo'] —de un solo archivo o de varios
    // (multiple)— y guarda TODOS. Cada documento se valida con el MISMO criterio que
    // guardarDocumentoRespaldo(): tamaño ≤ 5 MB, is_uploaded_file(), MIME real por finfo limitado a
    // PDF / JPG / PNG, nombre físico aleatorio en storage/documentos/ (carpeta protegida por
    // storage/.htaccess). Además conserva el nombre original saneado para mostrárselo a la persona.
    //
    // Devuelve una lista de ['ruta' => ruta relativa, 'nombre_original' => string]; lista vacía si no
    // se envió ningún archivo. Si CUALQUIER archivo del lote falla la validación, lanza
    // RuntimeException tras borrar los que ya se hubieran movido en esta misma llamada — nunca deja
    // archivos huérfanos a medias.
    function guardarDocumentosRespaldo(?array $archivos): array
    {
        $normalizados = normalizarArchivosSubidos($archivos);

        if ($normalizados === []) {
            return [];
        }

        // Tope defensivo por guardado: los documentos igual se van acumulando en ediciones sucesivas.
        if (count($normalizados) > 10) {
            throw new RuntimeException('No se pueden agregar más de 10 documentos de respaldo a la vez.');
        }

        $extensionesPorMime = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $directorioDestino = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documentos';

        if (!is_dir($directorioDestino) || !is_writable($directorioDestino)) {
            throw new RuntimeException('No se pudo guardar el documento de respaldo.');
        }

        $guardados = [];

        try {
            foreach ($normalizados as $archivo) {
                if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
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

                if ($mimeReal === false || !isset($extensionesPorMime[$mimeReal])) {
                    throw new RuntimeException('El documento de respaldo debe ser un archivo PDF, JPG o PNG.');
                }

                $extension = $extensionesPorMime[$mimeReal];
                $nombreFisico = 'documento_' . bin2hex(random_bytes(16)) . '.' . $extension;
                $rutaRelativa = 'storage/documentos/' . $nombreFisico;
                $rutaAbsoluta = $directorioDestino . DIRECTORY_SEPARATOR . $nombreFisico;

                if (!move_uploaded_file($tmpName, $rutaAbsoluta)) {
                    throw new RuntimeException('No se pudo guardar el documento de respaldo.');
                }

                $guardados[] = [
                    'ruta' => $rutaRelativa,
                    'nombre_original' => nombreOriginalSeguro((string) ($archivo['name'] ?? ''), 'Documento de respaldo'),
                ];
            }
        } catch (RuntimeException $e) {
            foreach ($guardados as $guardado) {
                eliminarDocumentoRespaldo($guardado['ruta']);
            }

            throw $e;
        }

        return $guardados;
    }
}

if (!function_exists('eliminarDocumentosRespaldo')) {
    // Borra en bloque los archivos recién subidos por guardarDocumentosRespaldo() (lista de
    // elementos con clave 'ruta', o de rutas sueltas). Se usa para limpiar cuando la transacción del
    // registro / edición del bien termina en rollback.
    function eliminarDocumentosRespaldo(array $documentos): void
    {
        foreach ($documentos as $documento) {
            $ruta = is_array($documento) ? ($documento['ruta'] ?? null) : $documento;
            eliminarDocumentoRespaldo(is_string($ruta) ? $ruta : null);
        }
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

if (!function_exists('guardarFotografiaBien')) {
    // Fotografía PRINCIPAL del bien (bienes.imagen_bien) — distinta de guardarImagenBien(), que
    // pese al nombre guarda la foto de una BAJA en storage/fotos_baja/. Esta vive en
    // storage/fotos_bienes/, acepta también WEBP, y es opcional (null si no se envió archivo).
    // Mismo patrón de seguridad que guardarDocumentoRespaldo(): límite de 5 MB, is_uploaded_file(),
    // MIME real por finfo (no extensión), nombre físico aleatorio.
    function guardarFotografiaBien(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo cargar la fotografía del bien.');
        }

        $tamano = (int) ($archivo['size'] ?? 0);

        if ($tamano <= 0) {
            throw new RuntimeException('La fotografía del bien está vacía.');
        }

        if ($tamano > 5 * 1024 * 1024) {
            throw new RuntimeException('La fotografía del bien no puede superar los 5 MB.');
        }

        $tmpName = $archivo['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('No se pudo cargar la fotografía del bien.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($tmpName);

        $extensionesPorMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if ($mimeReal === false || !isset($extensionesPorMime[$mimeReal])) {
            throw new RuntimeException('La fotografía del bien debe ser un archivo JPG, PNG o WEBP.');
        }

        $extension = $extensionesPorMime[$mimeReal];
        $nombreFisico = 'bien_' . bin2hex(random_bytes(16)) . '.' . $extension;

        $rutaRelativa = 'storage/fotos_bienes/' . $nombreFisico;
        $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        $directorioDestino = dirname($rutaAbsoluta);

        if (!is_dir($directorioDestino) || !is_writable($directorioDestino)) {
            throw new RuntimeException('No se pudo guardar la fotografía del bien.');
        }

        if (!move_uploaded_file($tmpName, $rutaAbsoluta)) {
            throw new RuntimeException('No se pudo guardar la fotografía del bien.');
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

if (!function_exists('eliminarFotografiaBien')) {
    // Alias semántico para borrar la fotografía principal del bien (bienes.imagen_bien). Mismo
    // borrado genérico por ruta relativa; se usa al sustituir la foto (tras el commit) y para
    // limpiar el archivo recién subido si la transacción falla.
    function eliminarFotografiaBien(?string $rutaRelativa): void
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
    // PDF / JPEG / PNG / WEBP (WEBP solo lo produce la fotografía del bien; los documentos y las
    // fotos de baja siguen limitados a PDF/JPG/PNG en sus propios validadores de subida). El nombre
    // de salida se saca con basename() de la propia ruta física. Nunca expone rutas del servidor:
    // ante cualquier problema responde un 404 genérico y corta.
    //
    // $disposition: 'inline' (ver en el navegador, es el caso de "Ver documento" / "Ver foto") o
    // 'attachment' (forzar descarga).
    function servirArchivoControlado(?string $rutaFisica, string $disposition = 'inline'): void
    {
        $mimesPermitidos = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
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
