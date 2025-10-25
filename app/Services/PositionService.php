<?php
// app/Services/PositionService.php
namespace App\Services;

use App\Repositories\PositionRepository;
use App\Validators\PositionValidator;

class PositionService {
    private PositionRepository $positionRepository;

    public function __construct(PositionRepository $positionRepository) {
        $this->positionRepository = $positionRepository;
    }

    public function getAllPositions() {
        return $this->positionRepository->getAll();
    }

    public function getPositionById(int $id) {
        return $this->positionRepository->findById($id);
    }

    public function createPosition(array $data): array {
        $errors = PositionValidator::validate($data);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $positionId = $this->positionRepository->create($data['name'], $data['level']);
        return ['id' => $positionId];
    }

    public function updatePosition(int $id, array $data): array {
        $errors = PositionValidator::validate($data);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $success = $this->positionRepository->update($id, $data['name'], $data['level']);
        return ['success' => $success];
    }

    public function deletePosition(int $id): bool {
        return $this->positionRepository->delete($id);
    }
}
