<?php

use App\Core\Router;
use App\Controllers\Api\EmployeeApiController;
use App\Controllers\Api\HolidayApiController;
use App\Controllers\Api\LeaveApiController;
use App\Controllers\Api\LeaveAdminApiController;
use App\Controllers\Api\LitteringApiController;
use App\Controllers\Api\LitteringAdminApiController;
use App\Controllers\Api\OrganizationApiController;
use App\Controllers\Api\RoleApiController;
use App\Controllers\Api\UserApiController;
use App\Controllers\Api\MenuApiController;
use App\Controllers\Api\ProfileApiController;
use App\Controllers\Api\LogApiController;
use App\Controllers\Api\WasteCollectionApiController;

Router::group('/api', function() {
    // Employee API routes
    Router::get('/employees', [EmployeeApiController::class, 'index']);
    Router::get('/employees/unlinked', [EmployeeApiController::class, 'unlinked']);

    // Holiday API routes
    Router::get('/holidays', [HolidayApiController::class, 'index']);
    Router::post('/holidays', [HolidayApiController::class, 'store']);
    Router::get('/holidays/{id}', [HolidayApiController::class, 'show']);
    Router::put('/holidays/{id}', [HolidayApiController::class, 'update']);
    Router::delete('/holidays/{id}', [HolidayApiController::class, 'destroy']);

    // Leave API routes (user)
    Router::get('/leaves', [LeaveApiController::class, 'index']);
    Router::post('/leaves', [LeaveApiController::class, 'store']);
    Router::post('/leaves/{id}/cancel', [LeaveApiController::class, 'cancel']);
    Router::post('/leaves/calculate-days', [LeaveApiController::class, 'calculateDays']);

    // Leave Admin API routes
    Router::get('/leaves_admin/requests', [LeaveAdminApiController::class, 'listRequests']);
    Router::post('/leaves_admin/requests/{id}/approve', [LeaveAdminApiController::class, 'approveRequest']);
    Router::post('/leaves_admin/requests/{id}/reject', [LeaveAdminApiController::class, 'rejectRequest']);
    Router::post('/leaves_admin/cancellations/{id}/approve', [LeaveAdminApiController::class, 'approveCancellation']);
    Router::post('/leaves_admin/cancellations/{id}/reject', [LeaveAdminApiController::class, 'rejectCancellation']);
    Router::get('/leaves_admin/entitlements', [LeaveAdminApiController::class, 'listEntitlements']);
    Router::post('/leaves_admin/grant-all', [LeaveAdminApiController::class, 'grantForAll']);
    Router::get('/leaves_admin/history/{employeeId}', [LeaveAdminApiController::class, 'getHistory']);
    Router::post('/leaves_admin/adjust', [LeaveAdminApiController::class, 'manualAdjustment']);
    Router::post('/leaves_admin/calculate', [LeaveAdminApiController::class, 'calculateLeaves']);
    Router::post('/leaves_admin/save-entitlements', [LeaveAdminApiController::class, 'saveEntitlements']);

    // Littering API routes (user-facing for map, history, etc.)
    Router::get('/littering', [LitteringApiController::class, 'index']);
    Router::post('/littering', [LitteringApiController::class, 'store']);
    Router::post('/littering/{id}/process', [LitteringApiController::class, 'process']);

    // Littering Admin API routes
    Router::get('/littering_admin/reports', [LitteringAdminApiController::class, 'listReports']);
    Router::post('/littering_admin/reports/{id}/confirm', [LitteringAdminApiController::class, 'confirm']);
    Router::delete('/littering_admin/reports/{id}', [LitteringAdminApiController::class, 'destroy']);
    Router::post('/littering_admin/reports/{id}/restore', [LitteringAdminApiController::class, 'restore']);
    Router::delete('/littering_admin/reports/{id}/permanent', [LitteringAdminApiController::class, 'permanentlyDelete']);

    // Organization API routes
    Router::get('/organization', [OrganizationApiController::class, 'index']);
    Router::post('/organization', [OrganizationApiController::class, 'store']);
    Router::put('/organization/{id}', [OrganizationApiController::class, 'update']);
    Router::delete('/organization/{id}', [OrganizationApiController::class, 'destroy']);

    // Role and Permission API routes
    Router::get('/roles', [RoleApiController::class, 'index']);
    Router::post('/roles', [RoleApiController::class, 'store']);
    Router::get('/roles/{id}', [RoleApiController::class, 'show']);
    Router::put('/roles/{id}', [RoleApiController::class, 'update']);
    Router::delete('/roles/{id}', [RoleApiController::class, 'destroy']);
    Router::put('/roles/{id}/permissions', [RoleApiController::class, 'updatePermissions']);

    // User API routes
    Router::get('/users', [UserApiController::class, 'index']);
    Router::get('/users/{id}', [UserApiController::class, 'show']);
    Router::put('/users/{id}', [UserApiController::class, 'update']);
    Router::post('/users/{id}/link', [UserApiController::class, 'linkEmployee']);
    Router::post('/users/{id}/unlink', [UserApiController::class, 'unlinkEmployee']);

    // Menu API routes
    Router::get('/menus', [MenuApiController::class, 'index']);
    Router::post('/menus', [MenuApiController::class, 'store']);
    Router::put('/menus/order', [MenuApiController::class, 'updateOrder']);
    Router::put('/menus/{id}', [MenuApiController::class, 'update']);
    Router::delete('/menus/{id}', [MenuApiController::class, 'destroy']);

    // Profile API routes
    Router::get('/profile', [ProfileApiController::class, 'index']);
    Router::put('/profile', [ProfileApiController::class, 'update']);

    // Log API routes
    Router::get('/logs', [LogApiController::class, 'index']);
    Router::delete('/logs', [LogApiController::class, 'destroy']);

    // Waste Collection API routes
    Router::get('/waste-collections', [WasteCollectionApiController::class, 'index']);
    Router::post('/waste-collections', [WasteCollectionApiController::class, 'store']);
    Router::get('/waste-collections/admin', [WasteCollectionApiController::class, 'getAdminCollections']);
    Router::post('/waste-collections/admin/{id}/process', [WasteCollectionApiController::class, 'processCollection']);
    Router::put('/waste-collections/admin/{id}/items', [WasteCollectionApiController::class, 'updateItems']);
    Router::put('/waste-collections/admin/{id}/memo', [WasteCollectionApiController::class, 'updateMemo']);
    Router::post('/waste-collections/admin/parse-html', [WasteCollectionApiController::class, 'parseHtmlFile']);
    Router::post('/waste-collections/admin/batch-register', [WasteCollectionApiController::class, 'batchRegister']);
    Router::delete('/waste-collections/admin/online-submissions', [WasteCollectionApiController::class, 'clearOnlineSubmissions']);
});