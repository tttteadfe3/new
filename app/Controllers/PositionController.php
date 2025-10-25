<?php
// app/Controllers/PositionController.php
namespace App\Controllers;

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

    public function store() {
        $data = [
            'name' => $_POST['name'],
            'level' => $_POST['level']
        ];

        $result = $this->positionService->createPosition($data);

        if (isset($result['errors'])) {
            View::render('pages/admin/positions/create', ['errors' => $result['errors'], 'data' => $data]);
        } else {
            header('Location: /admin/positions');
            exit();
        }
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

    public function update(int $id) {
        $data = [
            'name' => $_POST['name'],
            'level' => $_POST['level']
        ];

        $result = $this->positionService->updatePosition($id, $data);

        if (isset($result['errors'])) {
            $data['id'] = $id;
            View::render('pages/admin/positions/edit', ['errors' => $result['errors'], 'position' => $data]);
        } else {
            header('Location: /admin/positions');
            exit();
        }
    }

    public function delete(int $id) {
        $success = $this->positionService->deletePosition($id);
        if ($success) {
            $_SESSION['flash_success'] = '직급이 성공적으로 삭제되었습니다.';
        } else {
            $_SESSION['flash_error'] = '해당 직급에 소속된 직원이 있어 삭제할 수 없습니다.';
        }
        header('Location: /admin/positions');
        exit();
    }
}
