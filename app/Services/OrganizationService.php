<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;

class OrganizationService
{
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;

    public function __construct(DepartmentRepository $departmentRepository, PositionRepository $positionRepository)
    {
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
    }

    /**
     * 모든 부서 목록 조회
     */
    public function getAllDepartments(): array
    {
        return $this->departmentRepository->getAll();
    }

    /**
     * 부서 생성
     */
    public function createDepartment(string $name): string
    {
        return $this->departmentRepository->create($name);
    }

    /**
     * 부서 수정
     */
    public function updateDepartment(int $id, string $name): bool
    {
        return $this->departmentRepository->update($id, $name);
    }

    /**
     * 부서 삭제
     */
    public function deleteDepartment(int $id): bool
    {
        return $this->departmentRepository->delete($id);
    }

    /**
     * 부서 조회
     */
    public function getDepartment(int $id): ?array
    {
        return $this->departmentRepository->findById($id);
    }

    /**
     * 모든 직급 목록 조회
     */
    public function getAllPositions(): array
    {
        return $this->positionRepository->getAll();
    }

    /**
     * 직급 생성
     */
    public function createPosition(string $name): string
    {
        return $this->positionRepository->create($name);
    }

    /**
     * 직급 수정
     */
    public function updatePosition(int $id, string $name): bool
    {
        return $this->positionRepository->update($id, $name);
    }

    /**
     * 직급 삭제
     */
    public function deletePosition(int $id): bool
    {
        return $this->positionRepository->delete($id);
    }

    /**
     * 직급 조회
     */
    public function getPosition(int $id): ?array
    {
        return $this->positionRepository->findById($id);
    }
}
