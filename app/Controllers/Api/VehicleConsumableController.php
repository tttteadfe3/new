<?php

namespace App\Controllers\Api;

use App\Services\VehicleConsumableService;
use Exception;

class VehicleConsumableController extends BaseApiController
{
    private VehicleConsumableService $consumableService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->consumableService = $this->container->get(VehicleConsumableService::class);
    }

    // ========== Consumable Management Endpoints ==========

    public function index(): void
    {
        try {
            $consumables = $this->consumableService->getAllConsumables();
            $this->apiSuccess(array_map(fn($c) => $c->toArray(), $consumables));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $consumable = $this->consumableService->createConsumable($data);
            $this->apiSuccess($consumable->toArray(), 'Consumable created successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========== Consumable Log Endpoints ==========

    public function getLogsForVehicle(int $vehicleId): void
    {
        try {
            $logs = $this->consumableService->getLogsByVehicleId($vehicleId);
            $this->apiSuccess(array_map(fn($l) => $l->toArray(), $logs));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function storeLog(): void
    {
        try {
            $data = $this->getJsonInput();
            $log = $this->consumableService->recordConsumableUsage($data);
            $this->apiSuccess($log->toArray(), 'Consumable usage recorded successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
