<?php

namespace App\Services;

use App\Models\Consumable;
use App\Repositories\ConsumableRepository;
use InvalidArgumentException;

class ConsumableService
{
    private ConsumableRepository $consumableRepository;

    public function __construct(ConsumableRepository $consumableRepository)
    {
        $this->consumableRepository = $consumableRepository;
    }

    public function getConsumables(array $filters = []): array
    {
        return $this->consumableRepository->findAll($filters);
    }

    public function getConsumableById(int $id): ?array
    {
        return $this->consumableRepository->findById($id);
    }

    public function createConsumable(array $data): int
    {
        $consumable = Consumable::make($data);
        if (!$consumable->validate()) {
            throw new InvalidArgumentException('Invalid consumable data: ' . implode(', ', $consumable->getErrors()));
        }

        return $this->consumableRepository->save($consumable->getAttributes());
    }

    public function updateConsumable(int $id, array $data): int
    {
        $existingConsumable = $this->getConsumableById($id);
        if (!$existingConsumable) {
            throw new InvalidArgumentException('Consumable not found');
        }

        $consumable = Consumable::make($data);
        if (!$consumable->validate(true)) {
            throw new InvalidArgumentException('Invalid consumable data: ' . implode(', ', $consumable->getErrors()));
        }

        $data['id'] = $id;
        return $this->consumableRepository->save($data);
    }

    public function deleteConsumable(int $id): bool
    {
        return $this->consumableRepository->delete($id);
    }
}
