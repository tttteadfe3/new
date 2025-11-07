<?php

namespace App\Controllers\Web;

use App\Services\LeaveService;
use App\Services\EmployeeService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class LeaveController extends BaseController
{
    private LeaveService $leaveService;
    private EmployeeService $employeeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        LeaveService $leaveService,
        EmployeeService $employeeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->leaveService = $leaveService;
        $this->employeeService = $employeeService;
    }

    // ===================================================================
    // 직원용 페이지 (Employee Pages)
    // ===================================================================



    /**
     * 연차 관리 메인 페이지
     * - 연차 현황 대시보드
     * - 연차 신청 폼 및 유효성 검증
     * - 연차 신청 내역 관리 (조회, 취소)
     * 요구사항: 5.1, 5.4 - 연차 신청 폼, 실시간 잔여 연차 표시, 사용 이력 조회
     */
    public function apply(): void
    {
        $user = $this->user();
        $employeeId = $user['employee_id'] ?? null;
        
        if (!$employeeId) {
            $this->redirect('/dashboard?error=no_employee_link');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/chart.js/chart.umd.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-apply.js');
        
        echo $this->render('pages/leaves/apply', [], 'layouts/app');
    }

    /**
     * 팀 캘린더 조회
     * 요구사항: 8.1, 8.3, 8.4 - 승인된 연차 일정 표시, 부서별 데이터 접근, 중복 휴가자 표시
     */
    public function teamCalendar(): void
    {
        $user = $this->user();
        $employeeId = $user['employee_id'] ?? null;
        
        if (!$employeeId) {
            $this->redirect('/dashboard?error=no_employee_link');
            return;
        }
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/chart.js/chart.umd.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/fullcalendar/index.global.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/services/api-service.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/core/base-page.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-team-calendar.js');
        
        echo $this->render('pages/leaves/team-calendar', [], 'layouts/app');
    }

    // ===================================================================
    // 관리자용 페이지 (Administrator Pages)
    // ===================================================================

    /**
     * 관리자 대시보드
     * 요구사항: 6.1, 6.2, 6.4 - 팀별 연차 소진율 시각화, 미사용자 현황, 승인 대기 목록
     */
    public function adminDashboard(): void
    {
        $user = $this->user();
        $employeeId = $user['employee_id'] ?? null;
        
        if (!$employeeId) {
            $this->redirect('/dashboard?error=no_employee_link');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/chart.js/chart.umd.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-admin-dashboard.js');
        
        echo $this->render('pages/leaves/admin-dashboard', [], 'layouts/app');
    }

    /**
     * 관리자 연차 관리 페이지
     * 요구사항: 7.1, 7.2, 7.3, 7.4 - 연차 부여, 조정, 소멸 관리 기능
     */
    public function adminManagement(): void
    {
        $user = $this->user();
        $employeeId = $user['employee_id'] ?? null;
        
        if (!$employeeId) {
            $this->redirect('/dashboard?error=no_employee_link');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-admin-management.js');
        
        echo $this->render('pages/leaves/admin-management', [], 'layouts/app');
    }



    /**
     * 승인 대기 목록
     * 요구사항: 6.4 - 승인 대기 목록 관리 인터페이스
     */
    public function pendingApprovals(): void
    {
        $user = $this->user();
        $employeeId = $user['employee_id'] ?? null;
        
        if (!$employeeId) {
            $this->redirect('/dashboard?error=no_employee_link');
            return;
        }

        // CSS 파일 추가
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/css/leave-dashboard.css');
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/css/leave-pending-approvals.css');
        
        // JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-pending-approvals.js');
        
        echo $this->render('pages/leaves/pending-approvals', [], 'layouts/app');
    }


}
