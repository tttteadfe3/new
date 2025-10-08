<?php
namespace App\Controllers;

use App\Services\WasteCollectionService;
use App\Core\Request;

class WasteCollectionController extends BaseController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct()
    {
        $this->wasteCollectionService = new WasteCollectionService();
    }

    /**
     * Display waste collection map page (for users)
     */
    public function index(): string
    {
        $this->requireAuth('waste_view');
        
        $pageCss = [
            BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.css",
            BASE_ASSETS_URL . "/assets/libs/glightbox/css/glightbox.min.css",
            BASE_ASSETS_URL . "/assets/css/pages/littering.css"
        ];

        $pageJs = [
            BASE_ASSETS_URL . "/assets/libs/swiper/swiper-bundle.min.js",
            BASE_ASSETS_URL . "/assets/libs/glightbox/js/glightbox.min.js",
            "//dapi.kakao.com/v2/maps/sdk.js?appkey=bb4a71438b323ef95ff740374eef24a2&libraries=services",
            BASE_ASSETS_URL . "/assets/js/services/ApiService.js",
            BASE_ASSETS_URL . "/assets/js/components/MapManager.js",
            BASE_ASSETS_URL . "/assets/js/components/BaseApp.js",
            BASE_ASSETS_URL . "/assets/js/pages/waste_collection.js"
        ];

        $pageTitle = "대형폐기물 수거";
        log_menu_access($pageTitle);

        return $this->render('pages/waste/collection', [
            'pageCss' => $pageCss,
            'pageJs' => $pageJs,
            'pageTitle' => $pageTitle
        ]);
    }

    /**
     * Display waste collection map page (alias for index)
     */
    public function collection(): string
    {
        return $this->index();
    }

    /**
     * Display waste admin page
     */
    public function admin(): string
    {
        $this->requireAuth('waste_admin');
        
        $pageCss = [];
        $pageJs = [
            BASE_ASSETS_URL . "/assets/js/services/ApiService.js",
            BASE_ASSETS_URL . "/assets/js/pages/waste_admin.js"
        ];
        
        $pageTitle = "대형폐기물 관리";
        log_menu_access($pageTitle);

        return $this->render('pages/waste/admin', [
            'pageCss' => $pageCss,
            'pageJs' => $pageJs,
            'pageTitle' => $pageTitle
        ]);
    }

    /**
     * Show create form (if needed for future use)
     */
    public function create(): string
    {
        $this->requireAuth('waste_view');
        
        return $this->render('pages/waste/create');
    }

    /**
     * Store new waste collection (handled via API)
     */
    public function store(): void
    {
        $this->requireAuth('waste_view');
        
        // This would typically redirect to API or handle form submission
        $this->redirect('/waste');
    }

    /**
     * Show edit form (if needed for future use)
     */
    public function edit(): string
    {
        $this->requireAuth('waste_admin');
        
        $id = Request::get('id');
        if (!$id) {
            $this->redirect('/waste');
            return '';
        }

        $collection = $this->wasteCollectionService->getCollectionById((int)$id);
        if (!$collection) {
            $this->redirect('/waste');
            return '';
        }

        return $this->render('pages/waste/edit', [
            'collection' => $collection
        ]);
    }

    /**
     * Update waste collection (handled via API)
     */
    public function update(): void
    {
        $this->requireAuth('waste_admin');
        
        // This would typically redirect to API or handle form submission
        $this->redirect('/waste');
    }

    /**
     * Delete waste collection (handled via API)
     */
    public function delete(): void
    {
        $this->requireAuth('waste_admin');
        
        // This would typically redirect to API or handle deletion
        $this->redirect('/waste');
    }
}