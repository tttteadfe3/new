<?php

namespace App\Controllers\Web;

use App\Services\SupplyDistributionService;
use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class SupplyDistributionController extends BaseController
{
    private SupplyDistributionService $supplyDistributionService;
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        SupplyDistributionService $supplyDistributionService,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->supplyDistributionService = $supplyDistributionService;
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 지급 관리 목록 페이지를 표시합니다.
     */
    public function index(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-distributions.js');

        echo $this->render('pages/supply/distributions/index', [
            'pageTitle' => '지급 문서 관리'
        ], 'layouts/app');
    }

    /**
     * 지급 수정 폼 페이지를 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/distributions');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-distributions-edit.js');
        
        echo $this->render('pages/supply/distributions/edit', [
            'distributionId' => (int)$id,
            'pageTitle' => '지급품 지급 수정'
        ], 'layouts/app');
    }

    /**
     * 지급 상세 페이지를 표시합니다.
     */
    public function show(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/distributions');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-distributions-show.js');
        
        echo $this->render('pages/supply/distributions/show', [
            'distributionId' => (int)$id,
            'pageTitle' => '지급 상세 정보'
        ], 'layouts/app');
    }
}
