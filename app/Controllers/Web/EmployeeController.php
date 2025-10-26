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
     * 직원 목록 페이지를 표시합니다
     */
    public function index(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.css');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.js');

        // BaseApp 및 종속성 로드
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
        
        echo $this->render('pages/employees/index', [], 'layouts/app');
    }

    /**
     * 새 직원을 생성하는 양식을 표시합니다
     */
    public function create(): void
    {
        echo $this->render('pages/employees/create', [], 'layouts/app');
    }


    /**
     * 직원 편집 양식을 표시합니다
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
