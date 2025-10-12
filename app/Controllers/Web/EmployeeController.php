<?php

namespace App\Controllers\Web;

use App\Services\EmployeeService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class EmployeeController extends BaseController
{
    private EmployeeService $employeeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeService $employeeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->employeeService = $employeeService;
    }

    /**
     * Display the employee list page
     */
    public function index(): void
    {
        $pageTitle = "직원 목록";
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.css');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.js');

        // Load BaseApp and dependencies

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
        
        // Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);
        
        echo $this->render('pages/employees/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * Show the form for creating a new employee
     */
    public function create(): void
    {
        echo $this->render('pages/employees/create', [], 'layouts/app');
    }


    /**
     * Show the form for editing an employee
     */
    public function edit(): void
    {
        $id = $this->request->get('id');
        if (!$id) {
            $this->redirect('/employees');
            return;
        }
        
        $employee = $this->employeeService->getEmployee((int)$id);
        if (!$employee) {
            $this->redirect('/employees');
            return;
        }
        
        echo $this->render('pages/employees/edit', [
            'employee' => $employee
        ], 'layouts/app');
    }


}
