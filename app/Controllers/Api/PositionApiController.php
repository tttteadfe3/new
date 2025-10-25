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
        $data = $this->request->all();
        $result = $this->positionService->createPosition($data);

        if (isset($result['errors'])) {
            $this->apiError('Validation failed', 'VALIDATION_ERROR', 422);
        } else {
            $this->apiSuccess(['id' => $result['id']], 'Position created successfully.');
        }
    }

    public function update(int $id) {
        $data = $this->request->all();
        $result = $this->positionService->updatePosition($id, $data);

        if (isset($result['errors'])) {
            $this->apiError('Validation failed', 'VALIDATION_ERROR', 422);
        } elseif (!$result['success']) {
            $this->apiBadRequest('Update failed.');
        } else {
            $this->apiSuccess(null, 'Position updated successfully.');
        }
    }

    public function delete(int $id) {
        $success = $this->positionService->deletePosition($id);

        if ($success) {
            $this->apiSuccess(null, 'Position deleted successfully.');
        } else {
            $this->apiBadRequest('This position cannot be deleted because it is assigned to employees.');
        }
    }
}
