<?php

namespace App\Services;

use App\Models\Insurance;
use App\Repositories\InsuranceRepository;
use InvalidArgumentException;

class InsuranceService
{
    private InsuranceRepository $insuranceRepository;

    public function __construct(InsuranceRepository $insuranceRepository)
    {
        $this->insuranceRepository = $insuranceRepository;
    }

    public function getInsurances(array $filters = []): array
    {
        return $this->insuranceRepository->findAll($filters);
    }

    public function getInsuranceById(int $id): ?array
    {
        return $this->insuranceRepository->findById($id);
    }

    public function createInsurance(array $data): int
    {
        $insurance = Insurance::make($data);
        if (!$insurance->validate()) {
            throw new InvalidArgumentException('Invalid insurance data: ' . implode(', ', $insurance->getErrors()));
        }

        return $this->insuranceRepository->save($insurance->getAttributes());
    }

    public function updateInsurance(int $id, array $data): int
    {
        $existingInsurance = $this->getInsuranceById($id);
        if (!$existingInsurance) {
            throw new InvalidArgumentException('Insurance record not found');
        }

        $insurance = Insurance::make($data);
        if (!$insurance->validate(true)) {
            throw new InvalidArgumentException('Invalid insurance data: ' . implode(', ', $insurance->getErrors()));
        }

        $data['id'] = $id;
        return $this->insuranceRepository->save($data);
    }

    public function deleteInsurance(int $id): bool
    {
        return $this->insuranceRepository->delete($id);
    }
}
