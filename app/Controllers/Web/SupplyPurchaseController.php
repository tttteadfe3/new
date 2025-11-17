<?php

namespace App\Controllers\Web;

use App\Services\SupplyPurchaseService;
use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class SupplyPurchaseController extends BaseController
{
    private SupplyPurchaseService $supplyPurchaseService;
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        SupplyPurchaseService $supplyPurchaseService,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->supplyPurchaseService = $supplyPurchaseService;
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 구매 관리 목록 페이지를 표시합니다.
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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-purchases-index.js');

        echo $this->render('pages/supply/purchases/index', [
            'pageTitle' => '지급품 구매 관리'
        ], 'layouts/app');
    }

    /**
     * 새 구매 등록 폼 페이지를 표시합니다.
     */
    public function create(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-purchases-create.js');
        
        echo $this->render('pages/supply/purchases/create', [
            'pageTitle' => '지급품 구매 등록'
        ], 'layouts/app');
    }

    /**
     * 구매 수정 폼 페이지를 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/purchases');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-purchases-edit.js');
        
        echo $this->render('pages/supply/purchases/edit', [
            'purchaseId' => (int)$id,
            'pageTitle' => '지급품 구매 수정'
        ], 'layouts/app');
    }

    /**
     * 입고 처리 페이지를 표시합니다.
     */
    public function receive(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-purchases-receive.js');
        
        echo $this->render('pages/supply/purchases/receive', [
            'pageTitle' => '입고 처리'
        ], 'layouts/app');
    }

    /**
     * 구매 상세 페이지를 표시합니다.
     */
    public function show(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/purchases');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-purchases-show.js');
        
        echo $this->render('pages/supply/purchases/show', [
            'purchaseId' => (int)$id,
            'pageTitle' => '구매 상세 정보'
        ], 'layouts/app');
    }
}
