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

    /**
     * 관리자를 위한 휴가 관리 인덱스 페이지를 표시합니다.
     * 일반 사용자는 이제 대시보드로 이동합니다.
     */
    public function index(): void
    {
        // 이 페이지는 관리자를 위한 페이지이며, 일반 사용자는 대시보드에서 이 정보를 확인할 수 있습니다.
        // 관리자의 기본값으로 승인 페이지로 리디렉션할 수 있습니다.
        $this->redirect('/leaves/approval');
    }

    /**
     * 관리자를 위한 휴가 승인 페이지를 표시합니다
     */
    public function approval(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-approval.js');

        echo $this->render('pages/leaves/approval', [], 'layouts/app');
    }

    /**
     * 관리자를 위한 휴가 부여 페이지를 표시합니다
     */
    public function granting(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-granting.js');

        echo $this->render('pages/leaves/granting', [], 'layouts/app');
    }

    /**
     * 관리자를 위한 휴가 내역을 표시합니다
     */
    public function history(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-history-admin.js');

        // 서비스 계층을 통해 드롭다운에 대한 모든 직원을 가져옵니다
        $employees = $this->employeeService->getActiveEmployees();

        echo $this->render('pages/leaves/history', compact('employees'), 'layouts/app');
    }

}
