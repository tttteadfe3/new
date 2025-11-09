<?php

namespace App\Controllers\Api;

use App\Services\ItemService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Repositories\LogRepository;

class ItemController extends BaseApiController
{
    private ItemService $itemService;
    private LogRepository $logRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        ItemService $itemService,
        LogRepository $logRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemService = $itemService;
        $this->logRepository = $logRepository;
    }

    /**
     * 특정 분류에 속한 품목 목록을 가져옵니다.
     */
    public function index(): void
    {
        try {
            $categoryId = $this->request->input('category_id');
            if (!$categoryId) {
                $this->apiBadRequest('category_id는 필수입니다.');
                return;
            }
            $items = $this->itemService->getItemsByCategoryId((int)$categoryId);
            $this->apiSuccess($items);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 품목을 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $newItemId = $this->itemService->createItem($data);

            if ($newItemId) {
                $currentUser = $this->authService->user();
                $this->logRepository->insert([
                    'user_id' => $currentUser['id'],
                    'employee_id' => $currentUser['employee_id'],
                    'action' => 'item_create',
                    'details' => json_encode(['id' => $newItemId, 'name' => $data['name']]),
                ]);
                $this->apiSuccess(['id' => $newItemId], '품목이 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('품목 생성에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
