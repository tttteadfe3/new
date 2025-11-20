<?php

namespace App\Services;

use App\Models\VehicleRepair;
use App\Models\VehicleSelfMaintenance;
use App\Repositories\VehicleRepairRepository;
use App\Repositories\VehicleSelfMaintenanceRepository;
use Exception;

class VehicleMaintenanceService
{
    private VehicleRepairRepository $repairRepository;
    private VehicleSelfMaintenanceRepository $selfMaintenanceRepository;

    public function __construct(
        VehicleRepairRepository $repairRepository,
        VehicleSelfMaintenanceRepository $selfMaintenanceRepository
    ) {
        $this->repairRepository = $repairRepository;
        $this->selfMaintenanceRepository = $selfMaintenanceRepository;
    }

    // ========== Repair Methods ==========

    public function getRepairById(int $id): ?VehicleRepair
    {
        return $this->repairRepository->find($id);
    }

    public function getRepairsByBreakdownId(int $breakdownId): array
    {
        return $this->repairRepository->findByBreakdownId($breakdownId);
    }

    public function recordRepair(array $data): VehicleRepair
    {
        $repair = new VehicleRepair($data);
        if (!$repair->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $repair->getErrors()));
        }
        return $this->repairRepository->create($repair);
    }

    // ========== Self-Maintenance Methods ==========

    public function getSelfMaintenanceById(int $id): ?VehicleSelfMaintenance
    {
        return $this->selfMaintenanceRepository->find($id);
    }

    public function getSelfMaintenancesByVehicleId(int $vehicleId): array
    {
        return $this->selfMaintenanceRepository->findByVehicleId($vehicleId);
    }

    public function recordSelfMaintenance(array $data): VehicleSelfMaintenance
    {
        $maintenance = new VehicleSelfMaintenance($data);
        if (!$maintenance->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $maintenance->getErrors()));
        }
        return $this->selfMaintenanceRepository->create($maintenance);
    }
}
