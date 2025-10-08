<?php

namespace App\Controllers;

use App\Services\LeaveService;
use App\Services\EmployeeManager;
use App\Repositories\EmployeeRepository;
use Exception;

class LeaveController extends BaseController
{
    private LeaveService $leaveService;
    private EmployeeManager $employeeManager;

    public function __construct()
    {
        parent::__construct();
        $this->leaveService = new LeaveService();
        $this->employeeManager = new EmployeeManager();
    }

    /**
     * Display leave management index page (redirects to my leaves for regular users)
     */
    public function index(): string
    {
        $this->requireAuth('leave_view');
        
        // Regular users should see their own leaves
        return $this->redirect('/leaves/my');
    }

    /**
     * Display user's own leave requests and status
     */
    public function my(): string
    {
        $this->requireAuth('leave_view');

        $pageTitle = "연차 신청/내역";
        $pageCss = [
            BASE_ASSETS_URL . '/assets/libs/sweetalert2/sweetalert2.min.css'
        ];
        $pageJs = [
            BASE_ASSETS_URL . '/assets/libs/sweetalert2/sweetalert2.min.js',
            BASE_ASSETS_URL . '/assets/js/pages/my_leave.js'
        ];

        return $this->render('pages/leaves/my', compact('pageTitle', 'pageCss', 'pageJs'));
    }

    /**
     * Display leave approval page for administrators
     */
    public function approval(): string
    {
        $this->requireAuth('leave_admin');

        $pageTitle = "연차 신청 승인/반려";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/leave_approval.js'
        ];

        return $this->render('pages/leaves/approval', compact('pageTitle', 'pageJs'));
    }

    /**
     * Display leave granting page for administrators
     */
    public function granting(): string
    {
        $this->requireAuth('leave_admin');

        $pageTitle = "연차 부여/계산";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/leave_granting.js'
        ];

        return $this->render('pages/leaves/granting', compact('pageTitle', 'pageJs'));
    }

    /**
     * Display leave history for administrators
     */
    public function history(): string
    {
        $this->requireAuth('leave_admin');

        $pageTitle = "직원 연차 내역 조회";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/leave_history_admin.js'
        ];

        // Get all employees for the dropdown
        $employees = EmployeeRepository::findAllActive();

        return $this->render('pages/leaves/history', compact('pageTitle', 'pageJs', 'employees'));
    }

}