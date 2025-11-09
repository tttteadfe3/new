<?php

// public/index.php

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Container;
use App\Core\Database;
use App\Core\SessionManager;
use App\Core\Router;
use App\Core\Request;
use App\Core\JsonResponse;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize the DI container
$container = new Container();

// Register core components
$container->singleton(SessionManager::class, fn() => new SessionManager());
$container->singleton(Database::class, fn() => new Database());
$container->singleton(Request::class, fn() => new Request());
$container->singleton(JsonResponse::class, fn() => new JsonResponse());

// The order of registration is critical to avoid circular dependencies.

// 1. Core services with no repository dependencies, or only DB/Session.
$container->register(\App\Services\DataScopeService::class, fn($c) => new \App\Services\DataScopeService(
    $c->resolve(SessionManager::class),
    $c->resolve(Database::class)
));
$container->register(\App\Services\KakaoAuthService::class, fn($c) => new \App\Services\KakaoAuthService($c->resolve(SessionManager::class)));

// 2. Repositories - some now depend on DataScopeService.
$container->register(\App\Repositories\DepartmentRepository::class, fn($c) => new \App\Repositories\DepartmentRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\EmployeeRepository::class, fn($c) => new \App\Repositories\EmployeeRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\HolidayRepository::class, fn($c) => new \App\Repositories\HolidayRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\LeaveRepository::class, fn($c) => new \App\Repositories\LeaveRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\UserRepository::class, fn($c) => new \App\Repositories\UserRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));

// Repositories with no DataScopeService dependency
$container->register(\App\Repositories\EmployeeChangeLogRepository::class, fn($c) => new \App\Repositories\EmployeeChangeLogRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\LitteringRepository::class, fn($c) => new \App\Repositories\LitteringRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\LogRepository::class, fn($c) => new \App\Repositories\LogRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\MenuRepository::class, fn($c) => new \App\Repositories\MenuRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\PositionRepository::class, fn($c) => new \App\Repositories\PositionRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\RoleRepository::class, fn($c) => new \App\Repositories\RoleRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\WasteCollectionRepository::class, fn($c) => new \App\Repositories\WasteCollectionRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\ItemCategoryRepository::class, fn($c) => new \App\Repositories\ItemCategoryRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ItemRepository::class, fn($c) => new \App\Repositories\ItemRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ItemPlanRepository::class, fn($c) => new \App\Repositories\ItemPlanRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ItemPurchaseRepository::class, fn($c) => new \App\Repositories\ItemPurchaseRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ItemGiveRepository::class, fn($c) => new \App\Repositories\ItemGiveRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ItemStatisticRepository::class, fn($c) => new \App\Repositories\ItemStatisticRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));


