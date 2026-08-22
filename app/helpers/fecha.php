<?php

if (!function_exists('formatDate')) {
    function formatDate(?string $fecha): string
    {
        if ($fecha === null || trim($fecha) === '') {
            return '';
        }

        $fechaObjeto = DateTime::createFromFormat('!Y-m-d', substr(trim($fecha), 0, 10));

        if ($fechaObjeto === false) {
            return $fecha;
        }

        return $fechaObjeto->format('d/m/Y');
    }
}

if (!function_exists('isValidIsoDate')) {
    function isValidIsoDate(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $fechaObjeto = DateTime::createFromFormat('!Y-m-d', $fecha);

        return $fechaObjeto !== false && $fechaObjeto->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('calcularFinGarantia')) {
    // fecha_factura + meses_garantia, calculado siempre en backend (no solo en JS) para que el
    // resultado mostrado en la interfaz no dependa de que el cliente lo calcule correctamente.
    //
    // Aritmética de calendario manual (año/mes/día), NO DateTime::modify('+N months'): ese método
    // se sale del mes cuando el día de origen no existe en el mes destino (ej. 31 ene + 1 mes da
    // 3 mar, porque suma 31 días naturales en vez de "un mes calendario"). Regla exigida: conservar
    // el mismo día si existe en el mes destino; si no, usar el último día válido de ese mes.
    function calcularFinGarantia(?string $fechaFactura, ?int $mesesGarantia): ?string
    {
        if (!isValidIsoDate($fechaFactura) || $mesesGarantia === null || $mesesGarantia < 1 || $mesesGarantia > 12) {
            return null;
        }

        $fechaObjeto = DateTime::createFromFormat('!Y-m-d', $fechaFactura);

        if ($fechaObjeto === false) {
            return null;
        }

        $anio = (int) $fechaObjeto->format('Y');
        $mes = (int) $fechaObjeto->format('n');
        $dia = (int) $fechaObjeto->format('j');

        $totalMeses = ($mes - 1) + $mesesGarantia;
        $anioDestino = $anio + intdiv($totalMeses, 12);
        $mesDestino = ($totalMeses % 12) + 1;

        $primerDiaMesDestino = new DateTime();
        $primerDiaMesDestino->setDate($anioDestino, $mesDestino, 1);
        $ultimoDiaMesDestino = (int) $primerDiaMesDestino->format('t');

        $diaDestino = min($dia, $ultimoDiaMesDestino);

        return sprintf('%04d-%02d-%02d', $anioDestino, $mesDestino, $diaDestino);
    }
}

if (!function_exists('formatFechaSegunTipo')) {
    // Usado por Reportes (Movimientos / Bienes con actividad) donde un mismo listado mezcla fuentes
    // con DATETIME real (Traslado/Requisición/Verificación) y fuentes que solo tienen DATE
    // (Préstamo/Devolución/Baja). Sin esta distinción, una fecha DATE se mostraba con "00:00"
    // pegado, dando la falsa impresión de ser una hora real.
    function formatFechaSegunTipo(?string $fecha, bool $esDatetime): string
    {
        return $esDatetime ? formatDateTime($fecha) : formatDate($fecha);
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime(?string $fechaHora): string
    {
        if ($fechaHora === null || trim($fechaHora) === '') {
            return '';
        }

        $normalizado = str_replace('T', ' ', trim($fechaHora));

        $fechaObjeto = DateTime::createFromFormat('Y-m-d H:i:s', $normalizado);

        if ($fechaObjeto === false) {
            $fechaObjeto = DateTime::createFromFormat('Y-m-d H:i', $normalizado);
        }

        if ($fechaObjeto === false) {
            return $fechaHora;
        }

        return $fechaObjeto->format('d/m/Y H:i');
    }
}

if (!function_exists('formatDateTimeSeconds')) {
    // Igual que formatDateTime() pero conservando los segundos — usado únicamente por Bitácora, donde
    // dos registros distintos pueden compartir el mismo minuto (ej. varios intentos de login
    // seguidos) y la distinción visual sí importa. No modifica formatDateTime(): otros módulos ya
    // dependen de su formato actual sin segundos.
    function formatDateTimeSeconds(?string $fechaHora): string
    {
        if ($fechaHora === null || trim($fechaHora) === '') {
            return '';
        }

        $normalizado = str_replace('T', ' ', trim($fechaHora));

        $fechaObjeto = DateTime::createFromFormat('Y-m-d H:i:s', $normalizado);

        if ($fechaObjeto === false) {
            return $fechaHora;
        }

        return $fechaObjeto->format('d/m/Y H:i:s');
    }
}
