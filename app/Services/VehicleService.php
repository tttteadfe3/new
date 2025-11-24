<?php

namespace App\Services;

use App\Repositories\VehicleRepository;
use App\Models\Vehicle;

class VehicleService
{
    private VehicleRepository $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getAllVehicles(array $filters = []): array
    {
        return $this->vehicleRepository->findAll($filters);
    }

    public function getVehicleById(int $id): ?array
    {
        return $this->vehicleRepository->findById($id);
    }

    public function registerVehicle(array $data): int
    {
        // Validation logic can be added here if needed, 
        // but basic validation is handled in Model/Controller usually.
        // For now, just pass to repository.
        return $this->vehicleRepository->create($data);
    }

    public function updateVehicle(int $id, array $data): bool
    {
        return $this->vehicleRepository->update($id, $data);
    }

    public function deleteVehicle(int $id): bool
    {
        return $this->vehicleRepository->delete($id);
    }
}
