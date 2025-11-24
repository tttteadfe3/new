<?php

namespace App\Services;

use App\Repositories\VehicleMaintenanceRepository;

class VehicleMaintenanceService
{
    private VehicleMaintenanceRepository $maintenanceRepository;

    public function __construct(VehicleMaintenanceRepository $maintenanceRepository)
    {
        $this->maintenanceRepository = $maintenanceRepository;
    }

    // Breakdowns
    public function reportBreakdown(array $data): int
    {
        return $this->maintenanceRepository->createBreakdown($data);
    }

    public function getBreakdowns(array $filters = []): array
    {
        return $this->maintenanceRepository->findAllBreakdowns($filters);
    }

    public function getBreakdown(int $id): ?object
    {
        return $this->maintenanceRepository->findBreakdownById($id);
    }

    public function updateBreakdownStatus(int $id, string $status): bool
    {
        return $this->maintenanceRepository->updateBreakdown($id, ['status' => $status]);
    }

    // Breakdown Workflow
    public function decideRepairType(int $breakdownId, string $type): bool
    {
        // $type could be '자체수리' or '외부수리'
        // Update status to 처리결정 and potentially store the decision type if needed
        // For now, we just update status to 처리결정. 
        return $this->maintenanceRepository->updateBreakdown($breakdownId, ['status' => '처리결정']);
    }

    public function completeRepair(int $breakdownId, array $repairData): int
    {
        // 1. Create Repair Record
        $repairData['breakdown_id'] = $breakdownId;
        $repairId = $this->maintenanceRepository->createRepair($repairData);

        // 2. Update Breakdown Status to 완료
        $this->maintenanceRepository->updateBreakdown($breakdownId, ['status' => '완료']);

        return $repairId;
    }

    public function confirmRepair(int $breakdownId): bool
    {
        // Update Breakdown Status to 승인완료
        return $this->maintenanceRepository->updateBreakdown($breakdownId, ['status' => '승인완료']);
    }

    // Repairs
    public function registerRepair(array $data): int
    {
        // Ensure breakdown exists and is in correct state if needed
        // For now, just create repair
        return $this->maintenanceRepository->createRepair($data);
    }

    public function getRepairByBreakdown(int $breakdownId): ?object
    {
        return $this->maintenanceRepository->findRepairByBreakdownId($breakdownId);
    }

    // Self Maintenance
    public function recordSelfMaintenance(array $data): int
    {
        return $this->maintenanceRepository->createMaintenance($data);
    }

    public function getSelfMaintenances(array $filters = []): array
    {
        return $this->maintenanceRepository->findAllMaintenances($filters);
    }

    // Self Maintenance Workflow
    public function confirmSelfMaintenance(int $maintenanceId): bool
    {
        // Update Maintenance Status to 승인완료
        return $this->maintenanceRepository->updateMaintenance($maintenanceId, ['status' => '승인완료']);
    }
}
