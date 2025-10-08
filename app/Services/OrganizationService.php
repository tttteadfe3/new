<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;

class OrganizationService
{
    /**
     * 모든 부서 목록 조회
     */
    public function getAllDepartments(): array
    {
        return DepartmentRepository::getAll();
    }

    /**
     * 부서 생성
     */
    public function createDepartment(string $name): string
    {
        return DepartmentRepository::create($name);
    }

    /**
     * 부서 수정
     */
    public function updateDepartment(int $id, string $name): bool
    {
        return DepartmentRepository::update($id, $name);
    }

    /**
     * 부서 삭제
     */
    public function deleteDepartment(int $id): bool
    {
        return DepartmentRepository::delete($id);
    }

    /**
     * 부서 조회
     */
    public function getDepartment(int $id): ?array
    {
        return DepartmentRepository::findById($id);
    }

    /**
     * 모든 직급 목록 조회
     */
    public function getAllPositions(): array
    {
        return PositionRepository::getAll();
    }

    /**
     * 직급 생성
     */
    public function createPosition(string $name): string
    {
        return PositionRepository::create($name);
    }

    /**
     * 직급 수정
     */
    public function updatePosition(int $id, string $name): bool
    {
        return PositionRepository::update($id, $name);
    }

    /**
     * 직급 삭제
     */
    public function deletePosition(int $id): bool
    {
        return PositionRepository::delete($id);
    }

    /**
     * 직급 조회
     */
    public function getPosition(int $id): ?array
    {
        return PositionRepository::findById($id);
    }
}