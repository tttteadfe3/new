<?php
namespace App\Controllers\Web;

use App\Services\WasteCollectionService;
use App\Core\Request;
use App\Core\View;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use App\Services\ViewDataService;

class WasteCollectionController extends BaseController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        WasteCollectionService $wasteCollectionService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->wasteCollectionService = $wasteCollectionService;
    }

    /**
     * Display waste collection map page (for users)
     */
    public function index(): void
    {
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering-index.css");

        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-index.js");

        echo $this->render('pages/waste/index', [
            'jsConfig' => ['allowedRegions' => ALLOWED_REGIONS]
        ], 'layouts/app');
    }

    /**
     * Display waste admin page
     */
    public function manage(): void
    {
        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-manage.js");
        
        echo $this->render('pages/waste/manage', [], 'layouts/app');
    }

}
