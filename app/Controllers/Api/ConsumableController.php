<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\ConsumableService;
use Exception;

class ConsumableController extends BaseController
{
    private ConsumableService $consumableService;

    public function __construct(ConsumableService $consumableService)
    {
        $this->consumableService = $consumableService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $consumables = $this->consumableService->getConsumables($filters);
            return $this->jsonResponse(['data' => $consumables]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $consumable = $this->consumableService->getConsumableById($id);
            if (!$consumable) {
                return $this->jsonResponse(['error' => 'Consumable not found'], 404);
            }
            return $this->jsonResponse(['data' => $consumable]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $consumableId = $this->consumableService->createConsumable($data);
            $newConsumable = $this->consumableService->getConsumableById($consumableId);
            return $this->jsonResponse(['data' => $newConsumable], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->consumableService->updateConsumable($id, $data);
            $updatedConsumable = $this->consumableService->getConsumableById($id);
            return $this->jsonResponse(['data' => $updatedConsumable]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->consumableService->deleteConsumable($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete consumable'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
