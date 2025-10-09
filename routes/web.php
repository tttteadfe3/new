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
use App\Controllers\Web\PageController;
use App\Controllers\Web\AttendanceController;
use App\Controllers\Web\VehicleController;
use App\Controllers\Web\ScheduleController;
use App\Controllers\Web\DispatchController;
use App\Controllers\Web\TripLogController;

// --- 공용 페이지 (인증 불필요) ---
Router::get('/', [PageController::class, 'login'])->name('home');
Router::get('/login', [PageController::class, 'login'])->name('login');
Router::get('/auth/kakao/callback', [PageController::class, 'kakaoCallback'])->name('kakao.callback');
Router::get('/logout', [AuthController::class, 'logout'])->name('logout');

// --- 인증 필요 페이지 (일반 직원) ---
Router::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Router::get('/my-attendance', [AttendanceController::class, 'myAttendance'])->name('my.attendance');
Router::get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('my.leaves');
Router::get('/status', [StatusController::class, 'index'])->name('status');

// --- 관리자 전용 페이지 ---
Router::group('/admin', function() {
    // 기존 관리자 페이지
    Router::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Router::get('/employees', [AdminController::class, 'employees'])->name('admin.employees');
    Router::get('/organization', [AdminController::class, 'organization'])->name('admin.organization');
    Router::get('/role-permissions', [AdminController::class, 'rolePermissions'])->name('admin.role-permissions');
    Router::get('/menus', [AdminController::class, 'menus'])->name('admin.menus');

    // 휴가/휴일 관리
    Router::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Router::group('/leaves', function() {
        Router::get('/', [LeaveController::class, 'index'])->name('leaves.index');
        Router::get('/approval', [LeaveController::class, 'approval'])->name('leaves.approval');
        Router::get('/granting', [LeaveController::class, 'granting'])->name('leaves.granting');
        Router::get('/history', [LeaveController::class, 'history'])->name('leaves.history');
    });

    // 무단투기 관리
    Router::group('/littering', function() {
        Router::get('/', [LitteringController::class, 'index'])->name('littering.index');
        Router::get('/map', [LitteringController::class, 'map'])->name('littering.map');
        Router::get('/history', [LitteringController::class, 'history'])->name('littering.history');
        Router::get('/deleted', [LitteringController::class, 'deleted'])->name('littering.deleted');
        Router::get('/create', [LitteringController::class, 'create'])->name('littering.create');
        Router::get('/edit', [LitteringController::class, 'edit'])->name('littering.edit');
    });

    // 폐기물 수거
    Router::group('/waste', function() {
        Router::get('/', [WasteCollectionController::class, 'index'])->name('waste.index');
        Router::get('/collection', [WasteCollectionController::class, 'collection'])->name('waste.collection');
        Router::get('/admin', [WasteCollectionController::class, 'admin'])->name('waste.admin');
    });

    // 프로필 및 로그
    Router::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Router::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // 차량 운행 관리 페이지
    Router::group('/fleet', function() {
        Router::get('/vehicles', [VehicleController::class, 'index'])->name('admin.fleet.vehicles');
        Router::get('/schedules', [ScheduleController::class, 'index'])->name('admin.fleet.schedules');
        Router::get('/dispatches', [DispatchController::class, 'index'])->name('admin.dispatches.index');
        Router::get('/trip-logs/{id}', [TripLogController::class, 'show'])->name('admin.trips.show');
    });
});