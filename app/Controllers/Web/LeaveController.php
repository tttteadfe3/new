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
     * Display leave management index page (redirects to my leaves for regular users)
     */
    public function index(): void
    {
        // Regular users should see their own leaves
        $this->redirect('/leaves/my');
    }

    /**
     * Display user's own leave requests and status
     */
    public function my(): void
    {
        $pageTitle = "연차 신청/내역";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/my-leave.js');

        // Check permission in the controller, not in the view.
        $can_request_leave = $this->authService->check('leave.request');

        echo $this->render('pages/leaves/my', compact('pageTitle', 'can_request_leave'), 'layouts/app');
    }

    /**
     * Display leave approval page for administrators
     */
    public function approval(): void
    {
        $pageTitle = "연차 신청 승인/반려";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-approval.js');

        echo $this->render('pages/leaves/approval', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Display leave granting page for administrators
     */
    public function granting(): void
    {
        $pageTitle = "연차 부여/계산";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-granting.js');

        echo $this->render('pages/leaves/granting', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Display leave history for administrators
     */
    public function history(): void
    {
        $pageTitle = "직원 연차 내역 조회";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-history-admin.js');

        // Get all employees for the dropdown via the service layer
        $employees = $this->employeeService->getActiveEmployees();

        echo $this->render('pages/leaves/history', compact('pageTitle', 'employees'), 'layouts/app');
    }

}
