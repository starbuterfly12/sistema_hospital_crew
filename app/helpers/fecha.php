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
