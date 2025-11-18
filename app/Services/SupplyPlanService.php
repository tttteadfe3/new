<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\SupplyPlanRepository;
use App\Repositories\SupplyItemRepository;
use App\Repositories\SupplyPurchaseRepository;
use App\Models\SupplyPlan;
use App\Services\ActivityLogger;

class SupplyPlanService
{
    private SupplyPlanRepository $planRepository;
    private SupplyItemRepository $itemRepository;
    private SupplyPurchaseRepository $purchaseRepository;
    private SessionManager $sessionManager;
    private ActivityLogger $activityLogger;

    public function __construct(
        SupplyPlanRepository $planRepository,
        SupplyItemRepository $itemRepository,
        SupplyPurchaseRepository $purchaseRepository,
        SessionManager $sessionManager,
        ActivityLogger $activityLogger
    ) {
        $this->planRepository = $planRepository;
        $this->itemRepository = $itemRepository;
        $this->purchaseRepository = $purchaseRepository;
        $this->sessionManager = $sessionManager;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 연간 계획을 생성합니다.
     */
    public function createAnnualPlan(int $year, array $planData): bool
    {
        // 데이터 검증
        $this->validatePlanData($planData);

        // 중복 계획 검사
        if ($this->planRepository->isDuplicatePlan($year, $planData['item_id'])) {
            throw new \InvalidArgumentException('해당 연도에 이미 동일한 품목의 계획이 존재합니다.');
        }

        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($planData['item_id']);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 사용자 정보 추가
        $user = $this->sessionManager->get('user');
        $planData['created_by'] = $user['id'];
        $planData['year'] = $year;

        // 계획 생성
        $planId = $this->planRepository->create($planData);

        // 활동 로그 기록
        $this->activityLogger->log('supply_plan_create', "연간 계획 생성: 품목 ID {$planData['item_id']}", [
            'plan_id' => $planId,
            'year' => $year,
            'item_id' => $planData['item_id'],
            'quantity' => $planData['planned_quantity']
        ]);

        return $planId > 0;
    }

    /**
     * 연간 계획을 수정합니다.
     */
    public function updateAnnualPlan(int $planId, array $planData): bool
    {
        $existingPlan = $this->planRepository->findById($planId);
        if (!$existingPlan) {
            throw new \InvalidArgumentException('존재하지 않는 계획입니다.');
        }

        // 연관된 구매나 지급이 있는지 확인
        if ($this->planRepository->hasAssociatedPurchases($planId) || 
            $this->planRepository->hasAssociatedDistributions($planId)) {
            throw new \InvalidArgumentException('이미 구매나 지급 기록이 있는 계획은 수정할 수 없습니다.');
        }

        // 데이터 검증 (수정 가능한 필드만)
        $allowedFields = ['planned_quantity', 'unit_price', 'notes'];
        $updateData = array_intersect_key($planData, array_flip($allowedFields));
        
        if (empty($updateData)) {
            throw new \InvalidArgumentException('수정할 데이터가 없습니다.');
        }

        // 수량과 단가 검증
        if (isset($updateData['planned_quantity'])) {
            if (!is_numeric($updateData['planned_quantity']) || $updateData['planned_quantity'] <= 0) {
                throw new \InvalidArgumentException('계획 수량은 양수여야 합니다.');
            }
        }

        if (isset($updateData['unit_price'])) {
            if (!is_numeric($updateData['unit_price']) || $updateData['unit_price'] < 0) {
                throw new \InvalidArgumentException('단가는 0 이상이어야 합니다.');
            }
        }

        // 기존 데이터 조회
        $existingPlan = $this->planRepository->findById($planId);
        $oldData = $existingPlan ? $existingPlan->toArray() : [];

        // 계획 수정
        $success = $this->planRepository->update($planId, $updateData);

        if ($success) {
            // 활동 로그 기록
            $newData = array_merge($oldData, $updateData);
            $this->activityLogger->log('supply_plan_update', "연간 계획 수정: ID {$planId}", [
                'plan_id' => $planId,
                'old_data' => $oldData,
                'new_data' => $newData
            ]);
        }

        return $success;
    }

    /**
     * 연간 계획을 삭제합니다.
     */
    public function deleteAnnualPlan(int $planId): bool
    {
        $plan = $this->planRepository->findById($planId);
        if (!$plan) {
            throw new \InvalidArgumentException('존재하지 않는 계획입니다.');
        }

        // 연관된 구매나 지급이 있는지 확인
        if ($this->planRepository->hasAssociatedPurchases($planId) || 
            $this->planRepository->hasAssociatedDistributions($planId)) {
            throw new \InvalidArgumentException('이미 구매나 지급 기록이 있는 계획은 삭제할 수 없습니다.');
        }

        // 계획 삭제
        $success = $this->planRepository->delete($planId);

        if ($success) {
            // 활동 로그 기록
            $this->activityLogger->log('supply_plan_delete', "연간 계획 삭제: ID {$planId}", [
                'plan_id' => $planId,
                'deleted_data' => $plan->toArray()
            ]);
        }

        return $success;
    }

    /**
     * 연도별 계획 목록을 조회합니다.
     */
    public function getAnnualPlans(int $year): array
    {
        return $this->planRepository->findWithItems($year);
    }

    /**
     * 계획 상세 정보를 조회합니다.
     */
    public function getPlanById(int $planId): ?SupplyPlan
    {
        return $this->planRepository->findById($planId);
    }

    /**
     * 엑셀 파일에서 계획을 가져옵니다.
     */
    public function importPlansFromExcel(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('파일이 존재하지 않습니다.');
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // CSV 파일 읽기 (Excel 대신 CSV 사용)
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \InvalidArgumentException('파일을 읽을 수 없습니다.');
        }

        $user = $this->sessionManager->get('user');
        $lineNumber = 0;
        $header = null;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            
            // 첫 번째 줄은 헤더로 처리
            if ($lineNumber === 1) {
                $header = $data;
                continue;
            }

            try {
                // CSV 데이터를 배열로 변환
                $planData = array_combine($header, $data);
                
                // 필수 필드 검증
                $requiredFields = ['year', 'item_code', 'planned_quantity', 'unit_price'];
                foreach ($requiredFields as $field) {
                    if (!isset($planData[$field]) || empty(trim($planData[$field]))) {
                        throw new \InvalidArgumentException("필수 필드 '{$field}'가 누락되었습니다.");
                    }
                }

                // 품목 코드로 품목 ID 찾기
                $item = $this->itemRepository->findByItemCode(trim($planData['item_code']));
                if (!$item) {
                    throw new \InvalidArgumentException("품목 코드 '{$planData['item_code']}'를 찾을 수 없습니다.");
                }

                $year = (int) $planData['year'];
                $itemId = $item->getAttribute('id');

                // 중복 계획 검사
                if ($this->planRepository->isDuplicatePlan($year, $itemId)) {
                    throw new \InvalidArgumentException("연도 {$year}에 이미 동일한 품목의 계획이 존재합니다.");
                }

                // 계획 데이터 준비
                $insertData = [
                    'year' => $year,
                    'item_id' => $itemId,
                    'planned_quantity' => (int) $planData['planned_quantity'],
                    'unit_price' => (float) $planData['unit_price'],
                    'notes' => trim($planData['notes'] ?? ''),
                    'created_by' => $user['id']
                ];

                // 데이터 검증
                $this->validatePlanData($insertData);

                // 계획 생성
                $this->planRepository->create($insertData);
                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "라인 {$lineNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        // 활동 로그 기록
        $this->activityLogger->log('supply_plan_import', "연간 계획 엑셀 가져오기 완료", $results);

        return $results;
    }

    /**
     * 연도별 계획을 엑셀 파일로 내보냅니다.
     */
    public function exportPlansToExcel(int $year): string
    {
        $plans = $this->planRepository->findWithItems($year);
        
        if (empty($plans)) {
            throw new \InvalidArgumentException('내보낼 계획이 없습니다.');
        }

        // 임시 파일 생성
        $filename = "supply_plans_{$year}_" . date('YmdHis') . '.csv';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new \RuntimeException('파일을 생성할 수 없습니다.');
        }

        // UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
        fwrite($handle, "\xEF\xBB\xBF");

        // 헤더 작성
        $headers = [
            '연도', '품목코드', '품목명', '분류', '단위', 
            '계획수량', '단가', '총예산', '비고'
        ];
        fputcsv($handle, $headers);

        // 데이터 작성
        foreach ($plans as $plan) {
            $row = [
                $plan['year'],
                $plan['item_code'],
                $plan['item_name'],
                $plan['category_name'],
                $plan['unit'],
                $plan['planned_quantity'],
                $plan['unit_price'],
                $plan['planned_quantity'] * $plan['unit_price'],
                $plan['notes'] ?? ''
            ];
            fputcsv($handle, $row);
        }

        fclose($handle);

        // 활동 로그 기록
        $this->activityLogger->log('supply_plan_export', "연간 계획 엑셀 내보내기: {$year}년");

        return $filepath;
    }

