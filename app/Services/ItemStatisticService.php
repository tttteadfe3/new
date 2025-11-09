<?php

namespace App\Services;

use App\Repositories\ItemStatisticRepository;

class ItemStatisticService
{
    private ItemStatisticRepository $statisticRepository;

    public function __construct(ItemStatisticRepository $statisticRepository)
    {
        $this->statisticRepository = $statisticRepository;
    }

    /**
     * 지정된 연도에 대한 종합 통계 대시보드 데이터를 가져옵니다.
     * @param int $year
     * @return array
     */
    public function getDashboardStats(int $year): array
    {
        $budgetStats = $this->statisticRepository->getBudgetExecutionStats($year);
        $stockStatus = $this->statisticRepository->getCurrentStockStatus();
        $itemGiveStats = $this->statisticRepository->getItemGiveStatsByYear($year);
        $departmentGiveStats = $this->statisticRepository->getDepartmentGiveStatsByYear($year);

        $executionRate = 0;
        if ($budgetStats['total_budget'] > 0) {
            $executionRate = ($budgetStats['total_executed'] / $budgetStats['total_budget']) * 100;
        }

        return [
            'budget_stats' => [
                'total_budget' => (float)$budgetStats['total_budget'],
                'total_executed' => (float)$budgetStats['total_executed'],
                'execution_rate' => round($executionRate, 2)
            ],
            'stock_status' => $stockStatus,
            'item_give_stats' => $itemGiveStats,
            'department_give_stats' => $this->groupDepartmentStats($departmentGiveStats)
        ];
    }

    /**
     * 부서별 지급 통계를 부서 이름으로 그룹화합니다.
     * @param array $stats
     * @return array
     */
    private function groupDepartmentStats(array $stats): array
    {
        $grouped = [];
        foreach ($stats as $row) {
            $grouped[$row['department_name']][] = [
                'item_name' => $row['item_name'],
                'total_quantity' => $row['total_quantity']
            ];
        }
        return $grouped;
    }
}
