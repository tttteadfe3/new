<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class VehicleConsumableController extends BaseController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
    }

    /**
     * 소모품 관리 페이지
     */
    public function index(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/vehicle-consumables.js');
        echo $this->render('pages/vehicle/consumables', [
            'pageTitle' => '차량 소모품 관리'
        ], 'layouts/app');
    }
}
