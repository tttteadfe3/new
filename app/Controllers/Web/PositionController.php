<?php
// app/Controllers/Web/PositionController.php
namespace App\Controllers\Web;

use App\Services\PositionService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class PositionController extends BaseController {
    private PositionService $positionService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        PositionService $positionService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->positionService = $positionService;
    }

    public function index() {
        $positions = $this->positionService->getAllPositions();
        echo $this->render('pages/admin/positions/index', ['positions' => $positions], 'layouts/app');
    }

    public function create() {
        echo $this->render('pages/admin/positions/create', [], 'layouts/app');
    }

    public function edit(int $id) {
        $position = $this->positionService->getPositionById($id);
        if (!$position) {
            http_response_code(404);
            echo $this->render('errors/404'); // Assuming this view exists
            return;
        }
        echo $this->render('pages/admin/positions/edit', ['position' => $position], 'layouts/app');
    }
}
