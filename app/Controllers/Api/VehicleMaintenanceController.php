<?php

namespace App\Controllers\Api;

use App\Services\VehicleMaintenanceService;
use Exception;

class VehicleMaintenanceController extends BaseApiController
{
    private VehicleMaintenanceService $maintenanceService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->maintenanceService = $this->container->get(VehicleMaintenanceService::class);
    }

    // ========== Repair Endpoints ==========

    public function getRepairsForBreakdown(int $breakdownId): void
    {
        try {
            $repairs = $this->maintenanceService->getRepairsByBreakdownId($breakdownId);
            $this->apiSuccess(array_map(fn($r) => $r->toArray(), $repairs));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function storeRepair(): void
    {
        try {
            $data = $this->getJsonInput();
            $repair = $this->maintenanceService->recordRepair($data);
            $this->apiSuccess($repair->toArray(), 'Repair recorded successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========== Self-Maintenance Endpoints ==========

    public function getSelfMaintenancesForVehicle(int $vehicleId): void
    {
        try {
            $maintenances = $this->maintenanceService->getSelfMaintenancesByVehicleId($vehicleId);
            $this->apiSuccess(array_map(fn($m) => $m->toArray(), $maintenances));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function storeSelfMaintenance(): void
    {
        try {
            $data = $this->getJsonInput();
            $data['driver_id'] = $this->getCurrentEmployeeId(); // Automatically set driver

            if (empty($data['driver_id'])) {
                $this->apiForbidden('Could not identify the driver.');
                return;
            }

            $maintenance = $this->maintenanceService->recordSelfMaintenance($data);
            $this->apiSuccess($maintenance->toArray(), 'Self-maintenance recorded successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
