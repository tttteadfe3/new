<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class InventoryController extends BaseController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
    }

    /**
     * 지급품 분류 관리 페이지를 렌더링합니다.
     */
    public function categories(): void
    {
        // 페이지별 JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/inventory-categories.js');

        // 뷰 렌더링 (레이아웃 포함)
        echo $this->render('pages/inventory/categories', [
            'pageTitle' => '지급품 분류 관리'
        ], 'layouts/app');
    }

    /**
     * 지급품 계획 관리 페이지를 렌더링합니다.
     */
    public function plans(): void
    {
        // 페이지별 JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/inventory-plans.js');

        // 뷰 렌더링 (레이아웃 포함)
        echo $this->render('pages/inventory/plans', [
            'pageTitle' => '지급품 계획 관리'
        ], 'layouts/app');
    }

    /**
     * 지급품 구입 관리 페이지를 렌더링합니다.
     */
    public function purchases(): void
    {
        // 페이지별 JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/inventory-purchases.js');

        // 뷰 렌더링 (레이아웃 포함)
        echo $this->render('pages/inventory/purchases', [
            'pageTitle' => '지급품 구입 관리'
        ], 'layouts/app');
    }

    /**
     * 지급품 지급 관리 페이지를 렌더링합니다.
     */
    public function gives(): void
    {
        // 페이지별 JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/inventory-gives.js');

        // 뷰 렌더링 (레이아웃 포함)
        echo $this->render('pages/inventory/gives', [
            'pageTitle' => '지급 관리'
        ], 'layouts/app');
    }

    /**
     * 통계 및 현황 페이지를 렌더링합니다.
     */
    public function statistics(): void
    {
        // Chart.js 라이브러리 추가
        View::getInstance()->addJs('https://cdn.jsdelivr.net/npm/chart.js');
        // 페이지별 JavaScript 파일 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/inventory-statistics.js');

        // 뷰 렌더링 (레이아웃 포함)
        echo $this->render('pages/inventory/statistics', [
            'pageTitle' => '통계 및 현황'
        ], 'layouts/app');
    }
}
