<?php

namespace App\Controllers\Api;

use App\Services\ItemGiveService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Repositories\LogRepository;

class ItemGiveController extends BaseApiController
{
    private ItemGiveService $itemGiveService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        ItemGiveService $itemGiveService,
        LogRepository $logRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemGiveService = $itemGiveService;
        $this->logRepository = $logRepository;
    }

    /**
     * 지급 내역 목록을 가져옵니다.
     */
    public function index(): void
    {
        try {
            $filters = $this->request->all();
            $gives = $this->itemGiveService->getGives($filters);
            $this->apiSuccess($gives);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 가능한 (재고가 있는) 품목 목록을 가져옵니다.
     */
    public function getAvailableItems(): void
    {
        try {
            $items = $this->itemGiveService->getAvailableItems();
            $this->apiSuccess($items);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 지급 내역을 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $newGiveId = $this->itemGiveService->createGive($data);

            if ($newGiveId) {
                $this->logAction('item_give_create', ['id' => $newGiveId, 'data' => $data]);
                $this->apiSuccess(['id' => $newGiveId], '지급 처리가 완료되었습니다.');
            } else {
                $this->apiError('지급 처리에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->apiBadRequest($e->getMessage()); // Show specific error like "재고가 부족합니다."
        }
    }

    /**
     * 특정 지급 내역을 삭제(취소)합니다.
     * @param int $id
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->itemGiveService->deleteGive($id);
            if ($success) {
                $this->logAction('item_give_delete', ['id' => $id]);
                $this->apiSuccess(null, '지급이 성공적으로 취소되었습니다.');
            } else {
                $this->apiError('지급 취소에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    private function logAction(string $action, array $details)
    {
        $currentUser = $this->authService->user();
        $this->logRepository->insert([
            'user_id' => $currentUser['id'],
            'employee_id' => $currentUser['employee_id'],
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }
}
