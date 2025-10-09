<?php

namespace App\Controllers;

use App\Services\LitteringService;
use Exception;

class LitteringController extends BaseController
{
    private LitteringService $litteringService;

    public function __construct()
    {
        parent::__construct();
        $this->litteringService = new LitteringService();
    }

    /**
     * Display the littering admin page (검토 및 승인)
     */
    public function index(): string
    {
        $this->requireAuth('littering_manage');
        
        $pageTitle = "부적정배출 확인";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/css/pages/split-layout.css");
        
        \App\Core\View::addJs("https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js");
        \App\Core\View::addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=bb4a71438b323ef95ff740374eef24a2&libraries=services");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/ApiService.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/MapManager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/BaseApp.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/littering_admin.js");

        return $this->render('pages/littering/admin', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Display the littering map page (무단투기 신고/처리)
     */
    public function map(): string
    {
        $this->requireAuth('littering_process');
        
        $pageTitle = "부적정배출 등록";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/css/pages/littering.css");
        
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        \App\Core\View::addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=bb4a71438b323ef95ff740374eef24a2&libraries=services");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/ApiService.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/MapManager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/BaseApp.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/littering_map.js");

        return $this->render('pages/littering/map', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Display the littering history page (처리 완료 내역)
     */
    public function history(): string
    {
        $this->requireAuth('littering_view');
        
        $pageTitle = "무단투기 처리 내역";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/css/pages/littering_history.css");
        
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        \App\Core\View::addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=bb4a71438b323ef95ff740374eef24a2&libraries=services");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/ApiService.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/MapManager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/BaseApp.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/littering_history.js");

        return $this->render('pages/littering/history', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Display the deleted littering items page (삭제된 항목)
     */
    public function deleted(): string
    {
        $this->requireAuth('littering_admin');
        
        $pageTitle = "삭제된 부적정배출";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        \App\Core\View::addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=bb4a71438b323ef95ff740374eef24a2&libraries=services");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/ApiService.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/MapManager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/BaseApp.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/littering_deleted_admin.js");

        return $this->render('pages/littering/deleted', compact('pageTitle'), 'layouts/app');
    }

    /**
     * Show create form for new littering report
     */
    public function create(): string
    {
        $this->requireAuth('littering_process');
        
        // This might be handled via AJAX/API, but keeping for completeness
        return $this->render('pages/littering/create', [], 'layouts/app');
    }

    /**
     * Show edit form for littering report
     */
    public function edit(): string
    {
        $this->requireAuth('littering_manage');
        
        $id = $this->request->get('id');
        if (!$id) {
            $this->redirect('/littering');
            return '';
        }
        
        try {
            $littering = $this->litteringService->getLitteringById($id);
            return $this->render('pages/littering/edit', compact('littering'), 'layouts/app');
        } catch (Exception $e) {
            $this->redirect('/littering?error=' . urlencode($e->getMessage()));
            return '';
        }
    }
}