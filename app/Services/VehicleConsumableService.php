<?php

namespace App\Services;

use App\Models\VehicleConsumable;
use App\Models\VehicleConsumableLog;
use App\Repositories\VehicleConsumableRepository;
use App\Repositories\VehicleConsumableLogRepository;
use Exception;

class VehicleConsumableService
{
    private VehicleConsumableRepository $consumableRepository;
    private VehicleConsumableLogRepository $logRepository;

    public function __construct(
        VehicleConsumableRepository $consumableRepository,
        VehicleConsumableLogRepository $logRepository
    ) {
        $this->consumableRepository = $consumableRepository;
        $this->logRepository = $logRepository;
    }

    // ========== Consumable Management ==========

    public function getConsumableById(int $id): ?VehicleConsumable
    {
        return $this->consumableRepository->find($id);
    }

    public function getAllConsumables(): array
    {
        return $this->consumableRepository->findAll();
    }

    public function createConsumable(array $data): VehicleConsumable
    {
        $consumable = new VehicleConsumable($data);
        if (!$consumable->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $consumable->getErrors()));
        }
        return $this->consumableRepository->create($consumable);
    }

    public function updateConsumable(int $id, array $data): bool
    {
        $consumable = $this->consumableRepository->find($id);
        if (!$consumable) {
            return false;
        }
        $consumable->fill($data);
        if (!$consumable->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $consumable->getErrors()));
        }
        return $this->consumableRepository->update($consumable);
    }

    // ========== Consumable Log Management ==========

    public function getLogsByVehicleId(int $vehicleId): array
    {
        return $this->logRepository->findByVehicleId($vehicleId);
    }

    public function recordConsumableUsage(array $data): VehicleConsumableLog
    {
        $log = new VehicleConsumableLog($data);
        if (!$log->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $log->getErrors()));
        }
        return $this->logRepository->create($log);
    }
}
