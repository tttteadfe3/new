<?php

namespace App\Services;

use App\Repositories\MenuRepository;

class MenuManagementService
{
    /**
     * 모든 메뉴 목록 조회 (관리용)
     */
    public function getAllMenusForAdmin(): array
    {
        // 관리자용으로 모든 메뉴를 직접 조회
        $sql = "SELECT * FROM sys_menus ORDER BY parent_id ASC, display_order ASC, name ASC";
        return \App\Core\Database::query($sql);
    }

    /**
     * 메뉴 생성
     */
    public function createMenu(array $menuData): string
    {
        return MenuRepository::create($menuData);
    }

    /**
     * 메뉴 수정
     */
    public function updateMenu(int $id, array $menuData): bool
    {
        return MenuRepository::update($id, $menuData);
    }

    /**
     * 메뉴 삭제
     */
    public function deleteMenu(int $id): bool
    {
        return MenuRepository::delete($id);
    }

    /**
     * 메뉴 조회
     */
    public function getMenu(int $id): ?array
    {
        return MenuRepository::findById($id);
    }

    /**
     * 메뉴 순서 변경
     */
    public function updateMenuOrder(int $id, int $displayOrder): bool
    {
        return MenuRepository::updateDisplayOrder($id, $displayOrder);
    }

    /**
     * 부모 메뉴 변경
     */
    public function updateMenuParent(int $id, ?int $parentId): bool
    {
        return MenuRepository::updateParent($id, $parentId);
    }
}