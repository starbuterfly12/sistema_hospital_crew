<?php
/**
 * Configuracion general de la aplicacion.
 */

// Ajusta esto si el proyecto no vive en la raiz de htdocs (ej: /sistema_hospital)
define('BASE_URL', '/sistema_hospital');

define('APP_NAME', 'Sistema de Gestion de Bienes - Hospital General de Chiquimula');

define('APP_ROOT', dirname(__DIR__, 2));

define('STORAGE_PATH', APP_ROOT . '/storage');

// Duracion de la sesion en segundos (inactividad)
define('SESSION_LIFETIME', 3600);

date_default_timezone_set('America/Guatemala');
