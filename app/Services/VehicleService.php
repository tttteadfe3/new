<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use Exception;

class VehicleService
{
    private VehicleRepository $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getVehicleById(int $id): ?Vehicle
    {
        return $this->vehicleRepository->find($id);
    }

    public function getAllVehicles(): array
    {
        return $this->vehicleRepository->findAll();
    }

    public function createVehicle(array $data): Vehicle
    {
        $vehicle = new Vehicle($data);
        if (!$vehicle->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $vehicle->getErrors()));
        }
        return $this->vehicleRepository->create($vehicle);
    }

    public function updateVehicle(int $id, array $data): bool
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return false;
        }
        $vehicle->fill($data);
        if (!$vehicle->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $vehicle->getErrors()));
        }
        return $this->vehicleRepository->update($vehicle);
    }

    public function deleteVehicle(int $id): bool
    {
        return $this->vehicleRepository->delete($id);
    }

    public function changeVehicleStatus(int $id, string $status): bool
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return false;
        }
        $vehicle->status = $status;
        return $this->vehicleRepository->update($vehicle);
    }

    public function assignToDepartment(int $id, int $departmentId): bool
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return false;
        }
        $vehicle->department_id = $departmentId;
        return $this->vehicleRepository->update($vehicle);
    }
}
