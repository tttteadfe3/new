<?php

namespace App\Controllers\Web;

use App\Services\SupplyCategoryService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class SupplyCategoryController extends BaseController
{
    private SupplyCategoryService $supplyCategoryService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        SupplyCategoryService $supplyCategoryService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->supplyCategoryService = $supplyCategoryService;
    }

    /**
     * 지급품 분류 목록 페이지를 표시합니다.
     */
    public function index(): void
    {
        // CSS 및 JavaScript 파일 추가
        View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.css');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/list.js/list.min.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-categories.js');

        echo $this->render('pages/supply/categories/index', [
            'pageTitle' => '지급품 분류 관리'
        ], 'layouts/app');
    }



}