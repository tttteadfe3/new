<?php

use App\Controllers\Web\AdminController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\MyPageController;
use App\Controllers\Web\EmployeeController;
use App\Controllers\Web\HolidayController;
use App\Controllers\Web\HumanResourceController;
use App\Controllers\Web\LeaveController;
use App\Controllers\Web\LitteringController;
use App\Controllers\Web\LogController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\StatusController;
use App\Controllers\Web\WasteCollectionController;
use App\Controllers\Web\SupplyController;
use App\Controllers\Web\SupplyCategoryController;
use App\Controllers\Web\SupplyItemController;
use App\Controllers\Web\SupplyPlanController;
use App\Controllers\Web\SupplyPurchaseController;
use App\Controllers\Web\SupplyDistributionController;
use App\Controllers\Web\SupplyReportController;

// --- 공용 및 인증 ---
$router->get('/', [AuthController::class, 'login'])->name('home');
$router->get('/my-page', [MyPageController::class, 'index'])->name('my-page')->middleware('auth');
$router->get('/login', [AuthController::class, 'login'])->name('login');
$router->get('/auth/kakao/callback', [AuthController::class, 'kakaoCallback'])->name('kakao.callback');
$router->get('/logout', [AuthController::class, 'logout'])->name('logout');

// --- 대시보드 및 상태 ---
$router->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
$router->get('/status', [StatusController::class, 'index'])->name('status')->middleware('auth');

use App\Controllers\Web\OrganizationController;

// --- 직원 관리 ---
$router->get('/employees', [EmployeeController::class, 'index'])->name('employees.index')->middleware('auth')->middleware('permission', 'employee.view');
$router->get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create')->middleware('auth')->middleware('permission', 'employee.create');
$router->get('/employees/edit', [EmployeeController::class, 'edit'])->name('employees.edit')->middleware('auth')->middleware('permission', 'employee.update');

// --- 인사 발령 ---
$router->get('/hr/order/create', [HumanResourceController::class, 'create'])->name('hr.order.create')->middleware('auth')->middleware('permission', 'employee.assign');
$router->get('/hr/history', [HumanResourceController::class, 'history'])->name('hr.history')->middleware('auth')->middleware('permission', 'employee.view_history');

// --- 조직 관리 ---
$router->get('/organization/chart', [OrganizationController::class, 'chart'])->name('organization.chart')->middleware('auth')->middleware('permission', 'organization.view');

// --- 휴일 관리 ---
$router->get('/holidays', [HolidayController::class, 'index'])->name('holidays.index')->middleware('auth')->middleware('permission', 'holiday.manage');

// --- 연차 관리 (Annual Leave Management) ---
// 직원용 페이지 (Employee Pages)
$router->get('/leaves/apply', [LeaveController::class, 'apply'])->name('leaves.apply')->middleware('auth')->middleware('permission', 'leave.view');
$router->get('/leaves/team-calendar', [LeaveController::class, 'teamCalendar'])->name('leaves.team-calendar')->middleware('auth')->middleware('permission', 'leave.view');

// 관리자용 페이지 (Administrator Pages)
$router->get('/leaves/admin-dashboard', [LeaveController::class, 'adminDashboard'])->name('leaves.admin-dashboard')->middleware('auth')->middleware('permission', 'leave.approve');
$router->get('/leaves/admin-management', [LeaveController::class, 'adminManagement'])->name('leaves.admin-management')->middleware('auth')->middleware('permission', 'leave.manage');
$router->get('/leaves/pending-approvals', [LeaveController::class, 'pendingApprovals'])->name('leaves.pending-approvals')->middleware('auth')->middleware('permission', 'leave.approve');

// --- 무단투기 관리 ---
$router->get('/littering/manage', [LitteringController::class, 'manage'])->name('littering.manage')->middleware('auth')->middleware('permission', 'littering.view');
$router->get('/littering/index', [LitteringController::class, 'index'])->name('littering.index')->middleware('auth')->middleware('permission', 'littering.process');
$router->get('/littering/history', [LitteringController::class, 'history'])->name('littering.history')->middleware('auth')->middleware('permission', 'littering.view');
$router->get('/littering/deleted', [LitteringController::class, 'deleted'])->name('littering.deleted')->middleware('auth')->middleware('permission', 'littering.restore');
$router->get('/littering/create', [LitteringController::class, 'create'])->name('littering.create')->middleware('auth')->middleware('permission', 'littering.create');
$router->get('/littering/edit', [LitteringController::class, 'edit'])->name('littering.edit')->middleware('auth')->middleware('permission', 'littering.confirm');

// --- 폐기물 수거 ---
$router->get('/waste/index', [WasteCollectionController::class, 'index'])->name('waste.index')->middleware('auth')->middleware('permission', 'waste.view');
$router->get('/waste/manage', [WasteCollectionController::class, 'manage'])->name('waste.manage')->middleware('auth')->middleware('permission', 'waste.manage');

