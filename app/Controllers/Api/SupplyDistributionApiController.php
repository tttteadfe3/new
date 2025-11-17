<?php

namespace App\Controllers\Api;

use App\Services\SupplyDistributionService;
use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyDistributionApiController extends BaseApiController
{
    private SupplyDistributionService $supplyDistributionService;
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyDistributionService $supplyDistributionService,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyDistributionService = $supplyDistributionService;
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 지급 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $employeeId = $this->request->get('employee_id');
            $departmentId = $this->request->get('department_id');
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            
            // 필터링 조건에 따라 조회
            $filters = [];
            if ($employeeId) {
                $filters['employee_id'] = (int) $employeeId;
            }
            if ($departmentId) {
                $filters['department_id'] = (int) $departmentId;
            }
            if ($startDate && $endDate) {
                $filters['start_date'] = $startDate;
                $filters['end_date'] = $endDate;
            }
            
            $distributions = $this->supplyDistributionService->getDistributions($filters);
            
            $this->apiSuccess([
                'distributions' => $distributions,
                'total' => count($distributions)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 지급의 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $distribution = $this->supplyDistributionService->getDistributionById($id);
            if (!$distribution) {
                $this->apiNotFound('지급을 찾을 수 없습니다.');
                return;
            }

            $this->apiSuccess($distribution->toArray());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새로운 지급을 등록합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['item_id', 'employee_id', 'department_id', 'quantity'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 데이터 타입 변환
            $itemId = (int) $data['item_id'];
            $employeeId = (int) $data['employee_id'];
            $departmentId = (int) $data['department_id'];
            $quantity = (int) $data['quantity'];
            $notes = $data['notes'] ?? null;
            
            // 현재 로그인한 사용자 ID
            $distributedBy = $this->getCurrentUserId();

            $distributionId = $this->supplyDistributionService->distributeToEmployee(
                $itemId,
                $employeeId,
                $departmentId,
                $quantity,
                $distributedBy,
                $notes
            );
            
            $this->apiSuccess([
                'distribution_id' => $distributionId
            ], '지급이 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 정보를 수정합니다.
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data)) {
                $this->apiBadRequest('수정할 데이터가 없습니다.');
                return;
            }

            // 허용된 필드만 필터링
            $allowedFields = ['distribution_date', 'quantity', 'notes'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                $this->apiBadRequest('수정 가능한 필드가 없습니다.');
                return;
            }

            // 데이터 타입 변환
            if (isset($updateData['quantity'])) {
                $updateData['quantity'] = (int) $updateData['quantity'];
            }

            // 현재 로그인한 사용자 ID
            $updatedBy = $this->getCurrentUserId();

            $success = $this->supplyDistributionService->updateDistribution($id, $updateData, $updatedBy);
            
            if ($success) {
                $this->apiSuccess(null, '지급이 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('지급 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급을 취소합니다.
     */
    public function cancel(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            
            if (!isset($data['cancel_reason']) || empty(trim($data['cancel_reason']))) {
                $this->apiBadRequest('취소 사유를 입력해주세요.');
                return;
            }

            $cancelReason = trim($data['cancel_reason']);
            
            // 현재 로그인한 사용자 ID
            $cancelledBy = $this->getCurrentUserId();

            $success = $this->supplyDistributionService->cancelDistribution($id, $cancelledBy, $cancelReason);
            
            if ($success) {
                $this->apiSuccess(null, '지급이 성공적으로 취소되었습니다.');
            } else {
                $this->apiError('지급 취소에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 가능한 품목 목록을 조회합니다.
     */
    public function getAvailableItems(): void
    {
        try {
            $availableItems = $this->supplyDistributionService->getAvailableItems();
            
            $this->apiSuccess([
                'items' => $availableItems,
                'total' => count($availableItems)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 부서별 직원 목록을 조회합니다.
     */
    public function getEmployeesByDepartment(int $departmentId): void
    {
        try {
            $employees = $this->supplyDistributionService->getEmployeesByDepartment($departmentId);
            
            $this->apiSuccess([
                'department_id' => $departmentId,
                'employees' => $employees,
                'total' => count($employees)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 부서 전체 직원에게 지급합니다.
     */
    public function distributeToDepartment(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['item_id', 'department_id', 'employees'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            if (!is_array($data['employees']) || empty($data['employees'])) {
                $this->apiBadRequest('지급할 직원 목록이 필요합니다.');
                return;
            }

            $itemId = (int) $data['item_id'];
            $departmentId = (int) $data['department_id'];
            $employees = $data['employees'];
            $notes = $data['notes'] ?? null;
            
            // 현재 로그인한 사용자 ID
            $distributedBy = $this->getCurrentUserId();

            $results = $this->supplyDistributionService->distributeToDepartment(
                $itemId,
                $departmentId,
                $employees,
                $distributedBy,
                $notes
            );
            
            $this->apiSuccess([
                'results' => $results,
                'total' => count($results)
            ], '부서 지급이 성공적으로 완료되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 통계를 조회합니다.
     */
    public function getStatistics(): void
    {
        try {
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            $itemId = $this->request->get('item_id');
            $departmentId = $this->request->get('department_id');
            
            $filters = [];
            if ($startDate && $endDate) {
                $filters['start_date'] = $startDate;
                $filters['end_date'] = $endDate;
            }
            if ($itemId) {
                $filters['item_id'] = (int) $itemId;
            }
            if ($departmentId) {
                $filters['department_id'] = (int) $departmentId;
            }
            
            $stats = $this->supplyDistributionService->getDistributionStats($filters);
            
            $this->apiSuccess([
                'filters' => $filters,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 부서별 지급 현황을 조회합니다.
     */
    public function getDepartmentStats(): void
    {
        try {
            $departmentId = $this->request->get('department_id');
            $year = $this->request->get('year', date('Y'));
            
            if (!$departmentId) {
                $this->apiBadRequest('부서 ID가 필요합니다.');
                return;
            }

            $stats = $this->supplyDistributionService->getDepartmentDistributionStats(
                (int) $departmentId,
                (int) $year
            );
            
            $this->apiSuccess([
                'department_id' => (int) $departmentId,
                'year' => (int) $year,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 직원별 지급 현황을 조회합니다.
     */
    public function getEmployeeStats(): void
    {
        try {
            $employeeId = $this->request->get('employee_id');
            $year = $this->request->get('year', date('Y'));
            
            if (!$employeeId) {
                $this->apiBadRequest('직원 ID가 필요합니다.');
                return;
            }

            $stats = $this->supplyDistributionService->getEmployeeDistributionStats(
                (int) $employeeId,
                (int) $year
            );
            
            $this->apiSuccess([
                'employee_id' => (int) $employeeId,
                'year' => (int) $year,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 검색을 수행합니다.
     */
    public function search(): void
    {
        try {
            $query = $this->request->get('q', '');
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            
            if (empty($query) && (!$startDate || !$endDate)) {
                $this->apiBadRequest('검색어 또는 날짜 범위를 입력해주세요.');
                return;
            }

            // 날짜 범위로 먼저 필터링
            $filters = [];
            if ($startDate && $endDate) {
                $filters['start_date'] = $startDate;
                $filters['end_date'] = $endDate;
            }
            
            $distributions = $this->supplyDistributionService->getDistributions($filters);

            // 검색어로 추가 필터링
            if (!empty($query)) {
                $distributions = array_filter($distributions, function($distribution) use ($query) {
                    return stripos($distribution['item_name'] ?? '', $query) !== false || 
                           stripos($distribution['item_code'] ?? '', $query) !== false ||
                           stripos($distribution['employee_name'] ?? '', $query) !== false ||
                           stripos($distribution['department_name'] ?? '', $query) !== false;
                });
            }

            $this->apiSuccess([
                'query' => $query,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'distributions' => array_values($distributions),
                'total' => count($distributions)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 재고 확인을 수행합니다.
     */
    public function checkStock(): void
    {
        try {
            $itemId = $this->request->get('item_id');
            $quantity = $this->request->get('quantity');
            
            if (!$itemId || !$quantity) {
                $this->apiBadRequest('품목 ID와 수량이 필요합니다.');
                return;
            }

            $itemId = (int) $itemId;
            $quantity = (int) $quantity;
            
            $currentStock = $this->supplyStockService->getCurrentStock($itemId);
            $hasStock = $this->supplyStockService->hasAvailableStock($itemId, $quantity);
            
            $this->apiSuccess([
                'item_id' => $itemId,
                'requested_quantity' => $quantity,
                'current_stock' => $currentStock,
                'has_available_stock' => $hasStock,
                'message' => $hasStock 
                    ? '지급 가능한 재고가 있습니다.' 
                    : "재고가 부족합니다. (현재 재고: {$currentStock}, 요청 수량: {$quantity})"
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 데이터를 검증합니다.
     */
    public function validate(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['item_id', 'employee_id', 'department_id', 'quantity'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            $itemId = (int) $data['item_id'];
            $quantity = (int) $data['quantity'];

            // 서비스 레이어에서 검증
            $this->supplyDistributionService->validateDistribution($itemId, $quantity);
            
            $this->apiSuccess([
                'valid' => true,
                'message' => '유효한 지급 데이터입니다.'
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 모든 부서 목록을 조회합니다.
     */
    public function getDepartments(): void
    {
        try {
            $departments = $this->supplyDistributionService->getAllDepartments();
            
            $this->apiSuccess([
                'departments' => $departments,
                'total' => count($departments)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 현재 로그인한 사용자 ID를 가져옵니다.
     */
    private function getCurrentUserId(): int
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            throw new \RuntimeException('로그인이 필요합니다.');
        }
        return (int) $user['id'];
    }

    /**
     * 예외를 처리합니다.
     */
    private function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
