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
     * 무단투기 관리 페이지(검토 및 승인)를 표시합니다.
     */
    public function manage(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/split-layout.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-common.css");
        
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // 리팩토링된 스크립트
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-manage.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/manage', [], 'layouts/app');
    }

    /**
     * 무단투기 지도 페이지(무단투기 신고/처리)를 표시합니다.
     */
    public function index(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-index.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-common.css");
        
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // 리팩토링된 스크립트
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-index.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/index', [], 'layouts/app');
    }

    /**
     * 무단투기 내역 페이지(처리 완료 내역)를 표시합니다.
     */
    public function history(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering_history.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-common.css");
        
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // 리팩토링된 스크립트
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-history.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/history', [], 'layouts/app');
    }

    /**
     * 삭제된 무단투기 항목 페이지(삭제된 항목)를 표시합니다.
     */
    public function deleted(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/split-layout.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-common.css");

        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // 리팩토링된 스크립트
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/littering-deleted-admin.js", ['allowedRegions' => ALLOWED_REGIONS]);

        echo $this->render('pages/littering/deleted', [], 'layouts/app');
    }

    /**
     * 새 무단투기 신고를 위한 생성 양식을 표시합니다.
     */
    public function create(): void
    {
        // 이것은 AJAX/API를 통해 처리될 수 있지만 완전성을 위해 유지합니다.
        echo $this->render('pages/littering/create', [], 'layouts/app');
    }

    /**
     * 무단투기 신고를 위한 편집 양식을 표시합니다.
     */
    public function edit(): void
    {
        $id = $this->request->get('id');
        if (!$id) {
            $this->redirect('/littering/manage');
            return;
        }
        
        try {
            $littering = $this->litteringService->getLitteringById($id);
            echo $this->render('pages/littering/edit', compact('littering'), 'layouts/app');
        } catch (Exception $e) {
            $this->redirect('/littering/manage?error=' . urlencode($e->getMessage()));
            return;
        }
    }
}
