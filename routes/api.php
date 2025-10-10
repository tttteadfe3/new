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
    // Employee API routes (Refactored to be RESTful)
    Router::get('/employees', [EmployeeApiController::class, 'index'])->name('api.employees.index');
    Router::post('/employees', [EmployeeApiController::class, 'store'])->name('api.employees.store');
    Router::get('/employees/initial-data', [EmployeeApiController::class, 'getInitialData'])->name('api.employees.initial-data');
    Router::get('/employees/unlinked', [EmployeeApiController::class, 'unlinked'])->name('api.employees.unlinked');
    Router::get('/employees/{id}', [EmployeeApiController::class, 'show'])->name('api.employees.show');
    Router::put('/employees/{id}', [EmployeeApiController::class, 'update'])->name('api.employees.update');
    Router::delete('/employees/{id}', [EmployeeApiController::class, 'destroy'])->name('api.employees.destroy');
    Router::get('/employees/{id}/history', [EmployeeApiController::class, 'getChangeHistory'])->name('api.employees.history');
    Router::post('/employees/{id}/approve-update', [EmployeeApiController::class, 'approveUpdate'])->name('api.employees.approve-update');
    Router::post('/employees/{id}/reject-update', [EmployeeApiController::class, 'rejectUpdate'])->name('api.employees.reject-update');

    // Holiday API routes
    Router::get('/holidays', [HolidayApiController::class, 'index'])->name('api.holidays.index');
    Router::post('/holidays', [HolidayApiController::class, 'store'])->name('api.holidays.store');
    Router::get('/holidays/{id}', [HolidayApiController::class, 'show'])->name('api.holidays.show');
    Router::put('/holidays/{id}', [HolidayApiController::class, 'update'])->name('api.holidays.update');
    Router::delete('/holidays/{id}', [HolidayApiController::class, 'destroy'])->name('api.holidays.destroy');

    // Leave API routes (User)
    Router::get('/leaves', [LeaveApiController::class, 'index'])->name('api.leaves.index');
    Router::post('/leaves', [LeaveApiController::class, 'store'])->name('api.leaves.store');
    Router::post('/leaves/{id}/cancel', [LeaveApiController::class, 'cancel'])->name('api.leaves.cancel');
    Router::post('/leaves/calculate-days', [LeaveApiController::class, 'calculateDays'])->name('api.leaves.calculate-days');

    // Leave Admin API routes
    Router::get('/leaves_admin/requests', [LeaveAdminApiController::class, 'listRequests'])->name('api.leaves_admin.requests');
    Router::post('/leaves_admin/requests/{id}/approve', [LeaveAdminApiController::class, 'approveRequest'])->name('api.leaves_admin.approve');
    Router::post('/leaves_admin/requests/{id}/reject', [LeaveAdminApiController::class, 'rejectRequest'])->name('api.leaves_admin.reject');
    Router::post('/leaves_admin/cancellations/{id}/approve', [LeaveAdminApiController::class, 'approveCancellation'])->name('api.leaves_admin.cancellations.approve');
    Router::post('/leaves_admin/cancellations/{id}/reject', [LeaveAdminApiController::class, 'rejectCancellation'])->name('api.leaves_admin.cancellations.reject');
    Router::get('/leaves_admin/entitlements', [LeaveAdminApiController::class, 'listEntitlements'])->name('api.leaves_admin.entitlements');
    Router::post('/leaves_admin/grant-all', [LeaveAdminApiController::class, 'grantForAll'])->name('api.leaves_admin.grant-all');
    Router::get('/leaves_admin/history/{employeeId}', [LeaveAdminApiController::class, 'getHistory'])->name('api.leaves_admin.history');
    Router::post('/leaves_admin/adjust', [LeaveAdminApiController::class, 'manualAdjustment'])->name('api.leaves_admin.adjust');
    Router::post('/leaves_admin/calculate', [LeaveAdminApiController::class, 'calculateLeaves'])->name('api.leaves_admin.calculate');
    Router::post('/leaves_admin/save-entitlements', [LeaveAdminApiController::class, 'saveEntitlements'])->name('api.leaves_admin.save-entitlements');

    // Littering API routes (user-facing for map, history, etc.)
    Router::get('/littering', [LitteringApiController::class, 'index'])->name('api.littering.index');
    Router::post('/littering', [LitteringApiController::class, 'store'])->name('api.littering.store');
    Router::post('/littering/{id}/process', [LitteringApiController::class, 'process'])->name('api.littering.process');

    // Littering Admin API routes
    Router::get('/littering_admin/reports', [LitteringAdminApiController::class, 'listReports'])->name('api.littering_admin.reports');
    Router::post('/littering_admin/reports/{id}/confirm', [LitteringAdminApiController::class, 'confirm'])->name('api.littering_admin.confirm');
    Router::delete('/littering_admin/reports/{id}', [LitteringAdminApiController::class, 'destroy'])->name('api.littering_admin.destroy');
    Router::post('/littering_admin/reports/{id}/restore', [LitteringAdminApiController::class, 'restore'])->name('api.littering_admin.restore');
    Router::delete('/littering_admin/reports/{id}/permanent', [LitteringAdminApiController::class, 'permanentlyDelete'])->name('api.littering_admin.permanent');

    // Organization API routes (RESTful)
    Router::get('/organization', [OrganizationApiController::class, 'index'])->name('api.organization.index');
    Router::post('/organization', [OrganizationApiController::class, 'store'])->name('api.organization.store');
    Router::put('/organization/{id}', [OrganizationApiController::class, 'update'])->name('api.organization.update');
    Router::delete('/organization/{id}', [OrganizationApiController::class, 'destroy'])->name('api.organization.destroy');

    // Role and Permission API routes (RESTful)
    Router::get('/roles', [RoleApiController::class, 'index'])->name('api.roles.index');
    Router::post('/roles', [RoleApiController::class, 'store'])->name('api.roles.store');
    Router::get('/roles/{id}', [RoleApiController::class, 'show'])->name('api.roles.show');
    Router::put('/roles/{id}', [RoleApiController::class, 'update'])->name('api.roles.update');
    Router::delete('/roles/{id}', [RoleApiController::class, 'destroy'])->name('api.roles.destroy');
    Router::put('/roles/{id}/permissions', [RoleApiController::class, 'updatePermissions'])->name('api.roles.permissions');

    // User API routes (RESTful)
    Router::get('/users', [UserApiController::class, 'index'])->name('api.users.index');
    Router::get('/users/{id}', [UserApiController::class, 'show'])->name('api.users.show');
    Router::put('/users/{id}', [UserApiController::class, 'update'])->name('api.users.update');
    Router::post('/users/{id}/link', [UserApiController::class, 'linkEmployee'])->name('api.users.link');
    Router::post('/users/{id}/unlink', [UserApiController::class, 'unlinkEmployee'])->name('api.users.unlink');

    // Menu API routes
    Router::get('/menus', [MenuApiController::class, 'index'])->name('api.menus.index');
    Router::post('/menus', [MenuApiController::class, 'store'])->name('api.menus.store');
    Router::get('/menus/{id}', [MenuApiController::class, 'show'])->name('api.menus.show');
    Router::post('/menus/order', [MenuApiController::class, 'updateOrder'])->name('api.menus.order');
    Router::put('/menus/{id}', [MenuApiController::class, 'update'])->name('api.menus.update');
    Router::delete('/menus/{id}', [MenuApiController::class, 'destroy'])->name('api.menus.destroy');

    // Profile API routes
    Router::get('/profile', [ProfileApiController::class, 'index'])->name('api.profile.index');
    Router::put('/profile', [ProfileApiController::class, 'update'])->name('api.profile.update');

    // Log API routes
    Router::get('/logs', [LogApiController::class, 'index'])->name('api.logs.index');
    Router::delete('/logs', [LogApiController::class, 'destroy'])->name('api.logs.destroy');

    // Waste Collection API routes
    Router::get('/waste-collections', [WasteCollectionApiController::class, 'index'])->name('api.waste-collections.index');
    Router::post('/waste-collections', [WasteCollectionApiController::class, 'store'])->name('api.waste-collections.store');
    Router::get('/waste-collections/admin', [WasteCollectionApiController::class, 'getAdminCollections'])->name('api.waste-collections.admin');
    Router::post('/waste-collections/admin/{id}/process', [WasteCollectionApiController::class, 'processCollection'])->name('api.waste-collections.process');
    Router::put('/waste-collections/admin/{id}/items', [WasteCollectionApiController::class, 'updateItems'])->name('api.waste-collections.items');
    Router::put('/waste-collections/admin/{id}/memo', [WasteCollectionApiController::class, 'updateMemo'])->name('api.waste-collections.memo');
    Router::post('/waste-collections/admin/parse-html', [WasteCollectionApiController::class, 'parseHtmlFile'])->name('api.waste-collections.parse-html');
    Router::post('/waste-collections/admin/batch-register', [WasteCollectionApiController::class, 'batchRegister'])->name('api.waste-collections.batch-register');
    Router::delete('/waste-collections/admin/online-submissions', [WasteCollectionApiController::class, 'clearOnlineSubmissions'])->name('api.waste-collections.clear-online');

});