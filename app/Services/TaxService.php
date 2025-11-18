<?php

namespace App\Services;

use App\Models\Tax;
use App\Repositories\TaxRepository;
use InvalidArgumentException;

class TaxService
{
    private TaxRepository $taxRepository;

    public function __construct(TaxRepository $taxRepository)
    {
        $this->taxRepository = $taxRepository;
    }

    public function getTaxes(array $filters = []): array
    {
        return $this->taxRepository->findAll($filters);
    }

    public function getTaxById(int $id): ?array
    {
        return $this->taxRepository->findById($id);
    }

    public function createTax(array $data): int
    {
        $tax = Tax::make($data);
        if (!$tax->validate()) {
            throw new InvalidArgumentException('Invalid tax data: ' . implode(', ', $tax->getErrors()));
        }

        return $this->taxRepository->save($tax->getAttributes());
    }

    public function updateTax(int $id, array $data): int
    {
        $existingTax = $this->getTaxById($id);
        if (!$existingTax) {
            throw new InvalidArgumentException('Tax record not found');
        }

        $tax = Tax::make($data);
        if (!$tax->validate(true)) {
            throw new InvalidArgumentException('Invalid tax data: ' . implode(', ', $tax->getErrors()));
        }

        $data['id'] = $id;
        return $this->taxRepository->save($data);
    }

    public function deleteTax(int $id): bool
    {
        return $this->taxRepository->delete($id);
    }
}
