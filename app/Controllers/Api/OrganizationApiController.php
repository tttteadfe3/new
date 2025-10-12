<?php

namespace App\Controllers\Api;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class OrganizationApiController extends BaseApiController
{
    protected DepartmentRepository $departmentRepository;
    protected PositionRepository $positionRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
    }

    /**
     * Get a list of departments or positions.
     * GET /api/organization?type=department
     */
    public function index(): void
    {
        try {
            $type = $_GET['type'] ?? '';
            $repository = $this->getRepositoryForType($type);

            $entities = $repository->getAll();
            $this->apiSuccess($entities);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Store a new department or position.
     * POST /api/organization
     */
    public function store(): void
    {
        try {
            $input = $this->getJsonInput();
            $type = $input['type'] ?? '';
            $name = trim($input['name'] ?? '');

            $repository = $this->getRepositoryForType($type);
            $entityName = $type === 'department' ? '부서' : '직급';

            if (empty($name)) {
                $this->apiBadRequest($entityName . ' 이름은 필수입니다.');
            }

            $newId = $repository->create($name);
            $this->apiSuccess(['new_id' => $newId], '새 ' . $entityName . '(이)가 생성되었습니다.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing department or position.
     * PUT /api/organization/{id}
     */
    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $type = $input['type'] ?? '';
            $name = trim($input['name'] ?? '');

            $repository = $this->getRepositoryForType($type);
            $entityName = $type === 'department' ? '부서' : '직급';

            if (empty($name)) {
                $this->apiBadRequest($entityName . ' 이름은 필수입니다.');
            }

            $repository->update($id, $name);
            $this->apiSuccess(null, $entityName . ' 정보가 수정되었습니다.');

        } catch (Exception $e) {
            $this.handleException($e);
        }
    }

    /**
     * Delete a department or position.
     * DELETE /api/organization/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $type = $input['type'] ?? '';

            $repository = $this->getRepositoryForType($type);
            $entityName = $type === 'department' ? '부서' : '직급';

            if ($repository->delete($id)) {
                $this->apiSuccess(null, $entityName . '(이)가 삭제되었습니다.');
            } else {
                $this->apiError('직원이 할당된 ' . $entityName . '은(는) 삭제할 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get the corresponding repository instance based on the entity type.
     * @return DepartmentRepository|PositionRepository
     * @throws Exception
     */
    private function getRepositoryForType(string $type)
    {
        if ($type === 'department') {
            return $this->departmentRepository;
        } elseif ($type === 'position') {
            return $this->positionRepository;
        }
        throw new Exception('Invalid entity type specified.');
    }
}
