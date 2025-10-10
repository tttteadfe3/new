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
        // 이제 MenuRepository를 통해 조회
        return MenuRepository::findAllForAdmin();
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

    /**
     * 메뉴 순서와 계층 구조를 한 번에 업데이트 (트랜잭션)
     * @param array $updates 업데이트할 메뉴 데이터 배열
     * @return bool
     * @throws \Exception
     */
    public function updateOrderAndHierarchy(array $updates): bool
    {
        \App\Core\Database::beginTransaction();
        try {
            foreach ($updates as $menuItem) {
                $id = $menuItem['id'] ?? null;
                if (!$id) {
                    continue; // ID가 없으면 건너뛰기
                }
                
                $sql = "UPDATE sys_menus SET display_order = :display_order, parent_id = :parent_id WHERE id = :id";
                \App\Core\Database::execute($sql, [
                    ':display_order' => $menuItem['display_order'] ?? 0,
                    ':parent_id' => $menuItem['parent_id'] ?? null,
                    ':id' => $id
                ]);
            }
            \App\Core\Database::commit();
            return true;
        } catch (\Exception $e) {
            \App\Core\Database::rollBack();
            // Re-throw the exception to be handled by the controller
            throw $e;
        }
    }
}