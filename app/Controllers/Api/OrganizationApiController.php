<?php

namespace App\Controllers\Api;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;

class OrganizationApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle all organization API requests based on action and type parameters
     */
    public function index(): void
    {
        $action = $this->getAction();
        $type = $_GET['type'] ?? '';
        
        $repository = null;
        $entityName = '';
        
        if ($type === 'department') {
            $repository = DepartmentRepository::class;
            $entityName = '부서';
        } elseif ($type === 'position') {
            $repository = PositionRepository::class;
            $entityName = '직급';
        } else {
            $this->apiBadRequest('Invalid entity type specified.');
            return;
        }
        
        try {
            switch ($action) {
                case 'list':
                    $this->listEntities($repository);
                    break;
                case 'save':
                    $this->saveEntity($repository, $entityName);
                    break;
                case 'delete':
                    $this->deleteEntity($repository, $entityName);
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * List all entities (departments or positions)
     */
    private function listEntities(string $repository): void
    {
        $entities = $repository::getAll();
        $this->apiSuccess($entities);
    }

    /**
     * Save entity (create or update)
     */
    private function saveEntity(string $repository, string $entityName): void
    {
        $this->requireAuth('organization_admin');
        
        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        
        if (empty($name)) {
            $this->apiBadRequest($entityName . ' 이름은 필수입니다.');
            return;
        }
        
        if ($id > 0) { // 수정
            $repository::update($id, $name);
            $this->apiSuccess(null, $entityName . ' 정보가 수정되었습니다.');
        } else { // 생성
            $newId = $repository::create($name);
            $this->apiSuccess(['new_id' => $newId], '새 ' . $entityName . '(이)가 생성되었습니다.');
        }
    }

    /**
     * Delete entity
     */
    private function deleteEntity(string $repository, string $entityName): void
    {
        $this->requireAuth('organization_admin');
        
        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);
        
        if (!$id) {
            $this->apiBadRequest('ID is required');
            return;
        }
        
        if ($repository::delete($id)) {
            $this->apiSuccess(null, $entityName . '(이)가 삭제되었습니다.');
        } else {
            $this->apiError('직원이 할당된 ' . $entityName . '은(는) 삭제할 수 없습니다.');
        }
    }
}