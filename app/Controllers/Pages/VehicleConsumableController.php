<?php

namespace App\Controllers\Pages;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;

class VehicleConsumableController extends BasePageController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository);
    }

    /**
     * 소모품 관리 페이지
     */
    public function index(): void
    {
        $this->renderPage('vehicle/consumables', [
            'title' => '차량 소모품 관리',
            'script' => '/assets/js/pages/vehicle-consumables.js'
        ]);
    }
}
