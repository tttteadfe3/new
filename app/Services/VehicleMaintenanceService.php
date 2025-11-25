<?php

namespace App\Services;

use App\Repositories\VehicleMaintenanceRepository;
use App\Repositories\VehicleRepository;
use InvalidArgumentException;

class VehicleMaintenanceService
{
    private VehicleMaintenanceRepository $workRepository;
    private VehicleRepository $vehicleRepository;

    public function __construct(
        VehicleMaintenanceRepository $workRepository,
        VehicleRepository $vehicleRepository
    ) {
        $this->workRepository = $workRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * 작업 목록 조회
     */
    public function getAllWorks(array $filters = []): array
    {
        return $this->workRepository->findAll($filters);
    }

    /**
     * 작업 상세 조회
     */
    public function getWork(int $id): ?array
    {
        return $this->workRepository->findById($id);
    }

    /**
     * 작업 신고 (고장 또는 정비)
     */
    public function reportWork(array $data): int
    {
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException('차량을 선택해주세요.');
        }

        $vehicle = $this->vehicleRepository->findById($data['vehicle_id']);
        if (!$vehicle) {
            throw new InvalidArgumentException('유효하지 않은 차량입니다.');
        }

        // type: '고장' or '정비'
        // status: 기본값 '신고'
        if ($data['type'] === '정비') {
            $data['status'] = '완료';
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->workRepository->create($data);
    }

    /**
     * 고장 워크플로우: 수리 방법 결정
     * 신고 -> 처리결정
     */
    public function decideRepairType(int $id, string $repairType): bool
    {
        return $this->workRepository->update($id, [
            'status' => '처리결정',
            'repair_type' => $repairType, // '자체수리' or '외부수리'
            'decided_at' => date('Y-m-d H:i:s'),
            'decided_by' => null // TODO: 현재 사용자 ID
        ]);
    }

    /**
     * 작업 시작
     * 처리결정 -> 작업중 (고장)
     * 신고 -> 작업중 (정비)
     */
    public function startWork(int $id, array $data = []): bool
    {
        $updateData = [
            'status' => '작업중'
        ];
        
        // 작업자 정보
        if (!empty($data['worker_id'])) {
            $updateData['worker_id'] = $data['worker_id'];
        }
        
        // 외부 수리업체
        if (!empty($data['repair_shop'])) {
            $updateData['repair_shop'] = $data['repair_shop'];
        }
        
        return $this->workRepository->update($id, $updateData);
    }

    /**
     * 작업 완료
     * 작업중 -> 완료
     */
    public function completeWork(int $id, array $data = []): bool
    {
        $updateData = [
            'status' => '완료',
            'completed_at' => date('Y-m-d H:i:s')
        ];
        
        // 사용 부품
        if (!empty($data['parts_used'])) {
            $updateData['parts_used'] = $data['parts_used'];
        }
        
        // 비용
        if (isset($data['cost'])) {
            $updateData['cost'] = $data['cost'];
        }
        
        return $this->workRepository->update($id, $updateData);
    }

    /**
     * 작업 확인 (Manager)
     * 완료 -> (확인 완료 플래그)
     */
    public function confirmWork(int $id): bool
    {
        return $this->workRepository->update($id, [
            'status' => '승인',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => null // TODO: 현재 사용자 ID
        ]);
    }

    /**
     * 작업 삭제
     */
    public function deleteWork(int $id): bool
    {
        return $this->workRepository->delete($id);
    }

    /**
     * 작업 정보 수정 (통합)
     */
    public function updateWork(int $id, array $data): bool
    {
        return $this->workRepository->update($id, $data);
    }
}
