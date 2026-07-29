<?php
/**
 * Front controller. Todas las peticiones (via .htaccess) llegan aqui.
 */

session_start();

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/helpers/functions.php';

$url = $_GET['url'] ?? '';

$router = new Router();
$router->dispatch($url);
