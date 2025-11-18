<?php

namespace App\Services;

use App\Models\Inspection;
use App\Repositories\InspectionRepository;
use InvalidArgumentException;

class InspectionService
{
    private InspectionRepository $inspectionRepository;

    public function __construct(InspectionRepository $inspectionRepository)
    {
        $this->inspectionRepository = $inspectionRepository;
    }

    public function getInspections(array $filters = []): array
    {
        return $this->inspectionRepository->findAll($filters);
    }

    public function getInspectionById(int $id): ?array
    {
        return $this->inspectionRepository->findById($id);
    }

    public function createInspection(array $data): int
    {
        $inspection = Inspection::make($data);
        if (!$inspection->validate()) {
            throw new InvalidArgumentException('Invalid inspection data: ' . implode(', ', $inspection->getErrors()));
        }

        return $this->inspectionRepository->save($inspection->getAttributes());
    }

    public function updateInspection(int $id, array $data): int
    {
        $existingInspection = $this->getInspectionById($id);
        if (!$existingInspection) {
            throw new InvalidArgumentException('Inspection record not found');
        }

        $inspection = Inspection::make($data);
        if (!$inspection->validate(true)) {
            throw new InvalidArgumentException('Invalid inspection data: ' . implode(', ', $inspection->getErrors()));
        }

        $data['id'] = $id;
        return $this->inspectionRepository->save($data);
    }

    public function deleteInspection(int $id): bool
    {
        return $this->inspectionRepository->delete($id);
    }
}
