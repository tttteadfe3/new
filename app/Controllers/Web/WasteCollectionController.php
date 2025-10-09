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
        $this->requireAuth('waste_view');
        
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
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/api-service.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map-manager.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/base-app.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-collection-app.js");

        $pageTitle = "대형폐기물 수거";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        echo $this->render('pages/waste/collection', [
            'pageTitle' => $pageTitle
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
        $this->requireAuth('waste_admin');
        
        // Refactored Scripts
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/api-service.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/base-app.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-admin-app.js");
        
        $pageTitle = "대형폐기물 관리";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        echo $this->render('pages/waste/admin', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}