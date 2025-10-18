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
     * Display leave management index page for administrators.
     * Regular users are now directed to the dashboard.
     */
    public function index(): void
    {
        // This page is for admins; regular users have this info on their dashboard.
        // We can redirect to the approval page as a default for admins.
        $this->redirect('/leaves/approval');
    }

    /**
     * Display leave approval page for administrators
     */
    public function approval(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-approval.js');

        echo $this->render('pages/leaves/approval', [], 'layouts/app');
    }

    /**
     * Display leave granting page for administrators
     */
    public function granting(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-granting.js');

        echo $this->render('pages/leaves/granting', [], 'layouts/app');
    }

    /**
     * Display leave history for administrators
     */
    public function history(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/leave-history-admin.js');

        // Get all employees for the dropdown via the service layer
        $employees = $this->employeeService->getActiveEmployees();

        echo $this->render('pages/leaves/history', compact('employees'), 'layouts/app');
    }

}
