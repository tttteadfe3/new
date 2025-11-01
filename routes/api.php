<?php

use App\Controllers\Api\EmployeeApiController;
use App\Controllers\Api\HolidayApiController;
use App\Controllers\Api\HumanResourceApiController;
use App\Controllers\Api\LeaveRequestApiController;
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
    // --- Employee and HR ---
    $router->get('/employees', [EmployeeApiController::class, 'index'])->name('api.employees.index')->middleware('auth')->middleware('permission', 'employee.view');
    $router->post('/employees', [EmployeeApiController::class, 'store'])->name('api.employees.store')->middleware('auth')->middleware('permission', 'employee.create');
    $router->get('/employees/{id}', [EmployeeApiController::class, 'show'])->name('api.employees.show')->middleware('auth')->middleware('permission', 'employee.view');
    $router->put('/employees/{id}', [EmployeeApiController::class, 'update'])->name('api.employees.update')->middleware('auth')->middleware('permission', 'employee.update');
    $router->post('/hr/assign', [HumanResourceApiController::class, 'assign'])->name('api.hr.assign')->middleware('auth')->middleware('permission', 'employee.assign');
    $router->get('/hr/history', [HumanResourceApiController::class, 'history'])->name('api.hr.history')->middleware('auth')->middleware('permission', 'employee.view_history');

    // --- Holidays ---
    $router->get('/holidays', [HolidayApiController::class, 'index'])->name('api.holidays.index')->middleware('auth');
    $router->post('/holidays', [HolidayApiController::class, 'store'])->name('api.holidays.store')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->put('/holidays/{id}', [HolidayApiController::class, 'update'])->name('api.holidays.update')->middleware('auth')->middleware('permission', 'holiday.manage');
    $router->delete('/holidays/{id}', [HolidayApiController::class, 'destroy'])->name('api.holidays.destroy')->middleware('auth')->middleware('permission', 'holiday.manage');

    // --- (New) Leave Management System ---
    // Employee facing routes
    $router->get('/leave/balance', [LeaveRequestApiController::class, 'getBalance'])->name('api.leave.balance')->middleware('auth');
    $router->get('/leave-requests', [LeaveRequestApiController::class, 'index'])->name('api.leave-requests.index')->middleware('auth');
    $router->post('/leave-requests', [LeaveRequestApiController::class, 'store'])->name('api.leave-requests.store')->middleware('auth');
    $router->post('/leave-requests/{id}/cancel', [LeaveRequestApiController::class, 'cancel'])->name('api.leave-requests.cancel')->middleware('auth');

    // Admin facing routes
    $router->group('/admin/leaves', function($router) {
        $router->get('/entitlements', [LeaveAdminApiController::class, 'getEntitlements'])->name('api.admin.leaves.entitlements')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/calculate-all', [LeaveAdminApiController::class, 'calculateAnnualLeaveForAll'])->name('api.admin.leaves.calculate-all')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/grant', [LeaveAdminApiController::class, 'grantAnnualLeave'])->name('api.admin.leaves.grant')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/adjust', [LeaveAdminApiController::class, 'adjustLeave'])->name('api.admin.leaves.adjust')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/expire', [LeaveAdminApiController::class, 'expireLeave'])->name('api.admin.leaves.expire')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->get('/requests', [LeaveAdminApiController::class, 'getRequests'])->name('api.admin.leaves.requests')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/approve', [LeaveAdminApiController::class, 'approveRequest'])->name('api.admin.leaves.approve')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/reject', [LeaveAdminApiController::class, 'rejectRequest'])->name('api.admin.leaves.reject')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/approve-cancellation', [LeaveAdminApiController::class, 'approveCancellation'])->name('api.admin.leaves.cancel.approve')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/reject-cancellation', [LeaveAdminApiController::class, 'rejectCancellation'])->name('api.admin.leaves.cancel.reject')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->get('/logs', [LeaveAdminApiController::class, 'getLogs'])->name('api.admin.leaves.logs')->middleware('auth')->middleware('permission', 'leave.view_all');
    });

    // --- Littering ---
    $router->get('/littering', [LitteringApiController::class, 'index'])->name('api.littering.index')->middleware('auth')->middleware('permission', 'littering.view');
    $router->post('/littering', [LitteringApiController::class, 'store'])->name('api.littering.store')->middleware('auth')->middleware('permission', 'littering.create');
    $router->post('/littering/{id}/process', [LitteringApiController::class, 'process'])->name('api.littering.process')->middleware('auth')->middleware('permission', 'littering.process');
    $router->get('/littering_admin/reports', [LitteringAdminApiController::class, 'listReports'])->name('api.littering_admin.reports')->middleware('auth')->middleware('permission', 'littering.view');
    $router->post('/littering_admin/reports/{id}/confirm', [LitteringAdminApiController::class, 'confirm'])->name('api.littering_admin.confirm')->middleware('auth')->middleware('permission', 'littering.confirm');
    $router->post('/littering_admin/reports/{id}/approve', [LitteringAdminApiController::class, 'approve'])->name('api.littering_admin.approve')->middleware('auth')->middleware('permission', 'littering.approve');
    $router->delete('/littering_admin/reports/{id}', [LitteringAdminApiController::class, 'destroy'])->name('api.littering_admin.destroy')->middleware('auth')->middleware('permission', 'littering.delete');
    $router->post('/littering_admin/reports/{id}/restore', [LitteringAdminApiController::class, 'restore'])->name('api.littering_admin.restore')->middleware('auth')->middleware('permission', 'littering.restore');
    $router->delete('/littering_admin/reports/{id}/permanent', [LitteringAdminApiController::class, 'permanentlyDelete'])->name('api.littering_admin.permanent')->middleware('auth')->middleware('permission', 'littering.force_delete');

    // --- Organization, Positions, Roles, Users ---
    $router->get('/organization', [OrganizationApiController::class, 'index'])->name('api.organization.index')->middleware('auth');
    $router->get('/organization/{id}/eligible-viewer-employees', [OrganizationApiController::class, 'getEligibleViewerEmployees'])->name('api.organization.eligible-viewer-employees')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->get('/organization/{id}/view-permissions', [OrganizationApiController::class, 'getDepartmentViewPermissions'])->name('api.organization.view-permissions')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->post('/organization', [OrganizationApiController::class, 'store'])->name('api.organization.store')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->put('/organization/{id}', [OrganizationApiController::class, 'update'])->name('api.organization.update')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->delete('/organization/{id}', [OrganizationApiController::class, 'destroy'])->name('api.organization.destroy')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->get('/organization/chart', [OrganizationApiController::class, 'getChart'])->name('api.organization.chart')->middleware('auth')->middleware('permission', 'organization.view');
    $router->get('/organization/managable-departments', [OrganizationApiController::class, 'getManagableDepartments'])->name('api.organization.managable-departments')->middleware('auth');
    $router->post('/positions', [PositionApiController::class, 'store'])->name('api.positions.store')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->put('/positions/{id}', [PositionApiController::class, 'update'])->name('api.positions.update')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->delete('/positions/{id}', [PositionApiController::class, 'delete'])->name('api.positions.delete')->middleware('auth')->middleware('permission', 'organization.manage');
    $router->get('/roles', [RoleApiController::class, 'index'])->name('api.roles.index')->middleware('auth')->middleware('permission', 'role.view');
    $router->post('/roles', [RoleApiController::class, 'store'])->name('api.roles.store')->middleware('auth')->middleware('permission', 'role.create');
    $router->get('/roles/{id}', [RoleApiController::class, 'show'])->name('api.roles.show')->middleware('auth')->middleware('permission', 'role.view');
    $router->put('/roles/{id}', [RoleApiController::class, 'update'])->name('api.roles.update')->middleware('auth')->middleware('permission', 'role.update');
    $router->delete('/roles/{id}', [RoleApiController::class, 'destroy'])->name('api.roles.destroy')->middleware('auth')->middleware('permission', 'role.delete');
    $router->put('/roles/{id}/permissions', [RoleApiController::class, 'updatePermissions'])->name('api.roles.permissions')->middleware('auth')->middleware('permission', 'role.assign_permissions');
    $router->get('/users', [UserApiController::class, 'index'])->name('api.users.index')->middleware('auth')->middleware('permission', 'user.view');
    $router->get('/users/{id}', [UserApiController::class, 'show'])->name('api.users.show')->middleware('auth')->middleware('permission', 'user.view');
    $router->put('/users/{id}', [UserApiController::class, 'update'])->name('api.users.update')->middleware('auth')->middleware('permission', 'user.update');
    $router->post('/users/{id}/link', [UserApiController::class, 'linkEmployee'])->name('api.users.link')->middleware('auth')->middleware('permission', 'user.link');
    $router->post('/users/{id}/unlink', [UserApiController::class, 'unlinkEmployee'])->name('api.users.unlink')->middleware('auth')->middleware('permission', 'user.link');

    // --- Menus, Profile, Logs ---
    $router->get('/menus', [MenuApiController::class, 'index'])->name('api.menus.index')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->post('/menus', [MenuApiController::class, 'store'])->name('api.menus.store')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->get('/menus/{id}', [MenuApiController::class, 'show'])->name('api.menus.show')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->put('/menus/order', [MenuApiController::class, 'updateOrder'])->name('api.menus.order')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->put('/menus/{id}', [MenuApiController::class, 'update'])->name('api.menus.update')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->delete('/menus/{id}', [MenuApiController::class, 'destroy'])->name('api.menus.destroy')->middleware('auth')->middleware('permission', 'menu.manage');
    $router->get('/profile', [ProfileApiController::class, 'index'])->name('api.profile.index')->middleware('auth');
    $router->put('/profile', [ProfileApiController::class, 'update'])->name('api.profile.update')->middleware('auth');
    $router->get('/logs', [LogApiController::class, 'index'])->name('api.logs.index')->middleware('auth')->middleware('permission', 'log.view');
    $router->delete('/logs', [LogApiController::class, 'destroy'])->name('api.logs.destroy')->middleware('auth')->middleware('permission', 'log.delete');

    // --- Waste Collection ---
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
