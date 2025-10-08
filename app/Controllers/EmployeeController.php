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
        log_menu_access($pageTitle);
        
        return $this->render('pages/employees/index', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create(): string
    {
        $this->requireAuth('employee_admin');
        
        return $this->render('pages/employees/create');
    }

    /**
     * Store a newly created employee
     */
    public function store(): void
    {
        $this->requireAuth('employee_admin');
        
        try {
            $data = $this->request->all();
            $employeeId = $this->employeeService->createEmployee($data);
            
            if ($employeeId) {
                $this->json([
                    'success' => true,
                    'message' => '직원이 성공적으로 등록되었습니다.',
                    'data' => ['id' => $employeeId]
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => '직원 등록에 실패했습니다.',
                    'errors' => ['general' => '데이터 저장 중 오류가 발생했습니다.']
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => '입력 데이터가 올바르지 않습니다.',
                'errors' => ['validation' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => '직원 등록 중 오류가 발생했습니다.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500);
        }
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
        ]);
    }

    /**
     * Update an existing employee
     */
    public function update(): void
    {
        $this->requireAuth('employee_admin');
        
        try {
            $data = $this->request->all();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->json([
                    'success' => false,
                    'message' => '직원 ID가 필요합니다.',
                    'errors' => ['id' => '유효한 직원 ID를 제공해주세요.']
                ], 400);
                return;
            }
            
            $employeeId = $this->employeeService->updateEmployee((int)$id, $data);
            
            if ($employeeId) {
                $this->json([
                    'success' => true,
                    'message' => '직원 정보가 성공적으로 수정되었습니다.',
                    'data' => ['id' => $employeeId]
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => '직원 정보 수정에 실패했습니다.',
                    'errors' => ['general' => '데이터 저장 중 오류가 발생했습니다.']
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => '입력 데이터가 올바르지 않습니다.',
                'errors' => ['validation' => $e->getMessage()]
            ], 422);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => '직원 정보 수정 중 오류가 발생했습니다.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Delete an employee
     */
    public function delete(): void
    {
        $this->requireAuth('employee_admin');
        
        try {
            $id = $this->request->get('id');
            if (!$id) {
                $this->json([
                    'success' => false,
                    'message' => '직원 ID가 필요합니다.',
                    'errors' => ['id' => '유효한 직원 ID를 제공해주세요.']
                ], 400);
                return;
            }
            
            $success = $this->employeeService->deleteEmployee((int)$id);
            
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => '직원이 성공적으로 삭제되었습니다.'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => '직원 삭제에 실패했습니다.',
                    'errors' => ['general' => '데이터 삭제 중 오류가 발생했습니다.']
                ], 500);
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => '직원 삭제 중 오류가 발생했습니다.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500);
        }
    }
}