// --- 관리자 ---
$router->get('/admin/organization', [AdminController::class, 'organization'])->name('admin.organization')->middleware('auth')->middleware('permission', 'organization.manage');
$router->get('/admin/role-permissions', [AdminController::class, 'rolePermissions'])->name('admin.role-permissions')->middleware('auth')->middleware('permission', 'role.assign_permissions');
$router->get('/admin/users', [AdminController::class, 'users'])->name('admin.users')->middleware('auth')->middleware('permission', 'user.view');
$router->get('/admin/menus', [AdminController::class, 'menus'])->name('admin.menus')->middleware('auth')->middleware('permission', 'menu.manage');

// --- 프로필 및 로그 ---
$router->get('/logs', [LogController::class, 'index'])->name('logs.index')->middleware('auth')->middleware('permission', 'log.view');


// --- 지급품 관리 (Supply Management) ---
// 대시보드
$router->get('/supply', [SupplyController::class, 'index'])->name('supply.dashboard')->middleware('auth');

// 분류 관리
$router->get('/supply/categories', [SupplyCategoryController::class, 'index'])->name('supply.categories.index')->middleware('auth')->middleware('permission', 'supply.category.manage');

// 품목 관리
$router->get('/supply/items', [SupplyItemController::class, 'index'])->name('supply.items.index')->middleware('auth')->middleware('permission', 'supply.item.view');
$router->get('/supply/items/create', [SupplyItemController::class, 'create'])->name('supply.items.create')->middleware('auth')->middleware('permission', 'supply.item.manage');
$router->get('/supply/items/edit', [SupplyItemController::class, 'edit'])->name('supply.items.edit')->middleware('auth')->middleware('permission', 'supply.item.manage');
$router->get('/supply/items/show', [SupplyItemController::class, 'show'])->name('supply.items.show')->middleware('auth')->middleware('permission', 'supply.item.view');

// 연간 계획 관리
$router->get('/supply/plans', [SupplyPlanController::class, 'index'])->name('supply.plans.index')->middleware('auth')->middleware('permission', 'supply.plan.view');
$router->get('/supply/plans/import', [SupplyPlanController::class, 'import'])->name('supply.plans.import')->middleware('auth')->middleware('permission', 'supply.plan.manage');
$router->get('/supply/plans/budget-summary', [SupplyPlanController::class, 'budgetSummary'])->name('supply.plans.budget-summary')->middleware('auth')->middleware('permission', 'supply.plan.view');
$router->get('/supply/plans/copy', [SupplyPlanController::class, 'copy'])->name('supply.plans.copy')->middleware('auth')->middleware('permission', 'supply.plan.manage');

// 구매 관리
$router->get('/supply/purchases', [SupplyPurchaseController::class, 'index'])->name('supply.purchases.index')->middleware('auth')->middleware('permission', 'supply.purchase.view');
$router->get('/supply/purchases/receive', [SupplyPurchaseController::class, 'receive'])->name('supply.purchases.receive')->middleware('auth')->middleware('permission', 'supply.purchase.manage');

// 지급 관리
$router->get('/supply/distributions', [SupplyDistributionController::class, 'index'])->name('supply.distributions.index')->middleware('auth')->middleware('permission', 'supply.distribution.view');

// 재고 관리
$router->get('/supply/stocks', [SupplyItemController::class, 'stocks'])->name('supply.stocks')->middleware('auth')->middleware('permission', 'supply.item.view');

// 보고서
$router->get('/supply/reports', [SupplyReportController::class, 'index'])->name('supply.reports.index')->middleware('auth')->middleware('permission', 'supply.report.view');
$router->get('/supply/reports/distribution', [SupplyReportController::class, 'distributionStatus'])->name('supply.reports.distribution')->middleware('auth')->middleware('permission', 'supply.report.view');
$router->get('/supply/reports/stock', [SupplyReportController::class, 'stockStatus'])->name('supply.reports.stock')->middleware('auth')->middleware('permission', 'supply.report.view');
$router->get('/supply/reports/budget', [SupplyReportController::class, 'budgetExecution'])->name('supply.reports.budget')->middleware('auth')->middleware('permission', 'supply.report.view');
$router->get('/supply/reports/department', [SupplyReportController::class, 'departmentUsage'])->name('supply.reports.department')->middleware('auth')->middleware('permission', 'supply.report.view');

// --- 차량 관리 (Vehicle Management) ---
use App\Controllers\Web\VehicleController;
use App\Controllers\Web\VehicleDriverController;
use App\Controllers\Web\VehicleManagerController;
use App\Controllers\Pages\VehicleConsumableController;

// 차량 목록 (공통)
$router->get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index')->middleware('auth')->middleware('permission', 'vehicle.view');

// 운전원 작업 페이지
$router->get('/vehicles/my-work', [VehicleDriverController::class, 'index'])->name('vehicles.my-work')->middleware('auth')->middleware('permission', 'vehicle.work.report');

// Manager 작업 처리 페이지
$router->get('/vehicles/manager/work', [VehicleManagerController::class, 'index'])->name('vehicles.manager.work')->middleware('auth')->middleware('permission', 'vehicle.work.manage');

// 소모품 관리
$router->get('/vehicles/consumables', [VehicleConsumableController::class, 'index'])->name('vehicles.consumables')->middleware('auth')->middleware('permission', 'vehicle.consumable.view');