// 3. Application services that depend on repositories and other services.
$container->register(\App\Services\ItemStatisticService::class, fn($c) => new \App\Services\ItemStatisticService($c->resolve(\App\Repositories\ItemStatisticRepository::class)));
$container->register(\App\Services\ItemGiveService::class, fn($c) => new \App\Services\ItemGiveService($c->resolve(\App\Repositories\ItemGiveRepository::class), $c->resolve(\App\Services\AuthService::class)));
$container->register(\App\Services\ItemPurchaseService::class, fn($c) => new \App\Services\ItemPurchaseService($c->resolve(\App\Repositories\ItemPurchaseRepository::class), $c->resolve(\App\Repositories\ItemRepository::class), $c->resolve(\App\Repositories\ItemPlanRepository::class), $c->resolve(\App\Services\AuthService::class)));
$container->register(\App\Services\ItemCategoryService::class, fn($c) => new \App\Services\ItemCategoryService($c->resolve(\App\Repositories\ItemCategoryRepository::class)));
$container->register(\App\Services\ItemService::class, fn($c) => new \App\Services\ItemService($c->resolve(\App\Repositories\ItemRepository::class), $c->resolve(\App\Repositories\ItemCategoryRepository::class)));
$container->register(\App\Services\ItemPlanService::class, fn($c) => new \App\Services\ItemPlanService($c->resolve(\App\Repositories\ItemPlanRepository::class), $c->resolve(\App\Repositories\ItemRepository::class), $c->resolve(\App\Services\AuthService::class)));
$container->register(\App\Services\AuthService::class, fn($c) => new \App\Services\AuthService(
    $c->resolve(SessionManager::class),
    $c->resolve(\App\Repositories\UserRepository::class),
    $c->resolve(\App\Repositories\RoleRepository::class),
    $c->resolve(\App\Repositories\LogRepository::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class)
));
$container->register(\App\Services\ActivityLogger::class, fn($c) => new \App\Services\ActivityLogger($c->resolve(SessionManager::class), $c->resolve(\App\Repositories\LogRepository::class), $c->resolve(\App\Repositories\UserRepository::class)));
$container->register(\App\Services\EmployeeService::class, fn($c) => new \App\Services\EmployeeService(
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(\App\Repositories\EmployeeChangeLogRepository::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(\App\Repositories\PositionRepository::class),
    $c->resolve(\App\Repositories\LogRepository::class),
    $c->resolve(SessionManager::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Services\HolidayService::class, fn($c) => new \App\Services\HolidayService(
    $c->resolve(\App\Repositories\HolidayRepository::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Services\LeaveService::class, fn($c) => new \App\Services\LeaveService(
    $c->resolve(\App\Repositories\LeaveRepository::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(\App\Services\HolidayService::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Services\LitteringService::class, fn($c) => new \App\Services\LitteringService($c->resolve(\App\Repositories\LitteringRepository::class), $c->resolve(Database::class)));
$container->register(\App\Services\LogService::class, fn($c) => new \App\Services\LogService($c->resolve(\App\Repositories\LogRepository::class)));
$container->register(\App\Services\MenuManagementService::class, fn($c) => new \App\Services\MenuManagementService($c->resolve(\App\Repositories\MenuRepository::class), $c->resolve(Database::class)));
$container->register(\App\Services\OrganizationService::class, fn($c) => new \App\Services\OrganizationService(
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(\App\Services\DataScopeService::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class)
));
$container->register(\App\Services\PositionService::class, fn($c) => new \App\Services\PositionService($c->resolve(\App\Repositories\PositionRepository::class)));
$container->register(\App\Services\ProfileService::class, fn($c) => new \App\Services\ProfileService($c->resolve(\App\Repositories\UserRepository::class), $c->resolve(\App\Repositories\EmployeeRepository::class)));
$container->register(\App\Services\RolePermissionService::class, fn($c) => new \App\Services\RolePermissionService($c->resolve(\App\Repositories\RoleRepository::class)));
$container->register(\App\Services\UserService::class, fn($c) => new \App\Services\UserService(
    $c->resolve(\App\Repositories\UserRepository::class),
    $c->resolve(\App\Repositories\RoleRepository::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Services\ViewDataService::class, fn($c) => new \App\Services\ViewDataService(
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(SessionManager::class),
    $c->resolve(\App\Repositories\MenuRepository::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\WasteCollectionService::class, fn($c) => new \App\Services\WasteCollectionService($c->resolve(\App\Repositories\WasteCollectionRepository::class), $c->resolve(Database::class)));

// 4. Controllers (Web and API)
$container->register(\App\Controllers\Web\LeaveController::class, fn($c) => new \App\Controllers\Web\LeaveController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Services\LeaveService::class),
    $c->resolve(\App\Services\EmployeeService::class)
));
$container->register(\App\Controllers\Web\AdminController::class, fn($c) => new \App\Controllers\Web\AdminController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Services\OrganizationService::class),
    $c->resolve(\App\Services\RolePermissionService::class),
    $c->resolve(\App\Services\UserService::class),
    $c->resolve(\App\Services\MenuManagementService::class),
    $c->resolve(\App\Services\PositionService::class)
));
$container->register(\App\Controllers\Web\InventoryController::class, fn($c) => new \App\Controllers\Web\InventoryController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Controllers\Api\OrganizationApiController::class, fn($c) => new \App\Controllers\Api\OrganizationApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\OrganizationService::class),
    $c->resolve(\App\Repositories\PositionRepository::class),
    $c->resolve(\App\Services\DataScopeService::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class)
));
$container->register(\App\Controllers\Api\PositionApiController::class, fn($c) => new \App\Controllers\Api\PositionApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\PositionService::class)
));
$container->register(\App\Controllers\Api\EmployeeApiController::class, fn($c) => new \App\Controllers\Api\EmployeeApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\EmployeeService::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(\App\Repositories\PositionRepository::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Controllers\Api\LeaveController::class, fn($c) => new \App\Controllers\Api\LeaveController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\LeaveService::class)
));
$container->register(\App\Controllers\Api\ItemCategoryController::class, fn($c) => new \App\Controllers\Api\ItemCategoryController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemCategoryService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));
$container->register(\App\Controllers\Api\ItemController::class, fn($c) => new \App\Controllers\Api\ItemController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));
$container->register(\App\Controllers\Api\ItemPlanController::class, fn($c) => new \App\Controllers\Api\ItemPlanController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemPlanService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));
$container->register(\App\Controllers\Api\ItemPurchaseController::class, fn($c) => new \App\Controllers\Api\ItemPurchaseController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemPurchaseService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));
$container->register(\App\Controllers\Api\ItemGiveController::class, fn($c) => new \App\Controllers\Api\ItemGiveController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemGiveService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));
$container->register(\App\Controllers\Api\ItemStatisticController::class, fn($c) => new \App\Controllers\Api\ItemStatisticController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\ItemStatisticService::class),
    $c->resolve(\App\Repositories\LogRepository::class)
));

// Start session (temporarily disable regeneration for debugging)
$sessionManager = $container->resolve(SessionManager::class);
$sessionManager->start();
// $sessionManager->regenerate(); // Temporarily disabled for debugging

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Instantiate the router with the container
$router = new Router($container);

// Register Middlewares
$router->addMiddleware('auth', \App\Middleware\AuthMiddleware::class);
$router->addMiddleware('permission', \App\Middleware\PermissionMiddleware::class);

// Load routes
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

// Dispatch the request
$router->dispatch();
