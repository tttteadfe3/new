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

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/services/api-service.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/core/base-page.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans.js');

        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        echo $this->render('pages/supply/plans/index', [
            'currentYear' => $year,
            'pageTitle' => "연간 지급품 계획 ({$year}년)"
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

        echo $this->render('pages/supply/plans/import', [
            'year' => $year,
            'pageTitle' => "연간 지급품 계획 엑셀 업로드 ({$year}년)"
        ], 'layouts/app');
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

        echo $this->render('pages/supply/plans/budget-summary', [
            'currentYear' => $year,
            'pageTitle' => "예산 요약 ({$year}년)"
        ], 'layouts/app');
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

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-plans-copy.js');

        echo $this->render('pages/supply/plans/copy', [
            'sourceYear' => $sourceYear,
            'targetYear' => $targetYear,
            'pageTitle' => "연간 지급품 계획 복사 ({$sourceYear}년 → {$targetYear}년)"
        ], 'layouts/app');
    }
}