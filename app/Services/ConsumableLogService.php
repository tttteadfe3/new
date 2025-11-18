<?php

namespace App\Services;

use App\Models\ConsumableLog;
use App\Repositories\ConsumableLogRepository;
use InvalidArgumentException;

class ConsumableLogService
{
    private ConsumableLogRepository $consumableLogRepository;

    public function __construct(ConsumableLogRepository $consumableLogRepository)
    {
        $this->consumableLogRepository = $consumableLogRepository;
    }

    public function getConsumableLogs(array $filters = []): array
    {
        return $this->consumableLogRepository->findAll($filters);
    }

    public function getConsumableLogById(int $id): ?array
    {
        return $this->consumableLogRepository->findById($id);
    }

    public function createConsumableLog(array $data): int
    {
        $consumableLog = ConsumableLog::make($data);
        if (!$consumableLog->validate()) {
            throw new InvalidArgumentException('Invalid consumable log data: ' . implode(', ', $consumableLog->getErrors()));
        }

        return $this->consumableLogRepository->save($consumableLog->getAttributes());
    }

    public function updateConsumableLog(int $id, array $data): int
    {
        $existingConsumableLog = $this->getConsumableLogById($id);
        if (!$existingConsumableLog) {
            throw new InvalidArgumentException('Consumable log not found');
        }

        $consumableLog = ConsumableLog::make($data);
        if (!$consumableLog->validate(true)) {
            throw new InvalidArgumentException('Invalid consumable log data: ' . implode(', ', $consumableLog->getErrors()));
        }

        $data['id'] = $id;
        return $this->consumableLogRepository->save($data);
    }

    public function deleteConsumableLog(int $id): bool
    {
        return $this->consumableLogRepository->delete($id);
    }
}
