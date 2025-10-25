<?php
// app/Controllers/Web/PositionController.php
namespace App\Controllers\Web;

use App\Services\PositionService;
use App\Core\View;
use App\Core\Auth;

class PositionController {
    private PositionService $positionService;

    public function __construct(PositionService $positionService) {
        // Auth is handled by middleware
        $this->positionService = $positionService;
    }

    public function index() {
        $positions = $this->positionService->getAllPositions();
        View::render('pages/admin/positions/index', ['positions' => $positions]);
    }

    public function create() {
        View::render('pages/admin/positions/create');
    }

    public function edit(int $id) {
        $position = $this->positionService->getPositionById($id);
        if (!$position) {
            http_response_code(404);
            View::render('errors/404'); // Assuming this view exists
            return;
        }
        View::render('pages/admin/positions/edit', ['position' => $position]);
    }
}
