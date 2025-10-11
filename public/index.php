<?php

// public/index.php

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Use the new instance-based SessionManager
$sessionManager = new App\Core\SessionManager();
$sessionManager->start();
$sessionManager->regenerate();


// Load configuration
require_once __DIR__ . '/../config/config.php';

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
