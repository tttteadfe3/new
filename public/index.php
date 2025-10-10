<?php

// public/index.php

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Routing
use App\Core\Router;

// Register Middlewares
Router::addMiddleware('auth', \App\Middleware\AuthMiddleware::class);
Router::addMiddleware('permission', \App\Middleware\PermissionMiddleware::class);

// Load routes
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

// Dispatch the request
Router::dispatch();