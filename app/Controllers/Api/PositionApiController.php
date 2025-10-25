<?php
// app/Controllers/Api/PositionApiController.php
namespace App\Controllers\Api;

use App\Services\PositionService;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;

class PositionApiController extends BaseApiController {
    private PositionService $positionService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        PositionService $positionService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->positionService = $positionService;
    }

    public function store() {
        $data = $this->getJsonInput();
        $result = $this->positionService->createPosition($data);

        if (isset($result['errors'])) {
            $this->apiError('Validation failed', 'VALIDATION_ERROR', $result['errors']);
        } else {
            $this->apiSuccess(['id' => $result['id']], '직급이 생성되었습니다.');
        }
    }

    public function update(int $id) {
        $data = $this->getJsonInput();
        $result = $this->positionService->updatePosition($id, $data);

        if (isset($result['errors'])) {
            $this->apiError('Validation failed', 'VALIDATION_ERROR', $result['errors']);
        } elseif (!$result['success']) {
            $this->apiBadRequest($result['message'] ?? '직급 수정에 실패했습니다.');
        } else {
            $this->apiSuccess(null, '직급 정보가 수정되었습니다.');
        }
    }

    public function delete(int $id) {
        $success = $this->positionService->deletePosition($id);

        if ($success) {
            $this->apiSuccess(null, '직급이 삭제되었습니다.');
        } else {
            $this->apiBadRequest('직원이 할당된 직급은 삭제할 수 없습니다.');
        }
    }
}
