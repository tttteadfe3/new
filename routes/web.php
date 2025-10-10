<?php

use App\Core\Router;
use App\Controllers\Web\AdminController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\EmployeeController;
use App\Controllers\Web\HolidayController;
use App\Controllers\Web\LeaveController;
use App\Controllers\Web\LitteringController;
use App\Controllers\Web\LogController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\StatusController;
use App\Controllers\Web\WasteCollectionController;

// --- 공용 및 인증 ---
Router::get('/', [AuthController::class, 'login'])->name('home');
Router::get('/login', [AuthController::class, 'login'])->name('login');
Router::get('/auth/kakao/callback', [AuthController::class, 'kakaoCallback'])->name('kakao.callback');
Router::get('/logout', [AuthController::class, 'logout'])->name('logout');

// --- 대시보드 및 상태 ---
Router::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Router::get('/status', [StatusController::class, 'index'])->name('status')->middleware('auth');

// --- 직원 관리 ---
Router::get('/employees', [EmployeeController::class, 'index'])->name('employees.index')->middleware('auth')->middleware('permission', 'employee_admin');
Router::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create')->middleware('auth')->middleware('permission', 'employee_admin');
Router::get('/employees/edit', [EmployeeController::class, 'edit'])->name('employees.edit')->middleware('auth')->middleware('permission', 'employee_admin');

// --- 휴일 관리 ---
Router::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index')->middleware('auth')->middleware('permission', 'holiday_admin');

// --- 휴가 관리 ---
Router::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index')->middleware('auth')->middleware('permission', 'leave_view');
Router::get('/leaves/my', [LeaveController::class, 'my'])->name('leaves.my')->middleware('auth');
Router::get('/leaves/approval', [LeaveController::class, 'approval'])->name('leaves.approval')->middleware('auth')->middleware('permission', 'leave_admin');
Router::get('/leaves/granting', [LeaveController::class, 'granting'])->name('leaves.granting')->middleware('auth')->middleware('permission', 'leave_admin');
Router::get('/leaves/history', [LeaveController::class, 'history'])->name('leaves.history')->middleware('auth')->middleware('permission', 'leave_admin');

// --- 무단투기 관리 ---
Router::get('/littering', [LitteringController::class, 'index'])->name('littering.index')->middleware('auth')->middleware('permission', 'littering_manage');
Router::get('/littering/map', [LitteringController::class, 'map'])->name('littering.map')->middleware('auth')->middleware('permission', 'littering_process');
Router::get('/littering/history', [LitteringController::class, 'history'])->name('littering.history')->middleware('auth')->middleware('permission', 'littering_view');
Router::get('/littering/deleted', [LitteringController::class, 'deleted'])->name('littering.deleted')->middleware('auth')->middleware('permission', 'littering_admin');
Router::get('/littering/create', [LitteringController::class, 'create'])->name('littering.create')->middleware('auth')->middleware('permission', 'littering_process');
Router::get('/littering/edit', [LitteringController::class, 'edit'])->name('littering.edit')->middleware('auth')->middleware('permission', 'littering_manage');

// --- 폐기물 수거 ---
Router::get('/waste', [WasteCollectionController::class, 'index'])->name('waste.index')->middleware('auth')->middleware('permission', 'waste_view');
Router::get('/waste/collection', [WasteCollectionController::class, 'collection'])->name('waste.collection')->middleware('auth')->middleware('permission', 'waste_view');
Router::get('/waste/admin', [WasteCollectionController::class, 'admin'])->name('waste.admin')->middleware('auth')->middleware('permission', 'waste_admin');

// --- 관리자 ---
Router::get('/admin/organization', [AdminController::class, 'organization'])->name('admin.organization')->middleware('auth')->middleware('permission', 'organization_admin');
Router::get('/admin/role-permissions', [AdminController::class, 'rolePermissions'])->name('admin.role-permissions')->middleware('auth')->middleware('permission', 'role_admin');
Router::get('/admin/users', [AdminController::class, 'users'])->name('admin.users')->middleware('auth')->middleware('permission', 'user_admin');
Router::get('/admin/menus', [AdminController::class, 'menus'])->name('admin.menus')->middleware('auth')->middleware('permission', 'menu_admin');

// --- 프로필 및 로그 ---
Router::get('/profile', [ProfileController::class, 'index'])->name('profile.index')->middleware('auth');
Router::get('/logs', [LogController::class, 'index'])->name('logs.index')->middleware('auth')->middleware('permission', 'log_admin');

// --- 유틸리티 ---
Router::get('/blank', ['App\Controllers\Web\UtilityController', 'blank'])->name('blank');