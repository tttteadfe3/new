<?php
// app/Controllers/Api/PositionApiController.php
namespace App\Controllers\Api;

use App\Services\PositionService;
use App\Core\JsonResponse;
use App\Core\Request;

class PositionApiController {
    private PositionService $positionService;
    private Request $request;
    private JsonResponse $jsonResponse;

    public function __construct(PositionService $positionService, Request $request, JsonResponse $jsonResponse) {
        $this->positionService = $positionService;
        $this->request = $request;
        $this->jsonResponse = $jsonResponse;
    }

    public function store() {
        $data = $this->request->getBody();
        $result = $this->positionService->createPosition($data);

        if (isset($result['errors'])) {
            return $this->jsonResponse->send(['errors' => $result['errors']], 422);
        }

        return $this->jsonResponse->send(['success' => true, 'id' => $result['id']]);
    }

    public function update(int $id) {
        $data = $this->request->getBody();
        $result = $this->positionService->updatePosition($id, $data);

        if (isset($result['errors'])) {
            return $this->jsonResponse->send(['errors' => $result['errors']], 422);
        }

        if (!$result['success']) {
            return $this->jsonResponse->send(['success' => false, 'message' => 'Update failed'], 400);
        }

        return $this->jsonResponse->send(['success' => true]);
    }

    public function delete(int $id) {
        $success = $this->positionService->deletePosition($id);

        if (!$success) {
            return $this->jsonResponse->send(['success' => false, 'message' => '해당 직급에 소속된 직원이 있어 삭제할 수 없습니다.'], 400);
        }

        return $this->jsonResponse->send(['success' => true]);
    }
}
