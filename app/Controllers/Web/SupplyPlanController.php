<?php

namespace App\Controllers\Web;

use App\Services\SupplyPlanService;
use App\Services\SupplyCategoryService;
use App\Repositories\SupplyItemRepository;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class SupplyPlanController extends BaseController
{
    private SupplyPlanService $supplyPlanService;
    private SupplyCategoryService $supplyCategoryService;
    private SupplyItemRepository $supplyItemRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        SupplyPlanService $supplyPlanService,
        SupplyCategoryService $supplyCategoryService,
        SupplyItemRepository $supplyItemRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->supplyPlanService = $supplyPlanService;
        $this->supplyCategoryService = $supplyCategoryService;
        $this->supplyItemRepository = $supplyItemRepository;
    }

    /**
     * 연간 지급품 계획 목록 페이지를 표시합니다.
     */
    public function index(): void
    {

        View::getInstance()->addCss('https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css');
        View::getInstance()->addCss('https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css');
        View::getInstance()->addCss('https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css');

        View::getInstance()->addJs('https://code.jquery.com/jquery-3.7.1.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js');
        View::getInstance()->addJs('https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js');

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans-index.js');

        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        echo $this->render('pages/supply/plans/index', [
            'currentYear' => $year,
            'pageTitle' => "연간 지급품 계획 ({$year}년)"
        ], 'layouts/app');
    }

    /**
     * 새 계획 생성 폼 페이지를 표시합니다.
     */
    public function create(): void
    {
        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        // 필요한 데이터 조회
        try {
            $categories = $this->supplyCategoryService->getAllCategories();
            $availableItems = $this->supplyItemRepository->getAvailableItemsForPlan($year);
        } catch (\Exception $e) {
            $categories = [];
            $availableItems = [];
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans-create.js');

        echo $this->render('pages/supply/plans/create', [
            'year' => $year,
            'categories' => $categories,
            'availableItems' => $availableItems,
            'pageTitle' => "연간 지급품 계획 생성 ({$year}년)"
        ], 'layouts/app');
    }

    /**
     * 계획 수정 폼 페이지를 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/plans');
            return;
        }

        try {
            $plan = $this->supplyPlanService->getPlanById((int)$id);
        } catch (\Exception $e) {
            $plan = null;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans-edit.js');

        echo $this->render('pages/supply/plans/edit', [
            'planId' => (int)$id,
            'plan' => $plan,
            'pageTitle' => '연간 지급품 계획 수정'
        ], 'layouts/app');
    }

    /**
     * 엑셀 업로드 페이지를 표시합니다.
     */
    public function import(): void
    {
        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans-import.js');

        try {
            // 샘플 템플릿 데이터 준비
            $sampleData = [
                [
                    'year' => $year,
                    'item_code' => 'SAMPLE001',
                    'planned_quantity' => 100,
                    'unit_price' => 5000,
                    'notes' => '샘플 데이터입니다.'
                ]
            ];

            echo $this->render('pages/supply/plans/import', [
                'year' => $year,
                'sampleData' => $sampleData,
                'pageTitle' => "연간 지급품 계획 엑셀 업로드 ({$year}년)"
            ], 'layouts/app');
        } catch (\Exception $e) {
            echo $this->render('pages/supply/plans/import', [
                'year' => $year,
                'sampleData' => [],
                'pageTitle' => "연간 지급품 계획 엑셀 업로드 ({$year}년)",
                'error' => '엑셀 업로드 페이지를 불러오는 중 오류가 발생했습니다.'
            ], 'layouts/app');
        }
    }

    /**
     * 예산 요약 페이지를 표시합니다.
     */
    public function budgetSummary(): void
    {
        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        // Chart.js 라이브러리 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/chart.js/chart.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-budget-summary.js');

        try {
            // 예산 요약 정보 조회
            $budgetSummary = $this->supplyPlanService->calculateBudgetSummary($year);
            
            // 연도별 비교를 위한 이전 연도 데이터
            $previousYear = $year - 1;
            $previousBudgetSummary = null;
            try {
                $previousBudgetSummary = $this->supplyPlanService->calculateBudgetSummary($previousYear);
            } catch (\Exception $e) {
                // 이전 연도 데이터가 없는 경우 무시
            }

            // 연도 목록 생성
            $currentYear = (int) date('Y');
            $years = [];
            for ($i = $currentYear - 2; $i <= $currentYear + 5; $i++) {
                $years[] = $i;
            }
            
            echo $this->render('pages/supply/plans/budget-summary', [
                'budgetSummary' => $budgetSummary,
                'previousBudgetSummary' => $previousBudgetSummary,
                'currentYear' => $year,
                'previousYear' => $previousYear,
                'years' => $years,
                'pageTitle' => "예산 요약 ({$year}년)"
            ], 'layouts/app');
        } catch (\Exception $e) {
            echo $this->render('pages/supply/plans/budget-summary', [
                'budgetSummary' => [
                    'year' => $year,
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_budget' => 0,
                    'avg_unit_price' => 0,
                    'category_budgets' => []
                ],
                'previousBudgetSummary' => null,
                'currentYear' => $year,
                'previousYear' => $year - 1,
                'years' => [],
                'pageTitle' => "예산 요약 ({$year}년)",
                'error' => '예산 요약 정보를 불러오는 중 오류가 발생했습니다.'
            ], 'layouts/app');
        }
    }

    /**
     * 계획 복사 페이지를 표시합니다.
     */
    public function copy(): void
    {
        $sourceYear = $this->request->input('source_year', date('Y') - 1);
        $targetYear = $this->request->input('target_year', date('Y'));
        
        $sourceYear = (int) $sourceYear;
        $targetYear = (int) $targetYear;

        try {
            // 원본 연도의 계획 목록 조회
            $sourcePlans = $this->supplyPlanService->getAnnualPlans($sourceYear);
            
            // 대상 연도의 기존 계획 목록 조회
            $targetPlans = $this->supplyPlanService->getAnnualPlans($targetYear);
            $existingItemIds = array_column($targetPlans, 'item_id');
            
            // 복사 가능한 계획 필터링
            $copyablePlans = array_filter($sourcePlans, function($plan) use ($existingItemIds) {
                return !in_array($plan['item_id'], $existingItemIds);
            });

            // 연도 목록 생성
            $currentYear = (int) date('Y');
            $years = [];
            for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                $years[] = $i;
            }
            
            echo $this->render('pages/supply/plans/copy', [
                'sourcePlans' => $sourcePlans,
                'copyablePlans' => $copyablePlans,
                'sourceYear' => $sourceYear,
                'targetYear' => $targetYear,
                'years' => $years,
                'pageTitle' => "연간 지급품 계획 복사 ({$sourceYear}년 → {$targetYear}년)"
            ], 'layouts/app');
        } catch (\Exception $e) {
            echo $this->render('pages/supply/plans/copy', [
                'sourcePlans' => [],
                'copyablePlans' => [],
                'sourceYear' => $sourceYear,
                'targetYear' => $targetYear,
                'years' => [],
                'pageTitle' => "연간 지급품 계획 복사 ({$sourceYear}년 → {$targetYear}년)",
                'error' => '계획 복사 페이지를 불러오는 중 오류가 발생했습니다.'
            ], 'layouts/app');
        }
    }
}