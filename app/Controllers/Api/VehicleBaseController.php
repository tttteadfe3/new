<?php

namespace App\Controllers\Api;

use App\Services\VehicleService;
use Exception;

class VehicleBaseController extends BaseApiController
{
    private VehicleService $vehicleService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->vehicleService = $this->container->get(VehicleService::class);
    }

    public function index(): void
    {
        try {
            $vehicles = $this->vehicleService->getAllVehicles();
            $this->apiSuccess(array_map(fn($v) => $v->toArray(), $vehicles));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($id);
            if ($vehicle) {
                $this->apiSuccess($vehicle->toArray());
            } else {
                $this->apiNotFound("Vehicle with ID {$id} not found.");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $vehicle = $this->vehicleService->createVehicle($data);
            $this->apiSuccess($vehicle->toArray(), 'Vehicle created successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->vehicleService->updateVehicle($id, $data);
            if ($success) {
                $this->apiSuccess(null, "Vehicle with ID {$id} updated successfully.");
            } else {
                $this->apiNotFound("Vehicle with ID {$id} not found.");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $success = $this->vehicleService->deleteVehicle($id);
            if ($success) {
                $this->apiSuccess(null, "Vehicle with ID {$id} deleted successfully.");
            } else {
                $this->apiNotFound("Vehicle with ID {$id} not found.");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
