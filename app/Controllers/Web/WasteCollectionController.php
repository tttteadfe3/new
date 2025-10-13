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
        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/littering.css");

        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js");
        View::getInstance()->addJs("//dapi.kakao.com/v2/maps/sdk.js?appkey=" . KAKAO_MAP_API_KEY . "&libraries=services");

        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/location-utils.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/touch-manager.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/utils/marker-factory.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/components/interactive-map.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/services/map-service.js");

        $scriptOptions = [
            'API_URL' => '/waste-collections',
            'ITEMS' => ['매트리스', '침대틀', '장롱', '쇼파', '의자', '책상', '기타(가구)', '건폐', '소각', '변기', '캐리어', '기타'],
            'FILE' => [
                'MAX_SIZE' => 5 * 1024 * 1024, // 5MB
                'ALLOWED_TYPES' => ['image/jpeg', 'image/png'],
                'COMPRESS' => ['MAX_WIDTH' => 1200, 'MAX_HEIGHT' => 1200, 'QUALITY' => 0.8]
            ],
            'allowedRegions' => defined('ALLOWED_REGIONS') ? ALLOWED_REGIONS : []
        ];

        View::getInstance()->addJs(
            BASE_ASSETS_URL . "/assets/js/pages/waste-collection.js",
            ['options' => $scriptOptions]
        );

        $pageTitle = "대형폐기물 수거";
        $this->activityLogger->logMenuAccess($pageTitle);

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
        // Refactored Scripts
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/waste-admin.js");
        
        $pageTitle = "대형폐기물 관리";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/waste/admin', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}
