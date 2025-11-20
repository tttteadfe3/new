<?php

namespace App\Services;

use App\Models\VehicleBreakdown;
use App\Repositories\VehicleBreakdownRepository;
use App\Repositories\VehicleRepository;
use Exception;

class VehicleBreakdownService
{
    private VehicleBreakdownRepository $breakdownRepository;
    private VehicleRepository $vehicleRepository;

    public function __construct(VehicleBreakdownRepository $breakdownRepository, VehicleRepository $vehicleRepository)
    {
        $this->breakdownRepository = $breakdownRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getBreakdownById(int $id): ?VehicleBreakdown
    {
        return $this->breakdownRepository->find($id);
    }

    public function getBreakdownsByVehicleId(int $vehicleId): array
    {
        return $this->breakdownRepository->findByVehicleId($vehicleId);
    }

    public function getAllBreakdowns(): array
    {
        return $this->breakdownRepository->findAll();
    }

    public function reportBreakdown(array $data): VehicleBreakdown
    {
        // 1. Check if vehicle exists
        $vehicle = $this->vehicleRepository->find($data['vehicle_id']);
        if (!$vehicle) {
            throw new Exception("Vehicle not found.");
        }

        // 2. Validate breakdown data
        $breakdown = new VehicleBreakdown($data);
        if (!$breakdown->validate()) {
            throw new Exception("Validation failed: " . implode(", ", $breakdown->getErrors()));
        }

        // 3. Create the breakdown
        $newBreakdown = $this->breakdownRepository->create($breakdown);

        // 4. Update vehicle status to 'in_repair'
        $vehicle->status = 'in_repair';
        $this->vehicleRepository->update($vehicle);

        return $newBreakdown;
    }

    public function updateBreakdownStatus(int $id, string $status): bool
    {
        $breakdown = $this->breakdownRepository->find($id);
        if (!$breakdown) {
            return false;
        }

        $breakdown->status = $status;
        $now = date('Y-m-d H:i:s');

        switch ($status) {
            case 'confirmed':
                $breakdown->confirmed_at = $now;
                break;
            case 'resolved':
                $breakdown->resolved_at = $now;
                break;
            case 'approved':
                $breakdown->approved_at = $now;
                // If breakdown is fully approved, set vehicle status back to 'active'
                $vehicle = $this->vehicleRepository->find($breakdown->vehicle_id);
                if ($vehicle) {
                    $vehicle->status = 'active';
                    $this->vehicleRepository->update($vehicle);
                }
                break;
        }

        return $this->breakdownRepository->update($breakdown);
    }

    public function deleteBreakdown(int $id): bool
    {
        $breakdown = $this->breakdownRepository->find($id);
        if (!$breakdown) {
            return false; // Or throw exception
        }

        // If the vehicle was in repair due to this breakdown, it might need status update.
        // This logic can be complex, for now we just delete.

        return $this->breakdownRepository->delete($id);
    }
}
