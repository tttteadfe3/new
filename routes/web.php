<?php

use App\Controllers\Web\AdminController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\MyPageController;
use App\Controllers\Web\EmployeeController;
use App\Controllers\Web\HolidayController;
use App\Controllers\Web\LeaveController;
use App\Controllers\Web\LitteringController;
use App\Controllers\Web\LogController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\StatusController;
use App\Controllers\Web\WasteCollectionController;

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

// --- 조직 관리 ---
$router->get('/organization/chart', [OrganizationController::class, 'chart'])->name('organization.chart')->middleware('auth')->middleware('permission', 'organization.view');

// --- 휴일 관리 ---
$router->get('/holidays', [HolidayController::class, 'index'])->name('holidays.index')->middleware('auth')->middleware('permission', 'holiday.manage');

// --- 휴가 관리 ---
$router->get('/leaves', [LeaveController::class, 'index'])->name('leaves.index')->middleware('auth')->middleware('permission', 'leave.view_all');
$router->get('/leaves/approval', [LeaveController::class, 'approval'])->name('leaves.approval')->middleware('auth')->middleware('permission', 'leave.approve');
$router->get('/leaves/granting', [LeaveController::class, 'granting'])->name('leaves.granting')->middleware('auth')->middleware('permission', 'leave.manage_entitlement');
$router->get('/leaves/history', [LeaveController::class, 'history'])->name('leaves.history')->middleware('auth')->middleware('permission', 'leave.view_all');

// --- 무단투기 관리 ---
$router->get('/littering/manage', [LitteringController::class, 'manage'])->name('littering.manage')->middleware('auth')->middleware('permission', 'littering.view');
$router->get('/littering/index', [LitteringController::class, 'index'])->name('littering.index')->middleware('auth')->middleware('permission', 'littering.process');
$router->get('/littering/history', [LitteringController::class, 'history'])->name('littering.history')->middleware('auth')->middleware('permission', 'littering.view');
$router->get('/littering/deleted', [LitteringController::class, 'deleted'])->name('littering.deleted')->middleware('auth')->middleware('permission', 'littering.restore');
$router->get('/littering/create', [LitteringController::class, 'create'])->name('littering.create')->middleware('auth')->middleware('permission', 'littering.create');
$router->get('/littering/edit', [LitteringController::class, 'edit'])->name('littering.edit')->middleware('auth')->middleware('permission', 'littering.confirm');

// --- 폐기물 수거 ---
$router->get('/waste/index', [WasteCollectionController::class, 'index'])->name('waste.index')->middleware('auth')->middleware('permission', 'waste.view');
$router->get('/waste/manage', [WasteCollectionController::class, 'manage'])->name('waste.manage')->middleware('auth')->middleware('permission', 'waste.manage_online');

// --- 관리자 ---
$router->get('/admin/organization', [AdminController::class, 'organization'])->name('admin.organization')->middleware('auth')->middleware('permission', 'organization.manage');
$router->get('/admin/role-permissions', [AdminController::class, 'rolePermissions'])->name('admin.role-permissions')->middleware('auth')->middleware('permission', 'role.assign_permissions');
$router->get('/admin/users', [AdminController::class, 'users'])->name('admin.users')->middleware('auth')->middleware('permission', 'user.view');
$router->get('/admin/menus', [AdminController::class, 'menus'])->name('admin.menus')->middleware('auth')->middleware('permission', 'menu.manage');

// --- 프로필 및 로그 ---
$router->get('/logs', [LogController::class, 'index'])->name('logs.index')->middleware('auth')->middleware('permission', 'log.view');

// --- 유틸리티 ---
$router->get('/blank', ['App\Controllers\Web\UtilityController', 'blank'])->name('blank');