    /**
     * 연도별 예산 요약을 계산합니다.
     */
    public function calculateBudgetSummary(int $year): array
    {
        $summary = $this->planRepository->getBudgetSummaryByYear($year);
        $plans = $this->planRepository->findWithItems($year);

        // 분류별 요약 계산
        $categoryBudgets = [];
        foreach ($plans as $plan) {
            $categoryName = $plan['category_name'] ?? '미분류';
            if (!isset($categoryBudgets[$categoryName])) {
                $categoryBudgets[$categoryName] = [
                    'category_name' => $categoryName,
                    'item_count' => 0,
                    'total_quantity' => 0,
                    'total_budget' => 0
                ];
            }
            
            $categoryBudgets[$categoryName]['item_count']++;
            $categoryBudgets[$categoryName]['total_quantity'] += $plan['planned_quantity'];
            $categoryBudgets[$categoryName]['total_budget'] += ($plan['planned_quantity'] * $plan['unit_price']);
        }

        return [
            'year' => $year,
            'total_items' => (int) $summary['total_items'],
            'total_quantity' => (int) $summary['total_quantity'],
            'total_budget' => (float) $summary['total_budget'],
            'avg_unit_price' => (float) $summary['avg_unit_price'],
            'category_budgets' => array_values($categoryBudgets)
        ];
    }

