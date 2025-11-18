<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\MaintenanceService;
use Exception;

class MaintenanceController extends BaseController
{
    private MaintenanceService $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $maintenances = $this->maintenanceService->getMaintenances($filters);
            return $this->jsonResponse(['data' => $maintenances]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $maintenance = $this->maintenanceService->getMaintenanceById($id);
            if (!$maintenance) {
                return $this->jsonResponse(['error' => 'Maintenance record not found'], 404);
            }
            return $this->jsonResponse(['data' => $maintenance]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $maintenanceId = $this->maintenanceService->createMaintenance($data);
            $newMaintenance = $this->maintenanceService->getMaintenanceById($maintenanceId);
            return $this->jsonResponse(['data' => $newMaintenance], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->maintenanceService->updateMaintenance($id, $data);
            $updatedMaintenance = $this->maintenanceService->getMaintenanceById($id);
            return $this->jsonResponse(['data' => $updatedMaintenance]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->maintenanceService->deleteMaintenance($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete maintenance record'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
