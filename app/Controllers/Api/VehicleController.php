<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\VehicleService;
use Exception;

class VehicleController extends BaseController
{
    private VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $vehicles = $this->vehicleService->getVehicles($filters);
            return $this->jsonResponse(['data' => $vehicles]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($id);
            if (!$vehicle) {
                return $this->jsonResponse(['error' => 'Vehicle not found'], 404);
            }
            return $this->jsonResponse(['data' => $vehicle]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $vehicleId = $this->vehicleService->createVehicle($data);
            $newVehicle = $this->vehicleService->getVehicleById($vehicleId);
            return $this->jsonResponse(['data' => $newVehicle], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->vehicleService->updateVehicle($id, $data);
            $updatedVehicle = $this->vehicleService->getVehicleById($id);
            return $this->jsonResponse(['data' => $updatedVehicle]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->vehicleService->deleteVehicle($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete vehicle'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