    /**
     * 계획 데이터를 검증합니다.
     */
    public function validatePlanData(array $data): array
    {
        $errors = [];

        // 연도 검증
        if (!isset($data['year']) || !is_numeric($data['year'])) {
            $errors['year'] = '연도는 필수이며 숫자여야 합니다.';
        } else {
            $year = (int) $data['year'];
            $currentYear = (int) date('Y');
            if ($year < $currentYear || $year > ($currentYear + 10)) {
                $errors['year'] = '연도는 현재 연도부터 10년 후까지만 가능합니다.';
            }
        }

        // 품목 ID 검증
        if (!isset($data['item_id']) || !is_numeric($data['item_id'])) {
            $errors['item_id'] = '품목 ID는 필수이며 숫자여야 합니다.';
        }

        // 계획 수량 검증
        if (!isset($data['planned_quantity']) || !is_numeric($data['planned_quantity'])) {
            $errors['planned_quantity'] = '계획 수량은 필수이며 숫자여야 합니다.';
        } else {
            $quantity = (int) $data['planned_quantity'];
            if ($quantity <= 0) {
                $errors['planned_quantity'] = '계획 수량은 양수여야 합니다.';
            }
            if ($quantity > 999999) {
                $errors['planned_quantity'] = '계획 수량은 999,999개를 초과할 수 없습니다.';
            }
        }

        // 단가 검증
        if (!isset($data['unit_price']) || !is_numeric($data['unit_price'])) {
            $errors['unit_price'] = '단가는 필수이며 숫자여야 합니다.';
        } else {
            $unitPrice = (float) $data['unit_price'];
            if ($unitPrice < 0) {
                $errors['unit_price'] = '단가는 0 이상이어야 합니다.';
            }
            if ($unitPrice > 9999999.99) {
                $errors['unit_price'] = '단가는 9,999,999.99원을 초과할 수 없습니다.';
            }
        }

        // 생성자 ID 검증
        if (!isset($data['created_by']) || !is_numeric($data['created_by'])) {
            $errors['created_by'] = '생성자 ID는 필수입니다.';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('데이터 검증 실패: ' . implode(', ', $errors));
        }

        return $errors;
    }

    /**
     * 연도별 사용 가능한 품목 목록을 조회합니다.
     */
    public function getAvailableItemsForYear(int $year): array
    {
        // 이미 계획이 있는 품목 제외
        $existingPlans = $this->planRepository->findByYear($year);
        $existingItemIds = array_column($existingPlans, 'item_id');

        $allItems = $this->itemRepository->findActiveItems();
        
        return array_filter($allItems, function($item) use ($existingItemIds) {
            return !in_array($item->getAttribute('id'), $existingItemIds);
        });
    }

    /**
     * 활동 로그를 기록합니다.
     */
    private function logActivity(string $action, string $details): void
    {
        // ActivityLogger는 AuthService를 통해 자동으로 현재 사용자 정보를 가져옵니다
        // 여기서는 간단히 호출만 하면 됩니다
    }
}