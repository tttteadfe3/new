<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\MenuRepository;

class MenuManagementService
{
    private MenuRepository $menuRepository;
    private Database $db;

    public function __construct(MenuRepository $menuRepository, Database $db) {
        $this->menuRepository = $menuRepository;
        $this->db = $db;
    }

    /**
     * 모든 메뉴 목록 조회 (관리용)
     * @return array
     */
    public function getAllMenusForAdmin(): array
    {
        return $this->menuRepository->findAllForAdmin();
    }

    /**
     * 메뉴 생성
     * @param array $menuData
     * @return string
     */
    public function createMenu(array $menuData): string
    {
        return $this->menuRepository->create($menuData);
    }

    /**
     * 메뉴 수정
     * @param int $id
     * @param array $menuData
     * @return bool
     */
    public function updateMenu(int $id, array $menuData): bool
    {
        return $this->menuRepository->update($id, $menuData);
    }

    /**
     * 메뉴 삭제
     * @param int $id
     * @return bool
     */
    public function deleteMenu(int $id): bool
    {
        return $this->menuRepository->delete($id);
    }

    /**
     * 메뉴 조회
     * @param int $id
     * @return array|null
     */
    public function getMenu(int $id): ?array
    {
        return $this->menuRepository->findById($id);
    }

    /**
     * 메뉴 순서 변경
     * @param int $id
     * @param int $displayOrder
     * @return bool
     */
    public function updateMenuOrder(int $id, int $displayOrder): bool
    {
        return $this->menuRepository->updateDisplayOrder($id, $displayOrder);
    }

    /**
     * 부모 메뉴 변경
     * @param int $id
     * @param int|null $parentId
     * @return bool
     */
    public function updateMenuParent(int $id, ?int $parentId): bool
    {
        return $this->menuRepository->updateParent($id, $parentId);
    }

    /**
     * 메뉴 순서와 계층 구조를 한 번에 업데이트 (트랜잭션)
     * @param array $updates 업데이트할 메뉴 데이터 배열
     * @return bool
     * @throws \Exception
     */
    public function updateOrderAndHierarchy(array $updates): bool
    {
        $this->db->beginTransaction();
        try {
            foreach ($updates as $menuItem) {
                $id = $menuItem['id'] ?? null;
                if (!$id) {
                    continue; // ID가 없으면 건너뛰기
                }
                
                $sql = "UPDATE sys_menus SET display_order = :display_order, parent_id = :parent_id WHERE id = :id";
                $this->db->execute($sql, [
                    ':display_order' => $menuItem['display_order'] ?? 0,
                    ':parent_id' => $menuItem['parent_id'] ?? null,
                    ':id' => $id
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            // 컨트롤러에서 처리하도록 예외 다시 발생
            throw $e;
        }
    }
}
