<?php

session_start();

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$authController = new AuthController();
$authController->login();
