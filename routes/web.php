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
Router::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Router::get('/status', [StatusController::class, 'index'])->name('status');

// --- 직원 관리 ---
Router::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
Router::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
Router::get('/employees/edit', [EmployeeController::class, 'edit'])->name('employees.edit');

// --- 휴일 관리 ---
Router::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');

// --- 휴가 관리 ---
Router::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
Router::get('/leaves/my', [LeaveController::class, 'my'])->name('leaves.my');
Router::get('/leaves/approval', [LeaveController::class, 'approval'])->name('leaves.approval');
Router::get('/leaves/granting', [LeaveController::class, 'granting'])->name('leaves.granting');
Router::get('/leaves/history', [LeaveController::class, 'history'])->name('leaves.history');

// --- 무단투기 관리 ---
Router::get('/littering', [LitteringController::class, 'index'])->name('littering.index');
Router::get('/littering/map', [LitteringController::class, 'map'])->name('littering.map');
Router::get('/littering/history', [LitteringController::class, 'history'])->name('littering.history');
Router::get('/littering/deleted', [LitteringController::class, 'deleted'])->name('littering.deleted');
Router::get('/littering/create', [LitteringController::class, 'create'])->name('littering.create');
Router::get('/littering/edit', [LitteringController::class, 'edit'])->name('littering.edit');

// --- 폐기물 수거 ---
Router::get('/waste', [WasteCollectionController::class, 'index'])->name('waste.index');
Router::get('/waste/collection', [WasteCollectionController::class, 'collection'])->name('waste.collection');
Router::get('/waste/admin', [WasteCollectionController::class, 'admin'])->name('waste.admin');

// --- 관리자 ---
Router::get('/admin/organization', [AdminController::class, 'organization'])->name('admin.organization');
Router::get('/admin/role-permissions', [AdminController::class, 'rolePermissions'])->name('admin.role-permissions');
Router::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
Router::get('/admin/menus', [AdminController::class, 'menus'])->name('admin.menus');

// --- 프로필 및 로그 ---
Router::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Router::get('/logs', [LogController::class, 'index'])->name('logs.index');

// --- Test-Login ---
Router::get('/test-login', [TestLoginController::class, 'login'])->name('test-login');