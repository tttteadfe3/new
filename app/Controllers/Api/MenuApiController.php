<?php

namespace App\Controllers\Api;

use App\Core\Database;
use Exception;

class MenuApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all menus
     */
    public function index(): void
    {
        $this->requireAuth('menu_admin');
        
        try {
            $sql = "SELECT * FROM sys_menus ORDER BY parent_id, display_order";
            $menus = Database::query($sql);
            $this->apiSuccess($menus);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get a single menu by ID
     */
    public function show(int $id): void
    {
        $this->requireAuth('menu_admin');

        try {
            if (empty($id)) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }

            $sql = "SELECT * FROM sys_menus WHERE id = :id";
            $menu = Database::fetchOne($sql, [':id' => $id]);

            if ($menu) {
                $this->apiSuccess($menu);
            } else {
                $this->apiNotFound('메뉴를 찾을 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new menu
     */
    public function store(): void
    {
        $this->requireAuth('menu_admin');
        
        try {
            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }
            
            $name = $data['name'] ?? '';
            if (empty($name)) {
                $this->apiBadRequest('메뉴 이름은 필수입니다.');
                return;
            }

            $sql = "INSERT INTO sys_menus (name, url, icon, permission_key, parent_id, display_order) VALUES (:name, :url, :icon, :permission_key, :parent_id, :display_order)";
            $params = [
                ':name' => $name,
                ':url' => $data['url'] ?? null,
                ':icon' => $data['icon'] ?? null,
                ':permission_key' => $data['permission_key'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':display_order' => $data['display_order'] ?? 0
            ];
            $newId = Database::insert($sql, $params);
            $this->apiSuccess(['message' => '메뉴가 생성되었습니다.', 'id' => $newId], 201);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing menu
     */
    public function update(int $id): void
    {
        $this->requireAuth('menu_admin');

        try {
            if (empty($id)) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }

            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }

            $name = $data['name'] ?? '';
            if (empty($name)) {
                $this->apiBadRequest('메뉴 이름은 필수입니다.');
                return;
            }

            $sql = "UPDATE sys_menus SET name = :name, url = :url, icon = :icon, permission_key = :permission_key, parent_id = :parent_id, display_order = :display_order WHERE id = :id";
            $params = [
                ':id' => $id,
                ':name' => $name,
                ':url' => $data['url'] ?? null,
                ':icon' => $data['icon'] ?? null,
                ':permission_key' => $data['permission_key'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':display_order' => $data['display_order'] ?? 0
            ];
            Database::execute($sql, $params);
            $this->apiSuccess(['message' => '메뉴가 업데이트되었습니다.']);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update menu order
     */
    public function updateOrder(): void
    {
        $this->requireAuth('menu_admin');

        try {
            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }

            Database::beginTransaction();

            foreach ($data as $menuItem) {
                $id = $menuItem['id'] ?? null;
                $order = $menuItem['display_order'] ?? 0;
                $parentId = $menuItem['parent_id'] ?? null;

                if (!$id) continue;

                $sql = "UPDATE sys_menus SET display_order = :display_order, parent_id = :parent_id WHERE id = :id";
                Database::execute($sql, [
                    ':display_order' => $order,
                    ':parent_id' => $parentId,
                    ':id' => $id
                ]);
            }

            Database::commit();
            $this->apiSuccess(['message' => '메뉴 순서가 업데이트되었습니다.']);

        } catch (Exception $e) {
            Database::rollBack();
            $this->handleException($e);
        }
    }

    /**
     * Delete a menu
     */
    public function destroy(int $id): void
    {
        $this->requireAuth('menu_admin');
        
        try {
            if (!$id) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }
            
            // Check for child menus
            $sql = "SELECT COUNT(*) as count FROM sys_menus WHERE parent_id = :id";
            $result = Database::fetchOne($sql, [':id' => $id]);
            
            if ($result && $result['count'] > 0) {
                $this->apiBadRequest('하위 메뉴가 있는 메뉴는 삭제할 수 없습니다. 하위 메뉴를 먼저 삭제하거나 다른 곳으로 이동해주세요.');
                return;
            }
            
            $sql = "DELETE FROM sys_menus WHERE id = :id";
            Database::execute($sql, [':id' => $id]);
            $this->apiSuccess(['message' => '메뉴가 삭제되었습니다.']);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}