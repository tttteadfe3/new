<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use InvalidArgumentException;

class VehicleService
{
    private VehicleRepository $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getVehicles(array $filters = []): array
    {
        return $this->vehicleRepository->findAll($filters);
    }

    public function getVehicleById(int $id): ?array
    {
        return $this->vehicleRepository->findById($id);
    }

    public function createVehicle(array $data): int
    {
        $vehicle = Vehicle::make($data);
        if (!$vehicle->validate()) {
            throw new InvalidArgumentException('Invalid vehicle data: ' . implode(', ', $vehicle->getErrors()));
        }

        return $this->vehicleRepository->save($vehicle->getAttributes());
    }

    public function updateVehicle(int $id, array $data): int
    {
        $existingVehicle = $this->getVehicleById($id);
        if (!$existingVehicle) {
            throw new InvalidArgumentException('Vehicle not found');
        }

        // Use a different validation scenario for updates
        $vehicle = Vehicle::make($data);
        if (!$vehicle->validate(true)) {
            throw new InvalidArgumentException('Invalid vehicle data: ' . implode(', ', $vehicle->getErrors()));
        }

        $data['id'] = $id;
        return $this->vehicleRepository->save($data);
    }

    public function deleteVehicle(int $id): bool
    {
        return $this->vehicleRepository->delete($id);
    }
}
