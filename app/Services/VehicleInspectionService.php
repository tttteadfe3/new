<?php

namespace App\Services;

use App\Repositories\VehicleInspectionRepository;

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
}
