<?php

namespace App\Controllers\Api;

use App\Services\SupplyReportService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyReportApiController extends BaseApiController
{
    private SupplyReportService $supplyReportService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyReportService $supplyReportService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyReportService = $supplyReportService;
    }

    /**
     * 지급 현황 보고서 데이터를 조회합니다.
     */
    public function getDistributionReport(): void
    {
        try {
            // 필터 설정
            $filters = [
                'year' => $this->request->get('year'),
                'start_date' => $this->request->get('start_date'),
                'end_date' => $this->request->get('end_date'),
                'category_id' => $this->request->get('category_id'),
                'item_id' => $this->request->get('item_id'),
                'department_id' => $this->request->get('department_id'),
                'is_cancelled' => $this->request->get('is_cancelled')
            ];

            // 빈 필터 제거
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $distributions = $this->supplyReportService->getDistributionReport($filters);
            
            $this->apiSuccess([
                'filters' => $filters,
                'distributions' => $distributions,
                'total' => count($distributions)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 재고 현황 보고서 데이터를 조회합니다.
     */
    public function getStockReport(): void
    {
        try {
            // 필터 설정
            $filters = [
                'category_id' => $this->request->get('category_id'),
                'item_id' => $this->request->get('item_id'),
                'low_stock' => $this->request->get('low_stock'),
                'out_of_stock' => $this->request->get('out_of_stock'),
                'threshold' => $this->request->get('threshold', 10)
            ];

            // 빈 필터 제거
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $stocks = $this->supplyReportService->getStockReport($filters);
            
            // 재고 통계 계산
            $totalItems = count($stocks);
            $lowStockItems = array_filter($stocks, function($stock) use ($filters) {
                $threshold = $filters['threshold'] ?? 10;
                return $stock['current_stock'] <= $threshold && $stock['current_stock'] > 0;
            });
            $outOfStockItems = array_filter($stocks, function($stock) {
                return $stock['current_stock'] == 0;
            });

            $this->apiSuccess([
                'filters' => $filters,
                'stocks' => $stocks,
                'statistics' => [
                    'total_items' => $totalItems,
                    'low_stock_count' => count($lowStockItems),
                    'out_of_stock_count' => count($outOfStockItems),
                    'normal_stock_count' => $totalItems - count($lowStockItems) - count($outOfStockItems)
                ]
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 예산 집행률 보고서 데이터를 조회합니다.
     */
    public function getBudgetExecutionReport(): void
    {
        try {
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;
            
            $budgetData = $this->supplyReportService->getBudgetExecutionReport($year);
            
            // 전체 요약 계산
            $totalPlannedBudget = array_sum(array_column($budgetData, 'planned_budget'));
            $totalPurchasedAmount = array_sum(array_column($budgetData, 'purchased_amount'));
            $totalPlannedQuantity = array_sum(array_column($budgetData, 'planned_quantity'));
            $totalPurchasedQuantity = array_sum(array_column($budgetData, 'purchased_quantity'));
            $totalDistributedQuantity = array_sum(array_column($budgetData, 'distributed_quantity'));
            
            $overallExecutionRate = $totalPlannedBudget > 0 
                ? round(($totalPurchasedAmount / $totalPlannedBudget) * 100, 2) 
                : 0;
            
            $this->apiSuccess([
                'year' => $year,
                'budget_data' => $budgetData,
                'summary' => [
                    'total_planned_budget' => $totalPlannedBudget,
                    'total_purchased_amount' => $totalPurchasedAmount,
                    'total_planned_quantity' => $totalPlannedQuantity,
                    'total_purchased_quantity' => $totalPurchasedQuantity,
                    'total_distributed_quantity' => $totalDistributedQuantity,
                    'overall_execution_rate' => $overallExecutionRate,
                    'remaining_budget' => $totalPlannedBudget - $totalPurchasedAmount
                ]
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 부서별 사용 현황 보고서 데이터를 조회합니다.
     */
    public function getDepartmentUsageReport(): void
    {
        try {
            $departmentId = $this->request->get('department_id');
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;

            if (!$departmentId) {
                // 부서 ID가 없으면 전체 부서 요약 반환
                $departmentSummary = $this->supplyReportService->getDepartmentUsageSummary($year);
                
                $this->apiSuccess([
                    'year' => $year,
                    'department_summary' => $departmentSummary,
                    'total_departments' => count($departmentSummary)
                ]);
            } else {
                // 특정 부서의 상세 데이터 반환
                $departmentDetail = $this->supplyReportService->getDepartmentUsageReport((int)$departmentId, $year);
                
                $this->apiSuccess([
                    'year' => $year,
                    'department_id' => $departmentId,
                    'department_detail' => $departmentDetail,
                    'total_items' => count($departmentDetail)
                ]);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목별 지급 현황 요약을 조회합니다.
     */
    public function getItemDistributionSummary(): void
    {
        try {
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;
            
            $itemSummary = $this->supplyReportService->getItemDistributionSummary($year);
            
            $this->apiSuccess([
                'year' => $year,
                'item_summary' => $itemSummary,
                'total_items' => count($itemSummary)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 보고서를 엑셀 파일로 내보냅니다.
     */
    public function exportReport(): void
    {
        try {
            $reportType = $this->request->get('report_type');
            
            if (!$reportType) {
                $this->apiBadRequest('보고서 유형을 선택해주세요.');
                return;
            }

            $filePath = null;

            switch ($reportType) {
                case 'distribution':
                    $filters = [
                        'year' => $this->request->get('year'),
                        'start_date' => $this->request->get('start_date'),
                        'end_date' => $this->request->get('end_date'),
                        'category_id' => $this->request->get('category_id'),
                        'department_id' => $this->request->get('department_id'),
                        'is_cancelled' => $this->request->get('is_cancelled')
                    ];
                    $filters = array_filter($filters, function($value) {
                        return $value !== null && $value !== '';
                    });
                    $filePath = $this->supplyReportService->exportDistributionReport($filters);
                    break;

                case 'stock':
                    $filters = [
                        'category_id' => $this->request->get('category_id'),
                        'low_stock' => $this->request->get('low_stock'),
                        'out_of_stock' => $this->request->get('out_of_stock'),
                        'threshold' => $this->request->get('threshold', 10)
                    ];
                    $filters = array_filter($filters, function($value) {
                        return $value !== null && $value !== '';
                    });
                    $filePath = $this->supplyReportService->exportStockReport($filters);
                    break;

                case 'budget':
                    $year = $this->request->get('year', date('Y'));
                    $filePath = $this->supplyReportService->exportBudgetExecutionReport((int)$year);
                    break;

                case 'department':
                    $departmentId = $this->request->get('department_id');
                    $year = $this->request->get('year', date('Y'));
                    
                    if (!$departmentId) {
                        $this->apiBadRequest('부서를 선택해주세요.');
                        return;
                    }
                    
                    $filePath = $this->supplyReportService->exportDepartmentUsageReport((int)$departmentId, (int)$year);
                    break;

                default:
                    $this->apiBadRequest('지원하지 않는 보고서 유형입니다.');
                    return;
            }

            if (!$filePath || !file_exists($filePath)) {
                $this->apiError('파일 생성에 실패했습니다.');
                return;
            }

            // 파일 다운로드 헤더 설정
            $filename = basename($filePath);
            
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
     * 대시보드 통계 데이터를 조회합니다.
     */
    public function getDashboardStats(): void
    {
        try {
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;

            // 지급 현황 통계
            $distributionFilters = ['year' => $year, 'is_cancelled' => 0];
            $distributions = $this->supplyReportService->getDistributionReport($distributionFilters);
            
            // 재고 현황 통계
            $stocks = $this->supplyReportService->getStockReport([]);
            $lowStockItems = array_filter($stocks, function($stock) {
                return $stock['current_stock'] <= 10 && $stock['current_stock'] > 0;
            });
            $outOfStockItems = array_filter($stocks, function($stock) {
                return $stock['current_stock'] == 0;
            });

            // 예산 집행률 통계
            $budgetData = $this->supplyReportService->getBudgetExecutionReport($year);
            $totalPlannedBudget = array_sum(array_column($budgetData, 'planned_budget'));
            $totalPurchasedAmount = array_sum(array_column($budgetData, 'purchased_amount'));
            $overallExecutionRate = $totalPlannedBudget > 0 
                ? round(($totalPurchasedAmount / $totalPlannedBudget) * 100, 2) 
                : 0;

            // 부서별 사용 현황
            $departmentSummary = $this->supplyReportService->getDepartmentUsageSummary($year);

            $this->apiSuccess([
                'year' => $year,
                'distribution_stats' => [
                    'total_distributions' => count($distributions),
                    'total_quantity' => array_sum(array_column($distributions, 'quantity')),
                    'unique_items' => count(array_unique(array_column($distributions, 'item_id'))),
                    'unique_departments' => count(array_unique(array_column($distributions, 'department_id')))
                ],
                'stock_stats' => [
                    'total_items' => count($stocks),
                    'low_stock_count' => count($lowStockItems),
                    'out_of_stock_count' => count($outOfStockItems),
                    'total_stock_value' => array_sum(array_column($stocks, 'current_stock'))
                ],
                'budget_stats' => [
                    'total_planned_budget' => $totalPlannedBudget,
                    'total_purchased_amount' => $totalPurchasedAmount,
                    'execution_rate' => $overallExecutionRate,
                    'remaining_budget' => $totalPlannedBudget - $totalPurchasedAmount
                ],
                'department_stats' => [
                    'total_departments' => count($departmentSummary),
                    'active_departments' => count(array_filter($departmentSummary, function($dept) {
                        return $dept['distribution_count'] > 0;
                    }))
                ]
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 월별 지급 추이 데이터를 조회합니다.
     */
    public function getMonthlyDistributionTrend(): void
    {
        try {
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;

            $distributions = $this->supplyReportService->getDistributionReport([
                'year' => $year,
                'is_cancelled' => 0
            ]);

            // 월별 집계
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData[$month] = [
                    'month' => $month,
                    'distribution_count' => 0,
                    'total_quantity' => 0,
                    'unique_items' => []
                ];
            }

            foreach ($distributions as $distribution) {
                $month = (int) date('n', strtotime($distribution['distribution_date']));
                $monthlyData[$month]['distribution_count']++;
                $monthlyData[$month]['total_quantity'] += $distribution['quantity'];
                $monthlyData[$month]['unique_items'][] = $distribution['item_id'];
            }

            // unique_items를 카운트로 변환
            foreach ($monthlyData as &$data) {
                $data['unique_items'] = count(array_unique($data['unique_items']));
            }

            $this->apiSuccess([
                'year' => $year,
                'monthly_trend' => array_values($monthlyData)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류별 통계 데이터를 조회합니다.
     */
    public function getCategoryStats(): void
    {
        try {
            $year = $this->request->get('year', date('Y'));
            $year = (int) $year;

            $distributions = $this->supplyReportService->getDistributionReport([
                'year' => $year,
                'is_cancelled' => 0
            ]);

            // 분류별 집계
            $categoryStats = [];
            foreach ($distributions as $distribution) {
                $categoryName = $distribution['category_name'] ?? '미분류';
                
                if (!isset($categoryStats[$categoryName])) {
                    $categoryStats[$categoryName] = [
                        'category_name' => $categoryName,
                        'distribution_count' => 0,
                        'total_quantity' => 0,
                        'unique_items' => []
                    ];
                }
                
                $categoryStats[$categoryName]['distribution_count']++;
                $categoryStats[$categoryName]['total_quantity'] += $distribution['quantity'];
                $categoryStats[$categoryName]['unique_items'][] = $distribution['item_id'];
            }

            // unique_items를 카운트로 변환
            foreach ($categoryStats as &$stats) {
                $stats['unique_items'] = count(array_unique($stats['unique_items']));
            }

            $this->apiSuccess([
                'year' => $year,
                'category_stats' => array_values($categoryStats)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
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
