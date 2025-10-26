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

    /**
     * @return array
     */
    public function getAllPositions() {
        return $this->positionRepository->getAll();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getPositionById(int $id) {
        return $this->positionRepository->findById($id);
    }

    /**
     * @param array $data
     * @return array
     */
    public function createPosition(array $data): array {
        $errors = PositionValidator::validate($data);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $positionId = $this->positionRepository->create($data['name'], $data['level']);
        return ['id' => $positionId];
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updatePosition(int $id, array $data): array {
        $errors = PositionValidator::validate($data);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $position = $this->positionRepository->findById($id);
        if (!$position) {
            return ['success' => false, 'message' => '직급을 찾을 수 없습니다'];
        }

        if ($position['name'] === $data['name'] && $position['level'] == $data['level']) {
            return ['success' => true];
        }

        $success = $this->positionRepository->update($id, $data['name'], $data['level']);
        return ['success' => $success];
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deletePosition(int $id): bool {
        return $this->positionRepository->delete($id);
    }
}
