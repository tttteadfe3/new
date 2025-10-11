<?php
namespace App\Controllers\Web;

use App\Services\WasteCollectionService;
use App\Core\Request;

class WasteCollectionController extends BaseController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct()
    {
        parent::__construct();
        $this->wasteCollectionService = new WasteCollectionService();
    }

    /**
     * Display waste collection map page (for users)
     */
    public function index(): void
    {
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css");
        \App\Core\View::addCss(BASE_ASSETS_URL . "/assets/css/pages/littering.css");

        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        \App\Core\View::addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . ($_ENV['KAKAO_MAP_API_KEY'] ?? '') . "&libraries=services");

        // Refactored Scripts
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-collection.js");

        $pageTitle = "대형폐기물 수거";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/waste/collection', [
            'pageTitle' => $pageTitle,
            'jsConfig' => ['allowedRegions' => ALLOWED_REGIONS]
        ], 'layouts/app');
    }

    /**
     * Display waste collection map page (alias for index)
     */
    public function collection(): void
    {
        $this->index();
    }

    /**
     * Display waste admin page
     */
    public function admin(): void
    {
        // Refactored Scripts
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-admin.js");
        
        $pageTitle = "대형폐기물 관리";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/waste/admin', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}