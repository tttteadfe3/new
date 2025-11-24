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
$container->register(\App\Repositories\BreakdownRepository::class, fn($c) => new \App\Repositories\BreakdownRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\MaintenanceRepository::class, fn($c) => new \App\Repositories\MaintenanceRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\ConsumableRepository::class, fn($c) => new \App\Repositories\ConsumableRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\ConsumableLogRepository::class, fn($c) => new \App\Repositories\ConsumableLogRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\InsuranceRepository::class, fn($c) => new \App\Repositories\InsuranceRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\InspectionRepository::class, fn($c) => new \App\Repositories\InspectionRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\TaxRepository::class, fn($c) => new \App\Repositories\TaxRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\EmployeeChangeLogRepository::class, fn($c) => new \App\Repositories\EmployeeChangeLogRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\LitteringRepository::class, fn($c) => new \App\Repositories\LitteringRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\LogRepository::class, fn($c) => new \App\Repositories\LogRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\MenuRepository::class, fn($c) => new \App\Repositories\MenuRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\PositionRepository::class, fn($c) => new \App\Repositories\PositionRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\RoleRepository::class, fn($c) => new \App\Repositories\RoleRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\WasteCollectionRepository::class, fn($c) => new \App\Repositories\WasteCollectionRepository($c->resolve(Database::class)));

// Supply Management Repositories
$container->register(\App\Repositories\SupplyCategoryRepository::class, fn($c) => new \App\Repositories\SupplyCategoryRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\SupplyItemRepository::class, fn($c) => new \App\Repositories\SupplyItemRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\SupplyPlanRepository::class, fn($c) => new \App\Repositories\SupplyPlanRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\SupplyPurchaseRepository::class, fn($c) => new \App\Repositories\SupplyPurchaseRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\SupplyDistributionRepository::class, fn($c) => new \App\Repositories\SupplyDistributionRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\SupplyStockRepository::class, fn($c) => new \App\Repositories\SupplyStockRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));

// Vehicle Management Repositories
$container->register(\App\Repositories\VehicleRepository::class, fn($c) => new \App\Repositories\VehicleRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleBreakdownRepository::class, fn($c) => new \App\Repositories\VehicleBreakdownRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleRepairRepository::class, fn($c) => new \App\Repositories\VehicleRepairRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleSelfMaintenanceRepository::class, fn($c) => new \App\Repositories\VehicleSelfMaintenanceRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleConsumableRepository::class, fn($c) => new \App\Repositories\VehicleConsumableRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleConsumableLogRepository::class, fn($c) => new \App\Repositories\VehicleConsumableLogRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleInsuranceRepository::class, fn($c) => new \App\Repositories\VehicleInsuranceRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleTaxRepository::class, fn($c) => new \App\Repositories\VehicleTaxRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleInspectionRepository::class, fn($c) => new \App\Repositories\VehicleInspectionRepository($c->resolve(Database::class)));
$container->register(\App\Repositories\VehicleDocumentRepository::class, fn($c) => new \App\Repositories\VehicleDocumentRepository($c->resolve(Database::class)));


// 3. Application services that depend on repositories and other services.
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

