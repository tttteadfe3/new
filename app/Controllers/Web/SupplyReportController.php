<?php

namespace App\Controllers\Web;

use App\Services\SupplyReportService;
use App\Services\SupplyCategoryService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;
use App\Core\Database;

class SupplyReportController extends BaseController
{
    private SupplyReportService $supplyReportService;
    private SupplyCategoryService $supplyCategoryService;
    private Database $db;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        SupplyReportService $supplyReportService,
        SupplyCategoryService $supplyCategoryService,
        Database $db
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->supplyReportService = $supplyReportService;
        $this->supplyCategoryService = $supplyCategoryService;
        $this->db = $db;
    }

    /**
     * 보고서 메인 페이지를 표시합니다.
     */
    public function index(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-reports.js');

        echo $this->render('pages/supply/reports/index', [
            'pageTitle' => '지급품 보고서'
        ], 'layouts/app');
    }

    /**
     * 지급 현황 보고서 페이지를 표시합니다.
     */
    public function distributionStatus(): void
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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-report-distribution.js');

        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        echo $this->render('pages/supply/reports/distribution', [
            'currentYear' => $year,
            'pageTitle' => "지급 현황 보고서 ({$year}년)"
        ], 'layouts/app');
    }

    /**
     * 재고 현황 보고서 페이지를 표시합니다.
     */
    public function stockStatus(): void
    {
        // CSS 및 JavaScript 파일 추가
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css');
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/datatables.net/js/jquery.dataTables.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-report-stock.js');

        echo $this->render('pages/supply/reports/stock', [
            'pageTitle' => '재고 현황 보고서'
        ], 'layouts/app');
    }

    /**
     * 예산 집행률 보고서 페이지를 표시합니다.
     */
    public function budgetExecution(): void
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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-report-budget.js');

        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        echo $this->render('pages/supply/reports/budget', [
            'currentYear' => $year,
            'pageTitle' => "예산 집행률 보고서 ({$year}년)"
        ], 'layouts/app');
    }

    /**
     * 부서별 사용 현황 보고서 페이지를 표시합니다.
     */
    public function departmentUsage(): void
    {
        // CSS 및 JavaScript 파일 추가

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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-report-department.js');

        $year = $this->request->input('year', date('Y'));
        $year = (int) $year;

        echo $this->render('pages/supply/reports/department', [
            'currentYear' => $year,
            'pageTitle' => "부서별 사용 현황 보고서 ({$year}년)"
        ], 'layouts/app');
    }

    /**
     * 모든 부서 목록을 조회합니다.
     */
    private function getAllDepartments(): array
    {
        $sql = "SELECT id, name, code FROM hr_departments WHERE is_active = 1 ORDER BY name";
        return $this->db->query($sql);
    }

    /**
     * 부서 정보를 조회합니다.
     */
    private function getDepartmentById(int $id): ?array
    {
        $sql = "SELECT id, name, code FROM hr_departments WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
}
