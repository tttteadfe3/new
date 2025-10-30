<?php

use App\Controllers\Api\EmployeeApiController;
use App\Controllers\Api\HolidayApiController;
use App\Controllers\Api\HumanResourceApiController;
use App\Controllers\Api\LeaveApiController;
use App\Controllers\Api\LeaveAdminApiController;
use App\Controllers\Api\LitteringApiController;
use App\Controllers\Api\LitteringAdminApiController;
use App\Controllers\Api\OrganizationApiController;
use App\Controllers\Api\RoleApiController;
use App\Controllers\Api\UserApiController;
use App\Controllers\Api\MenuApiController;
use App\Controllers\Api\ProfileApiController;
use App\Controllers\Api\PositionApiController;
use App\Controllers\Api\LogApiController;
use App\Controllers\Api\WasteCollectionApiController;

$router->group('/api', function($router) {
    // Employee API routes
    $router->get('/employees', [EmployeeApiController::class, 'index'])->name('api.employees.index')->middleware('auth')->middleware('permission', 'employee.view');
    $router->post('/employees', [EmployeeApiController::class, 'store'])->name('api.employees.store')->middleware('auth')->middleware('permission', 'employee.create');
    $router->get('/employees/initial-data', [EmployeeApiController::class, 'getInitialData'])->name('api.employees.initial-data')->middleware('auth')->middleware('permission', 'employee.view');
    $router->get('/employees/unlinked', [EmployeeApiController::class, 'unlinked'])->name('api.employees.unlinked')->middleware('auth')->middleware('permission', 'user.link');
    $router->get('/employees/{id}', [EmployeeApiController::class, 'show'])->name('api.employees.show')->middleware('auth')->middleware('permission', 'employee.view');
    $router->put('/employees/{id}', [EmployeeApiController::class, 'update'])->name('api.employees.update')->middleware('auth')->middleware('permission', 'employee.update');
    $router->delete('/employees/{id}', [EmployeeApiController::class, 'destroy'])->name('api.employees.destroy')->middleware('auth')->middleware('permission', 'employee.delete');
    $router->post('/employees/{id}/terminate', [EmployeeApiController::class, 'terminate'])->name('api.employees.terminate')->middleware('auth')->middleware('permission', 'employee.terminate');
    $router->get('/employees/{id}/history', [EmployeeApiController::class, 'getChangeHistory'])->name('api.employees.history')->middleware('auth')->middleware('permission', 'employee.view');
    $router->post('/employees/{id}/approve-update', [EmployeeApiController::class, 'approveUpdate'])->name('api.employees.approve-update')->middleware('auth')->middleware('permission', 'employee.approve');
    $router->post('/employees/{id}/reject-update', [EmployeeApiController::class, 'rejectUpdate'])->name('api.employees.reject-update')->middleware('auth')->middleware('permission', 'employee.approve');

    // Human Resource (HR) Order API routes
    $router->post('/hr/orders', [HumanResourceApiController::class, 'store'])->name('api.hr.orders.store')->middleware('auth')->middleware('permission', 'employee.assign');

    // Holiday API routes
    $router->get('/holidays', [HolidayApiController::class, 'index'])->name('api.holidays.index')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->post('/holidays', [HolidayApiController::class, 'store'])->name('api.holidays.store')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->get('/holidays/{id}', [HolidayApiController::class, 'show'])->name('api.holidays.show')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->put('/holidays/{id}', [HolidayApiController::class, 'update'])->name('api.holidays.update')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->delete('/holidays/{id}', [HolidayApiController::class, 'destroy'])->name('api.holidays.destroy')->middleware('auth')->middleware('permission', 'holiday.manage');

    // (New) Leave API Routes
    $router->get('/leaves/my-balance', [LeaveApiController::class, 'getMyBalance'])->name('api.leaves.my-balance')->middleware('auth');
    $router->get('/leaves', [LeaveApiController::class, 'index'])->name('api.leaves.index')->middleware('auth');
    $router->post('/leaves', [LeaveApiController::class, 'store'])->name('api.leaves.store')->middleware('auth');
    $router->post('/leaves/{id}/cancel', [LeaveApiController::class, 'requestCancellation'])->name('api.leaves.cancel')->middleware('auth');
    $router->post('/leaves/calculate-days', [LeaveApiController::class, 'calculateDays'])->name('api.leaves.calculate-days')->middleware('auth');

    // (New) Leave Admin API Routes
    $router->get('/admin/leaves/balances', [LeaveAdminApiController::class, 'getLeaveBalances'])->name('api.admin.leaves.balances')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->get('/admin/leaves/requests', [LeaveAdminApiController::class, 'getLeaveRequests'])->name('api.admin.leaves.requests')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/admin/leaves/requests/{id}/approve', [LeaveAdminApiController::class, 'approveRequest'])->name('api.admin.leaves.approve')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/admin/leaves/requests/{id}/reject', [LeaveAdminApiController::class, 'rejectRequest'])->name('api.admin.leaves.reject')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/admin/leaves/requests/{id}/approve-cancellation', [LeaveAdminApiController::class, 'approveCancellation'])->name('api.admin.leaves.approve-cancellation')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/admin/leaves/requests/{id}/reject-cancellation', [LeaveAdminApiController::class, 'rejectCancellation'])->name('api.admin.leaves.reject-cancellation')->middleware('auth')->middleware('permission', 'leave.approve');

    // Manual Leave Management
    $router->post('/admin/leaves/grant-annual', [LeaveAdminApiController::class, 'grantAnnualLeaveForAll'])->name('api.admin.leaves.grant-annual')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/admin/leaves/expire-unused', [LeaveAdminApiController::class, 'expireUnusedLeaveForAll'])->name('api.admin.leaves.expire-unused')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/admin/leaves/adjust', [LeaveAdminApiController::class, 'manualAdjustment'])->name('api.admin.leaves.adjust')->middleware('auth')->middleware('permission', 'leave.manage');

    // Littering API routes
    $router->get('/littering', [LitteringApiController::class, 'index'])->name('api.littering.index')->middleware('auth')->middleware('permission', 'littering.view');
    $router->post('/littering', [LitteringApiController::class, 'store'])->name('api.littering.store')->middleware('auth')->middleware('permission', 'littering.create');
    $router->post('/littering/{id}/process', [LitteringApiController::class, 'process'])->name('api.littering.process')->middleware('auth')->middleware('permission', 'littering.process');

    // Littering Admin API routes
    $router->get('/littering_admin/reports', [LitteringAdminApiController::class, 'listReports'])->name('api.littering_admin.reports')->middleware('auth')->middleware('permission', 'littering.view');
    $router->post('/littering_admin/reports/{id}/confirm', [LitteringAdminApiController::class, 'confirm'])->name('api.littering_admin.confirm')->middleware('auth')->middleware('permission', 'littering.confirm');
