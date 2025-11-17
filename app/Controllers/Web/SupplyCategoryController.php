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

    /**
     * 새 분류 생성 폼 페이지를 표시합니다.
     */
    public function create(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-categories-create.js');
        
        echo $this->render('pages/supply/categories/create', [
            'pageTitle' => '지급품 분류 생성'
        ], 'layouts/app');
    }

    /**
     * 분류 수정 폼 페이지를 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/categories');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-categories-edit.js');
        
        echo $this->render('pages/supply/categories/edit', [
            'categoryId' => (int)$id,
            'pageTitle' => '지급품 분류 수정'
        ], 'layouts/app');
    }

    /**
     * 분류 상세 정보 페이지를 표시합니다.
     */
    public function show(): void
    {
        $id = $this->request->input('id');
        if (!$id) {
            $this->redirect('/supply/categories');
            return;
        }

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-categories-show.js');
        
        echo $this->render('pages/supply/categories/show', [
            'categoryId' => (int)$id,
            'pageTitle' => '지급품 분류 상세'
        ], 'layouts/app');
    }

    /**
     * 분류 코드 자동 생성 페이지를 표시합니다.
     */
    public function generateCode(): void
    {
        $level = $this->request->input('level', 1);
        $parentId = $this->request->input('parent_id');

        try {
            $generatedCode = $this->supplyCategoryService->generateCategoryCode(
                (int)$level, 
                $parentId ? (int)$parentId : null
            );

            // JSON 응답으로 처리 (AJAX 요청용)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'code' => $generatedCode
            ]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 분류 통계 페이지를 표시합니다.
     */
    public function statistics(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/supply-categories.js');
        
        echo $this->render('pages/supply/categories/statistics', [
            'pageTitle' => '지급품 분류 통계'
        ], 'layouts/app');
    }
}