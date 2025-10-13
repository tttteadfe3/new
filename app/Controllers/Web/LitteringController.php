<?php

namespace App\Controllers\Web;

use App\Services\LitteringService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class LitteringController extends BaseController
{
    private LitteringService $litteringService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        LitteringService $litteringService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->litteringService = $litteringService;
    }

    /**
     * Display the littering admin page (검토 및 승인)
     */
    public function index(): void
    {
        $pageTitle = "부적정배출 확인";
        $this->activityLogger->logMenuAccess($pageTitle);

        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/split-layout.css");
        
        View::getInstance()->addJs("https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . ($_ENV['KAKAO_MAP_API_KEY'] ?? '') . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-admin.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/admin', ['pageTitle' => $pageTitle], 'layouts/app');
    }

    /**
     * Display the littering map page (무단투기 신고/처리)
     */
    public function map(): void
    {
        $pageTitle = "부적정배출 등록";
        $this->activityLogger->logMenuAccess($pageTitle);

        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering.css");
        
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . ($_ENV['KAKAO_MAP_API_KEY'] ?? '') . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-map.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/map', ['pageTitle' => $pageTitle], 'layouts/app');
    }

    /**
     * Display the littering history page (처리 완료 내역)
     */
    public function history(): void
    {
        $pageTitle = "무단투기 처리 내역";
        $this->activityLogger->logMenuAccess($pageTitle);

        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering_history.css");
        
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . ($_ENV['KAKAO_MAP_API_KEY'] ?? '') . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-history.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/history', ['pageTitle' => $pageTitle], 'layouts/app');
    }

    /**
     * Display the deleted littering items page (삭제된 항목)
     */
    public function deleted(): void
    {
        $pageTitle = "삭제된 부적정배출";
        $this->activityLogger->logMenuAccess($pageTitle);

        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . ($_ENV['KAKAO_MAP_API_KEY'] ?? '') . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-deleted-admin.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/deleted', ['pageTitle' => $pageTitle], 'layouts/app');
    }

    /**
     * Show create form for new littering report
     */
    public function create(): void
    {
        // This might be handled via AJAX/API, but keeping for completeness
        echo $this->render('pages/littering/create', [], 'layouts/app');
    }

    /**
     * Show edit form for littering report
     */
    public function edit(): void
    {
        $id = $this->request->get('id');
        if (!$id) {
            $this->redirect('/littering');
            return;
        }
        
        try {
            $littering = $this->litteringService->getLitteringById($id);
            echo $this->render('pages/littering/edit', compact('littering'), 'layouts/app');
        } catch (Exception $e) {
            $this->redirect('/littering?error=' . urlencode($e->getMessage()));
            return;
        }
    }
}
