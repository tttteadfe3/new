<?php

namespace App\Services;

use App\Models\Breakdown;
use App\Repositories\BreakdownRepository;
use InvalidArgumentException;

class BreakdownService
{
    private BreakdownRepository $breakdownRepository;

    public function __construct(BreakdownRepository $breakdownRepository)
    {
        $this->breakdownRepository = $breakdownRepository;
    }

    public function getBreakdowns(array $filters = []): array
    {
        return $this->breakdownRepository->findAll($filters);
    }

    public function getBreakdownById(int $id): ?array
    {
        return $this->breakdownRepository->findById($id);
    }

    public function createBreakdown(array $data): int
    {
        // Set the initial status
        $data['status'] = 'REGISTERED';

        $breakdown = Breakdown::make($data);
        if (!$breakdown->validate()) {
            throw new InvalidArgumentException('Invalid breakdown data: ' . implode(', ', $breakdown->getErrors()));
        }

        return $this->breakdownRepository->save($breakdown->getAttributes());
    }

    public function updateBreakdown(int $id, array $data): int
    {
        $existingBreakdown = $this->getBreakdownById($id);
        if (!$existingBreakdown) {
            throw new InvalidArgumentException('Breakdown not found');
        }

        $breakdown = Breakdown::make($data);
        if (!$breakdown->validate(true)) {
            throw new InvalidArgumentException('Invalid breakdown data: ' . implode(', ', $breakdown->getErrors()));
        }

        $data['id'] = $id;
        return $this->breakdownRepository->save($data);
    }

    public function deleteBreakdown(int $id): bool
    {
        return $this->breakdownRepository->delete($id);
    }
}
