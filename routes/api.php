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

    // Supply Management API routes
    use App\Controllers\Api\SupplyCategoryApiController;
    use App\Controllers\Api\SupplyItemApiController;
    use App\Controllers\Api\SupplyPlanApiController;
    use App\Controllers\Api\SupplyPurchaseApiController;
    use App\Controllers\Api\SupplyDistributionApiController;
    use App\Controllers\Api\SupplyStockApiController;
    use App\Controllers\Api\SupplyReportApiController;
    use App\Controllers\Api\VehicleApiController;
    use App\Controllers\Api\VehicleMaintenanceApiController;
    use App\Controllers\Api\VehicleInspectionApiController;
    use App\Controllers\Api\VehicleConsumableApiController;

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

    // 연차 신청 관련 API (Leave Application APIs)
    $router->post('/leaves/apply', [LeaveApiController::class, 'applyLeave'])->name('api.leaves.apply')->middleware('auth')->middleware('permission', 'leave.view');
    $router->post('/leaves/calculate-days', [LeaveApiController::class, 'calculateDays'])->name('api.leaves.calculate-days')->middleware('auth')->middleware('permission', 'leave.view');
    
    // 연차 현황 및 잔여량 조회 API
    $router->get('/leaves/balance', [LeaveApiController::class, 'getBalance'])->name('api.leaves.balance')->middleware('auth')->middleware('permission', 'leave.view');

    $router->get('/leaves/history', [LeaveApiController::class, 'getHistory'])->name('api.leaves.history')->middleware('auth')->middleware('permission', 'leave.view');
    
    // 연차 신청 취소 관련 API (개별 신청 ID 기반)
    $router->post('/leaves/applications/{id}/cancel', [LeaveApiController::class, 'cancelApplicationById'])->name('api.leaves.applications.cancel')->middleware('auth')->middleware('permission', 'leave.view');
    $router->post('/leaves/applications/{id}/request-cancel', [LeaveApiController::class, 'requestCancellationById'])->name('api.leaves.applications.request-cancel')->middleware('auth')->middleware('permission', 'leave.view');
    
    // 휴일 정보 조회 API (Holiday API for calendar)
    $router->get('/holidays', [HolidayApiController::class, 'getHolidays'])->name('api.holidays.get')->middleware('auth');

    // Leave Admin API routes
    $router->get('/leaves_admin/requests', [LeaveAdminApiController::class, 'listRequests'])->name('api.leaves_admin.requests')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/requests/{id}', [LeaveAdminApiController::class, 'getRequest'])->name('api.leaves_admin.request')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/requests/{id}/approve', [LeaveAdminApiController::class, 'approveRequest'])->name('api.leaves_admin.approve')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/requests/{id}/reject', [LeaveAdminApiController::class, 'rejectRequest'])->name('api.leaves_admin.reject')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/cancellations/{id}', [LeaveAdminApiController::class, 'getCancellation'])->name('api.leaves_admin.cancellation')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/cancellations/{id}/approve', [LeaveAdminApiController::class, 'approveCancellation'])->name('api.leaves_admin.cancellations.approve')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/cancellations/{id}/reject', [LeaveAdminApiController::class, 'rejectCancellation'])->name('api.leaves_admin.cancellations.reject')->middleware('auth')->middleware('permission', 'leave.approve');

    
    // 관리자 대시보드 API
    $router->get('/leaves_admin/dashboard', [LeaveAdminApiController::class, 'getDashboardData'])->name('api.leaves_admin.dashboard')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/pending-requests', [LeaveAdminApiController::class, 'getPendingRequests'])->name('api.leaves_admin.pending-requests')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/pending-cancellations', [LeaveAdminApiController::class, 'getPendingCancellations'])->name('api.leaves_admin.pending-cancellations')->middleware('auth')->middleware('permission', 'leave.approve');
    
    // 연차 부여/조정/소멸 API
    $router->get('/leaves_admin/export', [LeaveAdminApiController::class, 'exportData'])->name('api.leaves_admin.export')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/adjust-leave', [LeaveAdminApiController::class, 'adjustLeave'])->name('api.leaves_admin.adjust-leave')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/leaves_admin/expire-leave', [LeaveAdminApiController::class, 'expireLeave'])->name('api.leaves_admin.expire-leave')->middleware('auth')->middleware('permission', 'leave.manage');
    
    // 추가 관리 API
    $router->get('/leaves_admin/processed-requests', [LeaveAdminApiController::class, 'getProcessedRequests'])->name('api.leaves_admin.processed-requests')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/team-calendar', [LeaveAdminApiController::class, 'getTeamCalendar'])->name('api.leaves_admin.team-calendar')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/team-status', [LeaveAdminApiController::class, 'getTeamStatus'])->name('api.leaves_admin.team-status')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/monthly-stats', [LeaveAdminApiController::class, 'getMonthlyStats'])->name('api.leaves_admin.monthly-stats')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->get('/leaves_admin/day-detail', [LeaveAdminApiController::class, 'getDayDetail'])->name('api.leaves_admin.day-detail')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/calculate-grant-targets', [LeaveAdminApiController::class, 'calculateGrantTargets'])->name('api.leaves_admin.calculate-grant-targets')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/leaves_admin/execute-grant', [LeaveAdminApiController::class, 'executeGrant'])->name('api.leaves_admin.execute-grant')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->get('/leaves_admin/adjustment-history', [LeaveAdminApiController::class, 'getAdjustmentHistory'])->name('api.leaves_admin.adjustment-history')->middleware('auth')->middleware('permission', 'leave.approve');
    $router->post('/leaves_admin/search-expire-targets', [LeaveAdminApiController::class, 'searchExpireTargets'])->name('api.leaves_admin.search-expire-targets')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/leaves_admin/execute-expire', [LeaveAdminApiController::class, 'executeExpire'])->name('api.leaves_admin.execute-expire')->middleware('auth')->middleware('permission', 'leave.manage');
    $router->post('/leaves_admin/bulk-approve', [LeaveAdminApiController::class, 'bulkApprove'])->name('api.leaves_admin.bulk-approve')->middleware('auth')->middleware('permission', 'leave.approve');


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


    // 분류 관리 API
    $router->get('/supply/categories', [SupplyCategoryApiController::class, 'index'])->name('api.supply.categories.index')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->get('/supply/categories/level/{level}', [SupplyCategoryApiController::class, 'getByLevel'])->name('api.supply.categories.by-level')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->get('/supply/categories/generate-code', [SupplyCategoryApiController::class, 'generateCode'])->name('api.supply.categories.generate-code')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->post('/supply/categories', [SupplyCategoryApiController::class, 'store'])->name('api.supply.categories.store')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->put('/supply/categories/{id}/toggle-status', [SupplyCategoryApiController::class, 'toggleStatus'])->name('api.supply.categories.toggle-status')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->get('/supply/categories/{id}', [SupplyCategoryApiController::class, 'show'])->name('api.supply.categories.show')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->put('/supply/categories/{id}', [SupplyCategoryApiController::class, 'update'])->name('api.supply.categories.update')->middleware('auth')->middleware('permission', 'supply.category.manage');
    $router->delete('/supply/categories/{id}', [SupplyCategoryApiController::class, 'destroy'])->name('api.supply.categories.destroy')->middleware('auth')->middleware('permission', 'supply.category.manage');

    // 품목 관리 API
    $router->get('/supply/items', [SupplyItemApiController::class, 'index'])->name('api.supply.items.index')->middleware('auth')->middleware('permission', 'supply.item.view');
    $router->get('/supply/items/active', [SupplyItemApiController::class, 'getActiveItems'])->name('api.supply.items.active')->middleware('auth')->middleware('permission', 'supply.item.view');
    $router->get('/supply/items/generate-code', [SupplyItemApiController::class, 'generateCode'])->name('api.supply.items.generate-code')->middleware('auth')->middleware('permission', 'supply.item.manage');
    $router->post('/supply/items', [SupplyItemApiController::class, 'store'])->name('api.supply.items.store')->middleware('auth')->middleware('permission', 'supply.item.manage');
    $router->get('/supply/items/{id}', [SupplyItemApiController::class, 'show'])->name('api.supply.items.show')->middleware('auth')->middleware('permission', 'supply.item.view');
    $router->put('/supply/items/{id}', [SupplyItemApiController::class, 'update'])->name('api.supply.items.update')->middleware('auth')->middleware('permission', 'supply.item.manage');
    $router->delete('/supply/items/{id}', [SupplyItemApiController::class, 'destroy'])->name('api.supply.items.destroy')->middleware('auth')->middleware('permission', 'supply.item.manage');
    $router->put('/supply/items/{id}/toggle-status', [SupplyItemApiController::class, 'toggleStatus'])->name('api.supply.items.toggle-status')->middleware('auth')->middleware('permission', 'supply.item.manage');

    // 연간 계획 관리 API
    $router->get('/supply/plans', [SupplyPlanApiController::class, 'index'])->name('api.supply.plans.index')->middleware('auth')->middleware('permission', 'supply.plan.view');
    $router->post('/supply/plans', [SupplyPlanApiController::class, 'store'])->name('api.supply.plans.store')->middleware('auth')->middleware('permission', 'supply.plan.manage');
    $router->post('/supply/plans/import-excel', [SupplyPlanApiController::class, 'importExcel'])->name('api.supply.plans.import-excel')->middleware('auth')->middleware('permission', 'supply.plan.manage');
    $router->get('/supply/plans/export-excel/{year}', [SupplyPlanApiController::class, 'exportExcel'])->name('api.supply.plans.export-excel')->middleware('auth')->middleware('permission', 'supply.plan.view');
    $router->get('/supply/plans/budget-summary', [SupplyPlanApiController::class, 'getBudgetSummary'])->name('api.supply.plans.budget-summary')->middleware('auth')->middleware('permission', 'supply.plan.view');
    $router->post('/supply/plans/copy', [SupplyPlanApiController::class, 'copyPlans'])->name('api.supply.plans.copy')->middleware('auth')->middleware('permission', 'supply.plan.manage');
    $router->get('/supply/plans/{id}', [SupplyPlanApiController::class, 'show'])->name('api.supply.plans.show')->middleware('auth')->middleware('permission', 'supply.plan.view');
    $router->put('/supply/plans/{id}', [SupplyPlanApiController::class, 'update'])->name('api.supply.plans.update')->middleware('auth')->middleware('permission', 'supply.plan.manage');
    $router->delete('/supply/plans/{id}', [SupplyPlanApiController::class, 'destroy'])->name('api.supply.plans.destroy')->middleware('auth')->middleware('permission', 'supply.plan.manage');

    // 구매 관리 API
    $router->get('/supply/purchases', [SupplyPurchaseApiController::class, 'index'])->name('api.supply.purchases.index')->middleware('auth')->middleware('permission', 'supply.purchase.view');
    $router->get('/supply/purchases/statistics', [SupplyPurchaseApiController::class, 'getStatistics'])->name('api.supply.purchases.statistics')->middleware('auth')->middleware('permission', 'supply.purchase.view');
    $router->get('/supply/purchases/{id}', [SupplyPurchaseApiController::class, 'show'])->name('api.supply.purchases.show')->middleware('auth')->middleware('permission', 'supply.purchase.view');
    $router->post('/supply/purchases', [SupplyPurchaseApiController::class, 'store'])->name('api.supply.purchases.store')->middleware('auth')->middleware('permission', 'supply.purchase.manage');
    $router->put('/supply/purchases/{id}', [SupplyPurchaseApiController::class, 'update'])->name('api.supply.purchases.update')->middleware('auth')->middleware('permission', 'supply.purchase.manage');
    $router->delete('/supply/purchases/{id}', [SupplyPurchaseApiController::class, 'destroy'])->name('api.supply.purchases.destroy')->middleware('auth')->middleware('permission', 'supply.purchase.manage');
    $router->post('/supply/purchases/{id}/mark-received', [SupplyPurchaseApiController::class, 'markReceived'])->name('api.supply.purchases.mark-received')->middleware('auth')->middleware('permission', 'supply.purchase.manage');
    $router->post('/supply/purchases/bulk-receive', [SupplyPurchaseApiController::class, 'bulkReceive'])->name('api.supply.purchases.bulk-receive')->middleware('auth')->middleware('permission', 'supply.purchase.manage');

    // 지급 관리 API
    $router->get('/supply/distributions', [SupplyDistributionApiController::class, 'index'])->name('api.supply.distributions.index')->middleware('auth')->middleware('permission', 'supply.distribution.view');
    $router->get('/supply/distributions/available-items', [SupplyDistributionApiController::class, 'getAvailableItems'])->name('api.supply.distributions.available-items')->middleware('auth')->middleware('permission', 'supply.distribution.view');
    $router->get('/supply/distributions/{id}', [SupplyDistributionApiController::class, 'show'])->name('api.supply.distributions.show')->middleware('auth')->middleware('permission', 'supply.distribution.view');
    $router->post('/supply/distributions/documents', [SupplyDistributionApiController::class, 'storeDocument'])->name('api.supply.distributions.documents.store')->middleware('auth')->middleware('permission', 'supply.distribution.manage');
    $router->get('/supply/distributions/employees-by-department/{deptId}', [SupplyDistributionApiController::class, 'getEmployeesByDepartment'])->name('api.supply.distributions.employees')->middleware('auth')->middleware('permission', 'supply.distribution.view');
    $router->put('/supply/distributions/documents/{id}', [SupplyDistributionApiController::class, 'updateDocument'])->name('api.supply.distributions.documents.update')->middleware('auth')->middleware('permission', 'supply.distribution.manage');
    $router->delete('/supply/distributions/documents/{id}', [SupplyDistributionApiController::class, 'deleteDocument'])->name('api.supply.distributions.documents.destroy')->middleware('auth')->middleware('permission', 'supply.distribution.manage');
    $router->post('/supply/distributions/documents/{id}/cancel', [SupplyDistributionApiController::class, 'cancelDocument'])->name('api.supply.distributions.documents.cancel')->middleware('auth')->middleware('permission', 'supply.distribution.manage');

    // 재고 현황 API
    $router->get('/supply/stocks', [SupplyStockApiController::class, 'index'])->name('api.supply.stocks.index')->middleware('auth')->middleware('permission', 'supply.stock.view');
    $router->get('/supply/stocks/{id}', [SupplyStockApiController::class, 'show'])->name('api.supply.stocks.show')->middleware('auth')->middleware('permission', 'supply.stock.view');

    // 보고서 API
    $router->get('/supply/reports/distribution', [SupplyReportApiController::class, 'getDistributionReport'])->name('api.supply.reports.distribution')->middleware('auth')->middleware('permission', 'supply.report.view');
    $router->get('/supply/reports/stock', [SupplyReportApiController::class, 'getStockReport'])->name('api.supply.reports.stock')->middleware('auth')->middleware('permission', 'supply.report.view');
    $router->get('/supply/reports/budget/{year}', [SupplyReportApiController::class, 'getBudgetExecutionReport'])->name('api.supply.reports.budget')->middleware('auth')->middleware('permission', 'supply.report.view');
    $router->get('/supply/reports/department/{deptId}/{year}', [SupplyReportApiController::class, 'getDepartmentUsageReport'])->name('api.supply.reports.department')->middleware('auth')->middleware('permission', 'supply.report.view');
    $router->post('/supply/reports/export', [SupplyReportApiController::class, 'exportReport'])->name('api.supply.reports.export')->middleware('auth')->middleware('permission', 'supply.report.view');

    // Vehicle Management API routes
    // Vehicle Works (순서 중요: vehicles/{id} 보다 위에 있어야 함)
    $router->get('/vehicles/works', [VehicleMaintenanceApiController::class, 'index'])->name('api.vehicles.works.index')->middleware('auth');
    $router->post('/vehicles/works', [VehicleMaintenanceApiController::class, 'store'])->name('api.vehicles.works.store')->middleware('auth');
    $router->get('/vehicles/works/{id}', [VehicleMaintenanceApiController::class, 'show'])->name('api.vehicles.works.show')->middleware('auth');
    $router->put('/vehicles/works/{id}', [VehicleMaintenanceApiController::class, 'update'])->name('api.vehicles.works.update')->middleware('auth');
    $router->delete('/vehicles/works/{id}', [VehicleMaintenanceApiController::class, 'destroy'])->name('api.vehicles.works.destroy')->middleware('auth');

    // Vehicle Inspections (순서 중요: vehicles/{id} 보다 위에 있어야 함)
    $router->get('/vehicles/inspections', [VehicleInspectionApiController::class, 'index'])->name('api.vehicles.inspections.index')->middleware('auth')->middleware('permission', 'vehicle.inspection.view');
    $router->post('/vehicles/inspections', [VehicleInspectionApiController::class, 'store'])->name('api.vehicles.inspections.store')->middleware('auth')->middleware('permission', 'vehicle.inspection.manage');
    $router->get('/vehicles/inspections/{id}', [VehicleInspectionApiController::class, 'show'])->name('api.vehicles.inspections.show')->middleware('auth')->middleware('permission', 'vehicle.inspection.view');
    $router->put('/vehicles/inspections/{id}', [VehicleInspectionApiController::class, 'update'])->name('api.vehicles.inspections.update')->middleware('auth')->middleware('permission', 'vehicle.inspection.manage');
    $router->delete('/vehicles/inspections/{id}', [VehicleInspectionApiController::class, 'destroy'])->name('api.vehicles.inspections.destroy')->middleware('auth')->middleware('permission', 'vehicle.inspection.manage');

    // Vehicles
    $router->get('/vehicles', [VehicleApiController::class, 'index'])->name('api.vehicles.index')->middleware('auth')->middleware('permission', 'vehicle.view');
    $router->get('/vehicles/{id}', [VehicleApiController::class, 'show'])->name('api.vehicles.show')->middleware('auth')->middleware('permission', 'vehicle.view');
    $router->post('/vehicles', [VehicleApiController::class, 'store'])->name('api.vehicles.store')->middleware('auth')->middleware('permission', 'vehicle.manage');
    $router->put('/vehicles/{id}', [VehicleApiController::class, 'update'])->name('api.vehicles.update')->middleware('auth')->middleware('permission', 'vehicle.manage');
    $router->delete('/vehicles/{id}', [VehicleApiController::class, 'destroy'])->name('api.vehicles.destroy')->middleware('auth')->middleware('permission', 'vehicle.manage');

    // Vehicle Consumables - Categories
    $router->get('/vehicles/consumables/categories', [VehicleConsumableApiController::class, 'categories'])->name('api.vehicles.consumables.categories')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    $router->get('/vehicles/consumables/categories/tree', [VehicleConsumableApiController::class, 'categoryTree'])->name('api.vehicles.consumables.categories.tree')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    $router->get('/vehicles/consumables/categories/{id}', [VehicleConsumableApiController::class, 'show'])->name('api.vehicles.consumables.categories.show')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    $router->post('/vehicles/consumables/categories', [VehicleConsumableApiController::class, 'store'])->name('api.vehicles.consumables.categories.store')->middleware('auth')->middleware('permission', 'vehicle.consumable.manage');
    $router->put('/vehicles/consumables/categories/{id}', [VehicleConsumableApiController::class, 'update'])->name('api.vehicles.consumables.categories.update')->middleware('auth')->middleware('permission', 'vehicle.consumable.manage');
    $router->delete('/vehicles/consumables/categories/{id}', [VehicleConsumableApiController::class, 'destroy'])->name('api.vehicles.consumables.categories.destroy')->middleware('auth')->middleware('permission', 'vehicle.consumable.manage');
    
    // Vehicle Consumables - Stock
    $router->post('/vehicles/consumables/stock-in', [VehicleConsumableApiController::class, 'stockIn'])->name('api.vehicles.consumables.stock-in')->middleware('auth')->middleware('permission', 'vehicle.consumable.stock');
    $router->post('/vehicles/consumables/adjust-stock', [VehicleConsumableApiController::class, 'adjustStock'])->name('api.vehicles.consumables.adjust-stock')->middleware('auth')->middleware('permission', 'vehicle.consumable.manage');
    $router->get('/vehicles/consumables/categories/{id}/stock-in-history', [VehicleConsumableApiController::class, 'stockInHistory'])->name('api.vehicles.consumables.stock-in-history')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    $router->get('/vehicles/consumables/categories/{id}/stock-by-category', [VehicleConsumableApiController::class, 'stockByCategory'])->name('api.vehicles.consumables.stock-by-category')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    $router->get('/vehicles/consumables/categories/{id}/stock-by-item', [VehicleConsumableApiController::class, 'stockByItem'])->name('api.vehicles.consumables.stock-by-item')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
    
    // Vehicle Consumables - Usage
    $router->post('/vehicles/consumables/use', [VehicleConsumableApiController::class, 'use'])->name('api.vehicles.consumables.use')->middleware('auth')->middleware('permission', 'vehicle.consumable.stock');
    $router->get('/vehicles/consumables/categories/{id}/usage-history', [VehicleConsumableApiController::class, 'usageHistory'])->name('api.vehicles.consumables.usage-history')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');
});
