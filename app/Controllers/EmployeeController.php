<?php

namespace App\Controllers;

use App\Services\EmployeeService;
use App\Repositories\EmployeeRepository;
use App\Core\SessionManager;

class EmployeeController extends BaseController
{
    private EmployeeService $employeeService;

    public function __construct()
    {
        parent::__construct();
        $this->employeeService = new EmployeeService();
    }

    /**
     * Display the employee list page
     */
    public function index(): string
    {
        $this->requireAuth('employee_admin');
        
        $pageTitle = "직원 목록";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/employees.js'
        ];
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        return $this->render('pages/employees/index', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ], 'layouts/app');
    }

    /**
     * Show the form for creating a new employee
     */
    public function create(): string
    {
        $this->requireAuth('employee_admin');
        
        return $this->render('pages/employees/create', [], 'layouts/app');
    }


    /**
     * Show the form for editing an employee
     */
    public function edit(): string
    {
        $this->requireAuth('employee_admin');
        
        $id = $this->request->get('id');
        if (!$id) {
            $this->redirect('/employees');
            return '';
        }
        
        $employee = $this->employeeService->getEmployee((int)$id);
        if (!$employee) {
            $this->redirect('/employees');
            return '';
        }
        
        return $this->render('pages/employees/edit', [
            'employee' => $employee
        ], 'layouts/app');
    }


}