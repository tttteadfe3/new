<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class SupplyItemController extends BaseController
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
     * 품목 목록 페이지를 표시합니다.
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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-items-index.js');

        echo $this->render('pages/supply/items/index', [
            'pageTitle' => '지급품 품목 관리'
        ], 'layouts/app');
    }

    /**
     * 새 품목 등록 폼 페이지를 표시합니다.
     */
    public function create(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-items-create.js');

        echo $this->render('pages/supply/items/create', [
            'pageTitle' => '지급품 품목 등록'
        ], 'layouts/app');
    }

    /**
     * 품목 수정 폼 페이지를 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/items');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-items-edit.js');

        echo $this->render('pages/supply/items/edit', [
            'itemId' => (int)$id,
            'pageTitle' => '지급품 품목 수정'
        ], 'layouts/app');
    }

    /**
     * 품목 상세 페이지를 표시합니다.
     */
    public function show(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/items');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-items-show.js');

        echo $this->render('pages/supply/items/show', [
            'itemId' => (int)$id,
            'pageTitle' => '지급품 품목 상세'
        ], 'layouts/app');
    }

    /**
     * 재고 현황 페이지를 표시합니다.
     */
    public function stocks(): void
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
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-stocks.js');

        echo $this->render('pages/supply/stocks/index', [
            'pageTitle' => '재고 현황'
        ], 'layouts/app');
    }
}
