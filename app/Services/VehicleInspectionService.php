<?php

namespace App\Services;

use App\Repositories\VehicleInspectionRepository;
use App\Models\VehicleInspection;

class VehicleInspectionService
{
    private VehicleInspectionRepository $inspectionRepository;

    public function __construct(VehicleInspectionRepository $inspectionRepository)
    {
        $this->inspectionRepository = $inspectionRepository;
    }

    public function getInspections(array $filters = []): array
    {
        return $this->inspectionRepository->findAll($filters);
    }

    public function registerInspection(array $data): int
    {
        return $this->inspectionRepository->create($data);
    }

    public function getUpcomingInspections(int $days = 30): array
    {
        return $this->inspectionRepository->findAll(['upcoming_expiry' => $days]);
    }

    public function getInspectionById(int $id): ?VehicleInspection
    {
        return $this->inspectionRepository->findById($id);
    }

    public function updateInspection(int $id, array $data): void
    {
        $this->inspectionRepository->update($id, $data);
    }

    public function deleteInspection(int $id): void
    {
        $this->inspectionRepository->delete($id);
    }
}
