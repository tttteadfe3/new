<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\ConsumableLogService;
use App\Core\Request;
use Exception;

class ConsumableLogController extends BaseController
{
    private ConsumableLogService $consumableLogService;

    public function __construct(ConsumableLogService $consumableLogService, Request $request)
    {
        parent::__construct($request);
        $this->consumableLogService = $consumableLogService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $consumableLogs = $this->consumableLogService->getConsumableLogs($filters);
            return $this->jsonResponse(['data' => $consumableLogs]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $consumableLog = $this->consumableLogService->getConsumableLogById($id);
            if (!$consumableLog) {
                return $this->jsonResponse(['error' => 'Consumable log not found'], 404);
            }
            return $this->jsonResponse(['data' => $consumableLog]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $consumableLogId = $this->consumableLogService->createConsumableLog($data);
            $newConsumableLog = $this->consumableLogService->getConsumableLogById($consumableLogId);
            return $this->jsonResponse(['data' => $newConsumableLog], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->consumableLogService->updateConsumableLog($id, $data);
            $updatedConsumableLog = $this->consumableLogService->getConsumableLogById($id);
            return $this->jsonResponse(['data' => $updatedConsumableLog]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->consumableLogService->deleteConsumableLog($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete consumable log'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