$router->post('/littering_admin/reports/{id}/approve', [LitteringAdminApiController::class, 'approve'])->name('api.littering_admin.approve')->middleware('auth')->middleware('permission', 'littering.approve');
    $router->delete('/littering_admin/reports/{id}', [LitteringAdminApiController::class, 'destroy'])->name('api.littering_admin.destroy')->middleware('auth')->middleware('permission', 'littering.delete');
    $router->post('/littering_admin/reports/{id}/restore', [LitteringAdminApiController::class, 'restore'])->name('api.littering_admin.restore')->middleware('auth')->middleware('permission', 'littering.restore');
    $router->delete('/littering_admin/reports/{id}/permanent', [LitteringAdminApiController::class, 'permanentlyDelete'])->name('api.littering_admin.permanent')->middleware('auth')->middleware('permission', 'littering.force_delete');

    // Organization API routes
    $router->get('/organization', [OrganizationApiController::class, 'index'])->name('api.organization.index')->middleware('auth');
    $router->get('/organization/{id}/eligible-viewer-employees', [OrganizationApiController::class, 'getEligibleViewerEmployees'])->name('api.organization.eligible-viewer-employees')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->get('/organization/{id}/view-permissions', [OrganizationApiController::class, 'getDepartmentViewPermissions'])->name('api.organization.view-permissions')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->post('/organization', [OrganizationApiController::class, 'store'])->name('api.organization.store')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->put('/organization/{id}', [OrganizationApiController::class, 'update'])->name('api.organization.update')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->delete('/organization/{id}', [OrganizationApiController::class, 'destroy'])->name('api.organization.destroy')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->get('/organization/chart', [OrganizationApiController::class, 'getChart'])->name('api.organization.chart')->middleware('auth')->middleware('permission', 'organization.view');
    $router->get('/organization/managable-departments', [OrganizationApiController::class, 'getManagableDepartments'])->name('api.organization.managable-departments')->middleware('auth');

    // Position API routes
    $router->post('/positions', [PositionApiController::class, 'store'])->name('api.positions.store')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->put('/positions/{id}', [PositionApiController::class, 'update'])->name('api.positions.update')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->delete('/positions/{id}', [PositionApiController::class, 'delete'])->name('api.positions.delete')->middleware('auth')->middleware('permission', 'organization.manage');

    // Role and Permission API routes
    $router->get('/roles', [RoleApiController::class, 'index'])->name('api.roles.index')->middleware('auth')->middleware('permission', 'role.view');
    $router->post('/roles', [RoleApiController::class, 'store'])->name('api.roles.store')->middleware('auth')->middleware('permission', 'role.create');
    $router->get('/roles/{id}', [RoleApiController::class, 'show'])->name('api.roles.show')->middleware('auth')->middleware('permission', 'role.view');
    $router->put('/roles/{id}', [RoleApiController::class, 'update'])->name('api.roles.update')->middleware('auth')->middleware('permission', 'role.update');
    $router->delete('/roles/{id}', [RoleApiController::class, 'destroy'])->name('api.roles.destroy')->middleware('auth')->middleware('permission', 'role.delete');
    $router->put('/roles/{id}/permissions', [RoleApiController::class, 'updatePermissions'])->name('api.roles.permissions')->middleware('auth')->middleware('permission', 'role.assign_permissions');

    // User API routes
    $router->get('/users', [UserApiController::class, 'index'])->name('api.users.index')->middleware('auth')->middleware('permission', 'user.view');
    $router->get('/users/{id}', [UserApiController::class, 'show'])->name('api.users.show')->middleware('auth')->middleware('permission', 'user.view');
    $router->put('/users/{id}', [UserApiController::class, 'update'])->name('api.users.update')->middleware('auth')->middleware('permission', 'user.update');
    $router->post('/users/{id}/link', [UserApiController::class, 'linkEmployee'])->name('api.users.link')->middleware('auth')->middleware('permission', 'user.link');
    $router->post('/users/{id}/unlink', [UserApiController::class, 'unlinkEmployee'])->name('api.users.unlink')->middleware('auth')->middleware('permission', 'user.link');

    // Menu API routes
    $router->get('/menus', [MenuApiController::class, 'index'])->name('api.menus.index')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->post('/menus', [MenuApiController::class, 'store'])->name('api.menus.store')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->get('/menus/{id}', [MenuApiController::class, 'show'])->name('api.menus.show')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->put('/menus/order', [MenuApiController::class, 'updateOrder'])->name('api.menus.order')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->put('/menus/{id}', [MenuApiController::class, 'update'])->name('api.menus.update')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->delete('/menus/{id}', [MenuApiController::class, 'destroy'])->name('api.menus.destroy')->middleware('auth')->middleware('permission', 'menu.manage');

    // Profile API routes
    $router->get('/profile', [ProfileApiController::class, 'index'])->name('api.profile.index')->middleware('auth');
    $router->put('/profile', [ProfileApiController::class, 'update'])->name('api.profile.update')->middleware('auth');

    // Log API routes
    $router->get('/logs', [LogApiController::class, 'index'])->name('api.logs.index')->middleware('auth')->middleware('permission', 'log.view');
    $router->delete('/logs', [LogApiController::class, 'destroy'])->name('api.logs.destroy')->middleware('auth')->middleware('permission', 'log.delete');

    // Waste Collection API routes
    $router->get('/waste-collections', [WasteCollectionApiController::class, 'index'])->name('api.waste-collections.index')->middleware('auth')->middleware('permission', 'waste.view');
    $router->post('/waste-collections', [WasteCollectionApiController::class, 'store'])->name('api.waste-collections.store')->middleware('auth')->middleware('permission', 'waste.view');
    $router->get('/waste-collections/admin', [WasteCollectionApiController::class, 'getAdminCollections'])->name('api.waste-collections.admin')->middleware('auth')->middleware('permission', 'waste.manage');
    $router->post('/waste-collections/admin/{id}/process', [WasteCollectionApiController::class, 'processCollection'])->name('api.waste-collections.process')->middleware('auth')->middleware('permission', 'waste.process');
    $router->put('/waste-collections/admin/{id}/items', [WasteCollectionApiController::class, 'updateItems'])->name('api.waste-collections.items')->middleware('auth')->middleware('permission', 'waste.manage');
    $router->put('/waste-collections/admin/{id}/memo', [WasteCollectionApiController::class, 'updateMemo'])->name('api.waste-collections.memo')->middleware('auth')->middleware('permission', 'waste.manage');
    $router->post('/waste-collections/admin/parse-html', [WasteCollectionApiController::class, 'parseHtmlFile'])->name('api.waste-collections.parse-html')->middleware('auth')->middleware('permission', 'waste.manage');
    $router->post('/waste-collections/admin/batch-register', [WasteCollectionApiController::class, 'batchRegister'])->name('api.waste-collections.batch-register')->middleware('auth')->middleware('permission', 'waste.manage');
    $router->delete('/waste-collections/admin/online-submissions', [WasteCollectionApiController::class, 'clearOnlineSubmissions'])->name('api.waste-collections.clear-online')->middleware('auth')->middleware('permission', 'waste.manage');
});
