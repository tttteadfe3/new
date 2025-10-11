<?php

namespace App\Controllers\Web;

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
    public function index(): void
    {
        $pageTitle = "직원 목록";
        \App\Core\\App\Core\View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.css');
        \App\Core\\App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.js');

        // Load BaseApp and dependencies

        \App\Core\\App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
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