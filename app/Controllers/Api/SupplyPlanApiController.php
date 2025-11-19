<?php

namespace App\Controllers\Api;

use App\Services\SupplyPlanService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyPlanApiController extends BaseApiController
{
    private SupplyPlanService $supplyPlanService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyPlanService $supplyPlanService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyPlanService = $supplyPlanService;
    }

    /**
     * 연간 계획 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $plans = $this->supplyPlanService->getAnnualPlans($year);
            
            $this->apiSuccess([
                'year' => $year,
                'plans' => $plans,
                'total' => count($plans)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 계획의 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $plan = $this->supplyPlanService->getPlanById($id);
            if (!$plan) {
                $this->apiNotFound('계획을 찾을 수 없습니다.');
                return;
            }

            $planData = $plan->toArray();
            $planData['id'] = $id;

            $this->apiSuccess($planData);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새로운 연간 계획을 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['year', 'item_id', 'planned_quantity', 'unit_price'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 데이터 타입 변환
            $year = (int) $data['year'];
            $user = $this->authService->user();
            if (!$user) {
                $this->apiUnauthorized('로그인이 필요합니다.');
                return;
            }
            $data['created_by'] = $user['id'];
            $data['item_id'] = (int) $data['item_id'];
            $data['planned_quantity'] = (int) $data['planned_quantity'];
            $data['unit_price'] = (float) $data['unit_price'];

            $success = $this->supplyPlanService->createAnnualPlan($year, $data);
            
            if ($success) {
                $this->apiSuccess(null, '연간 계획이 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('연간 계획 생성에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획 정보를 수정합니다.
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
            $allowedFields = ['planned_quantity', 'unit_price', 'notes'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                $this->apiBadRequest('수정 가능한 필드가 없습니다.');
                return;
            }

            // 데이터 타입 변환
            if (isset($updateData['planned_quantity'])) {
                $updateData['planned_quantity'] = (int) $updateData['planned_quantity'];
            }
            if (isset($updateData['unit_price'])) {
                $updateData['unit_price'] = (float) $updateData['unit_price'];
            }

            $success = $this->supplyPlanService->updateAnnualPlan($id, $updateData);
            
            if ($success) {
                $this->apiSuccess(null, '계획이 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('계획 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획을 삭제합니다.
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->supplyPlanService->deleteAnnualPlan($id);
            
            if ($success) {
                $this->apiSuccess(null, '계획이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('계획 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 엑셀 파일에서 계획을 가져옵니다.
     */
    public function importExcel(): void
    {
        try {
            // 파일 업로드 검증
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $this->apiBadRequest('엑셀 파일을 업로드해주세요.');
                return;
            }

            $uploadedFile = $_FILES['excel_file'];
            
            // 파일 확장자 검증
            $allowedExtensions = ['csv', 'xlsx', 'xls'];
            $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $this->apiBadRequest('CSV 또는 Excel 파일만 업로드 가능합니다.');
                return;
            }

            // 임시 파일 경로
            $tempFilePath = $uploadedFile['tmp_name'];
            
            // CSV가 아닌 경우 CSV로 변환 (간단한 구현)
            if ($fileExtension !== 'csv') {
                $this->apiBadRequest('현재 CSV 파일만 지원됩니다. Excel 파일을 CSV로 변환해서 업로드해주세요.');
                return;
            }

            // 엑셀 가져오기 처리
            $results = $this->supplyPlanService->importPlansFromExcel($tempFilePath);
            
            $this->apiSuccess($results, '엑셀 가져오기가 완료되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 연도별 계획을 엑셀 파일로 내보냅니다.
     */
    public function exportExcel(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $filePath = $this->supplyPlanService->exportPlansToExcel($year);
            
            if (!file_exists($filePath)) {
                $this->apiError('파일 생성에 실패했습니다.');
                return;
            }

            // 파일 다운로드 헤더 설정
            $filename = "supply_plans_{$year}_" . date('YmdHis') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            // 파일 출력
            readfile($filePath);
            
            // 임시 파일 삭제
            unlink($filePath);
            
            exit;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 연도별 예산 요약을 조회합니다.
     */
    public function getBudgetSummary(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $budgetSummary = $this->supplyPlanService->calculateBudgetSummary($year);
            
            $this->apiSuccess($budgetSummary);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 연도별 사용 가능한 품목 목록을 조회합니다.
     */
    public function getAvailableItems(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $availableItems = $this->supplyPlanService->getAvailableItemsForYear($year);
            
            $this->apiSuccess([
                'year' => $year,
                'items' => $availableItems,
                'total' => count($availableItems)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획 데이터를 검증합니다.
     */
    public function validate(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['year', 'item_id', 'planned_quantity', 'unit_price'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 데이터 타입 변환
            $data['year'] = (int) $data['year'];
            $data['item_id'] = (int) $data['item_id'];
            $data['planned_quantity'] = (int) $data['planned_quantity'];
            $data['unit_price'] = (float) $data['unit_price'];

            // 서비스 레이어에서 검증
            $errors = $this->supplyPlanService->validatePlanData($data);
            
            if (empty($errors)) {
                $this->apiSuccess([
                    'valid' => true,
                    'message' => '유효한 계획 데이터입니다.'
                ]);
            } else {
                $this->apiBadRequest('데이터 검증 실패: ' . implode(', ', $errors));
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획을 복사합니다.
     */
    public function copyPlans(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['source_year', 'target_year', 'plan_ids'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            $sourceYear = (int) $data['source_year'];
            $targetYear = (int) $data['target_year'];
            $planIds = $data['plan_ids'];

            if (!is_array($planIds) || empty($planIds)) {
                $this->apiBadRequest('복사할 계획을 선택해주세요.');
                return;
            }

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($planIds as $planId) {
                try {
                    $sourcePlan = $this->supplyPlanService->getPlanById((int) $planId);
                    if (!$sourcePlan) {
                        $errors[] = "계획 ID {$planId}: 원본 계획을 찾을 수 없습니다.";
                        $failedCount++;
                        continue;
                    }

                    // 새 계획 데이터 준비
                    $newPlanData = [
                        'item_id' => $sourcePlan->getAttribute('item_id'),
                        'planned_quantity' => $sourcePlan->getAttribute('planned_quantity'),
                        'unit_price' => $sourcePlan->getAttribute('unit_price'),
                        'notes' => $sourcePlan->getAttribute('notes') . ' (복사됨)'
                    ];

                    $this->supplyPlanService->createAnnualPlan($targetYear, $newPlanData);
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "계획 ID {$planId}: " . $e->getMessage();
                    $failedCount++;
                }
            }

            $this->apiSuccess([
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ], "계획 복사가 완료되었습니다. (성공: {$successCount}건, 실패: {$failedCount}건)");
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획 통계 정보를 조회합니다.
     */
    public function getStatistics(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $plans = $this->supplyPlanService->getAnnualPlans($year);
            $budgetSummary = $this->supplyPlanService->calculateBudgetSummary($year);
            
            // 추가 통계 계산
            $statistics = [
                'year' => $year,
                'total_plans' => count($plans),
                'total_budget' => $budgetSummary['total_budget'],
                'total_quantity' => $budgetSummary['total_quantity'],
                'avg_unit_price' => $budgetSummary['avg_unit_price'],
                'category_count' => count($budgetSummary['category_budgets']),
                'highest_budget_item' => null,
                'lowest_budget_item' => null
            ];

            // 최고/최저 예산 품목 찾기
            if (!empty($plans)) {
                $sortedPlans = $plans;
                usort($sortedPlans, function($a, $b) {
                    $budgetA = $a['planned_quantity'] * $a['unit_price'];
                    $budgetB = $b['planned_quantity'] * $b['unit_price'];
                    return $budgetB <=> $budgetA;
                });
                
                $statistics['highest_budget_item'] = [
                    'item_name' => $sortedPlans[0]['item_name'],
                    'budget' => $sortedPlans[0]['planned_quantity'] * $sortedPlans[0]['unit_price']
                ];
                
                $statistics['lowest_budget_item'] = [
                    'item_name' => end($sortedPlans)['item_name'],
                    'budget' => end($sortedPlans)['planned_quantity'] * end($sortedPlans)['unit_price']
                ];
            }

            $this->apiSuccess($statistics);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획 검색을 수행합니다.
     */
    public function search(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $query = $this->request->input('q', '');
            $categoryId = $this->request->input('category_id');
            
            $year = (int) $year;

            if (empty($query) && !$categoryId) {
                $this->apiBadRequest('검색어 또는 분류를 선택해주세요.');
                return;
            }

            $plans = $this->supplyPlanService->getAnnualPlans($year);

            // 검색 필터링
            $filteredPlans = array_filter($plans, function($plan) use ($query, $categoryId) {
                $matchesQuery = empty($query) || 
                    stripos($plan['item_name'], $query) !== false || 
                    stripos($plan['item_code'], $query) !== false ||
                    stripos($plan['category_name'], $query) !== false;
                
                $matchesCategory = !$categoryId || $plan['category_id'] == $categoryId;
                
                return $matchesQuery && $matchesCategory;
            });

            $this->apiSuccess([
                'year' => $year,
                'query' => $query,
                'category_id' => $categoryId,
                'plans' => array_values($filteredPlans),
                'total' => count($filteredPlans)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 예외를 처리합니다.
     */
    protected function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}