// Supply Management Services
$container->register(\App\Services\VehicleService::class, fn($c) => new \App\Services\VehicleService($c->resolve(\App\Repositories\VehicleRepository::class)));
$container->register(\App\Services\BreakdownService::class, fn($c) => new \App\Services\BreakdownService($c->resolve(\App\Repositories\BreakdownRepository::class)));
$container->register(\App\Services\MaintenanceService::class, fn($c) => new \App\Services\MaintenanceService($c->resolve(\App\Repositories\MaintenanceRepository::class)));
$container->register(\App\Services\ConsumableService::class, fn($c) => new \App\Services\ConsumableService($c->resolve(\App\Repositories\ConsumableRepository::class)));
$container->register(\App\Services\ConsumableLogService::class, fn($c) => new \App\Services\ConsumableLogService($c->resolve(\App\Repositories\ConsumableLogRepository::class)));
$container->register(\App\Services\InsuranceService::class, fn($c) => new \App\Services\InsuranceService($c->resolve(\App\Repositories\InsuranceRepository::class)));
$container->register(\App\Services\InspectionService::class, fn($c) => new \App\Services\InspectionService($c->resolve(\App\Repositories\InspectionRepository::class)));
$container->register(\App\Services\TaxService::class, fn($c) => new \App\Services\TaxService($c->resolve(\App\Repositories\TaxRepository::class)));
$container->register(\App\Services\SupplyCategoryService::class, fn($c) => new \App\Services\SupplyCategoryService(
    $c->resolve(\App\Repositories\SupplyCategoryRepository::class),
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\SupplyItemService::class, fn($c) => new \App\Services\SupplyItemService(
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Repositories\SupplyCategoryRepository::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\SupplyStockService::class, fn($c) => new \App\Services\SupplyStockService(
    $c->resolve(\App\Repositories\SupplyStockRepository::class),
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\SupplyPlanService::class, fn($c) => new \App\Services\SupplyPlanService(
    $c->resolve(\App\Repositories\SupplyPlanRepository::class),
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Repositories\SupplyPurchaseRepository::class),
    $c->resolve(SessionManager::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\SupplyPurchaseService::class, fn($c) => new \App\Services\SupplyPurchaseService(
    $c->resolve(\App\Repositories\SupplyPurchaseRepository::class),
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Services\SupplyStockService::class),
    $c->resolve(SessionManager::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));
$container->register(\App\Services\SupplyDistributionService::class, fn($c) => new \App\Services\SupplyDistributionService(
    $c->resolve(\App\Repositories\SupplyDistributionRepository::class),
    $c->resolve(\App\Services\SupplyStockService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(Database::class)
));
$container->register(\App\Services\SupplyReportService::class, fn($c) => new \App\Services\SupplyReportService(
    $c->resolve(\App\Repositories\SupplyDistributionRepository::class),
    $c->resolve(\App\Repositories\SupplyStockRepository::class),
    $c->resolve(\App\Repositories\SupplyPlanRepository::class),
    $c->resolve(\App\Repositories\SupplyPurchaseRepository::class),
    $c->resolve(\App\Repositories\SupplyItemRepository::class),
    $c->resolve(\App\Repositories\DepartmentRepository::class),
    $c->resolve(Database::class),
    $c->resolve(\App\Services\ActivityLogger::class)
));

// Vehicle Management Services
$container->register(\App\Services\VehicleService::class, fn($c) => new \App\Services\VehicleService($c->resolve(\App\Repositories\VehicleRepository::class)));
$container->register(\App\Services\VehicleBreakdownService::class, fn($c) => new \App\Services\VehicleBreakdownService(
    $c->resolve(\App\Repositories\VehicleBreakdownRepository::class),
    $c->resolve(\App\Repositories\VehicleRepository::class)
));
$container->register(\App\Services\VehicleMaintenanceService::class, fn($c) => new \App\Services\VehicleMaintenanceService(
    $c->resolve(\App\Repositories\VehicleRepairRepository::class),
    $c->resolve(\App\Repositories\VehicleSelfMaintenanceRepository::class)
));
$container->register(\App\Services\VehicleConsumableService::class, fn($c) => new \App\Services\VehicleConsumableService(
    $c->resolve(\App\Repositories\VehicleConsumableRepository::class)
));
$container->register(\App\Services\VehicleAdminService::class, fn($c) => new \App\Services\VehicleAdminService(
    $c->resolve(\App\Repositories\VehicleInsuranceRepository::class),
    $c->resolve(\App\Repositories\VehicleTaxRepository::class),
    $c->resolve(\App\Repositories\VehicleInspectionRepository::class),
    $c->resolve(\App\Repositories\VehicleDocumentRepository::class)
));


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
$container->register(\App\Controllers\Api\VehicleApiController::class, fn($c) => new \App\Controllers\Api\VehicleApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\VehicleService::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Controllers\Api\VehicleWorkApiController::class, fn($c) => new \App\Controllers\Api\VehicleWorkApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\VehicleWorkService::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Controllers\Api\VehicleInspectionApiController::class, fn($c) => new \App\Controllers\Api\VehicleInspectionApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\VehicleInspectionService::class)
));
$container->register(\App\Controllers\Api\VehicleConsumableApiController::class, fn($c) => new \App\Controllers\Api\VehicleConsumableApiController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class),
    $c->resolve(JsonResponse::class),
    $c->resolve(\App\Services\VehicleConsumableService::class)
));
$container->register(\App\Controllers\Pages\VehicleConsumableController::class, fn($c) => new \App\Controllers\Pages\VehicleConsumableController(
    $c->resolve(Request::class),
    $c->resolve(\App\Services\AuthService::class),
    $c->resolve(\App\Services\ViewDataService::class),
    $c->resolve(\App\Services\ActivityLogger::class),
    $c->resolve(\App\Repositories\EmployeeRepository::class)
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
