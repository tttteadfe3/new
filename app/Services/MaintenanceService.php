<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Repositories\MaintenanceRepository;
use InvalidArgumentException;

class MaintenanceService
{
    private MaintenanceRepository $maintenanceRepository;

    public function __construct(MaintenanceRepository $maintenanceRepository)
    {
        $this->maintenanceRepository = $maintenanceRepository;
    }

    public function getMaintenances(array $filters = []): array
    {
        return $this->maintenanceRepository->findAll($filters);
    }

    public function getMaintenanceById(int $id): ?array
    {
        return $this->maintenanceRepository->findById($id);
    }

    public function createMaintenance(array $data): int
    {
        // Set the initial status
        $data['status'] = 'COMPLETED';

        $maintenance = Maintenance::make($data);
        if (!$maintenance->validate()) {
            throw new InvalidArgumentException('Invalid maintenance data: ' . implode(', ', $maintenance->getErrors()));
        }

        return $this->maintenanceRepository->save($maintenance->getAttributes());
    }

    public function updateMaintenance(int $id, array $data): int
    {
        $existingMaintenance = $this->getMaintenanceById($id);
        if (!$existingMaintenance) {
            throw new InvalidArgumentException('Maintenance record not found');
        }

        $maintenance = Maintenance::make($data);
        if (!$maintenance->validate(true)) {
            throw new InvalidArgumentException('Invalid maintenance data: ' . implode(', ', $maintenance->getErrors()));
        }

        $data['id'] = $id;
        return $this->maintenanceRepository->save($data);
    }

    public function deleteMaintenance(int $id): bool
    {
        return $this->maintenanceRepository->delete($id);
    }
}
