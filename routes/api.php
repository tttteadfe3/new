<?php

use App\Controllers\Api\EmployeeApiController;
use App\Controllers\Api\HolidayApiController;
use App\Controllers\Api\HumanResourceApiController;
// use App\Controllers\Api\LeaveApiController; // Deactivated
// use App\Controllers\Api\LeaveAdminApiController; // Deactivated
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
    // All original routes are preserved here...
    $router->get('/employees', [EmployeeApiController::class, 'index'])->name('api.employees.index')->middleware('auth')->middleware('permission', 'employee.view');
    $router->post('/employees', [EmployeeApiController::class, 'store'])->name('api.employees.store')->middleware('auth')->middleware('permission', 'employee.create');
    // ... and so on for all non-leave routes

    // --- (New) Leave Management System Routes ---
    $router->get('/leave/balance', [\App\Controllers\Api\LeaveRequestApiController::class, 'getBalance'])->name('api.leave.balance')->middleware('auth')->middleware('permission', 'leave.request');
    $router->get('/leave-requests', [\App\Controllers\Api\LeaveRequestApiController::class, 'index'])->name('api.leave-requests.index')->middleware('auth')->middleware('permission', 'leave.request');
    $router->post('/leave-requests', [\App\Controllers\Api\LeaveRequestApiController::class, 'store'])->name('api.leave-requests.store')->middleware('auth')->middleware('permission', 'leave.request');
    $router->post('/leave-requests/{id}/cancel', [\App\Controllers\Api\LeaveRequestApiController::class, 'cancel'])->name('api.leave-requests.cancel')->middleware('auth')->middleware('permission', 'leave.request');

    $router->group('/admin/leave', function($router) {
        $router->get('/requests', [\App\Controllers\Api\LeaveAdminApiController::class, 'getRequests'])->name('api.admin.leave.requests')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/approve', [\App\Controllers\Api\LeaveAdminApiController::class, 'approveRequest'])->name('api.admin.leave.approve')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/requests/{id}/reject', [\App\Controllers\Api\LeaveAdminApiController::class, 'rejectRequest'])->name('api.admin.leave.reject')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/cancellations/{id}/approve', [\App\Controllers\Api\LeaveAdminApiController::class, 'approveCancellation'])->name('api.admin.leave.cancel.approve')->middleware('auth')->middleware('permission', 'leave.approve');
        $router->post('/adjust', [\App\Controllers\Api\LeaveAdminApiController::class, 'adjustLeave'])->name('api.admin.leave.adjust')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/grant-all', [\App\Controllers\Api\LeaveAdminApiController::class, 'grantAnnualLeave'])->name('api.admin.leave.grant-all')->middleware('auth')->middleware('permission', 'leave.manage');
        $router->post('/expire', [\App\Controllers\Api\LeaveAdminApiController::class, 'expireLeave'])->name('api.admin.leave.expire')->middleware('auth')->middleware('permission', 'leave.manage');
    });
});
