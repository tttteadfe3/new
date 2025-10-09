<?php
// app/Repositories/MenuRepository.php
namespace App\Repositories;

use App\Core\Database;

class MenuRepository 
{
    /**
     * 사용자가 볼 수 있는 모든 메뉴를 가져옵니다.
     * @param array $userPermissions 사용자의 퍼미션 키 배열
     * @return array
     */
    private static function getVisibleMenus(array $userPermissions): array 
    {
        $sql = "SELECT * FROM sys_menus ORDER BY parent_id ASC, display_order ASC, name ASC";
        $allMenus = Database::query($sql);
        
        $visibleMenus = [];
        
        // 1단계: 리프 노드(자식 메뉴)부터 권한 체크
        foreach ($allMenus as $menu) {
            if (!empty($menu['parent_id'])) { // 자식 메뉴만
                if (empty($menu['permission_key']) || in_array($menu['permission_key'], $userPermissions)) {
                    $visibleMenus[$menu['id']] = $menu;
                }
            }
        }
        
        // 2단계: 보이는 자식이 있는 모든 상위 부모들을 반복적으로 추가
        $parentIdsToAdd = [];
        foreach ($visibleMenus as $menu) {
            if ($menu['parent_id']) {
                $parentIdsToAdd[$menu['parent_id']] = true;
            }
        }

        while (!empty($parentIdsToAdd)) {
            $newParentIds = [];
            foreach (array_keys($parentIdsToAdd) as $parentId) {
                if (isset($visibleMenus[$parentId])) {
                    continue; // 이미 처리된 부모는 건너뜁니다.
                }

                foreach ($allMenus as $parent) {
                    if ($parent['id'] == $parentId) {
                        // 부모의 권한을 확인하고 목록에 추가합니다.
                        if (empty($parent['permission_key']) || in_array($parent['permission_key'], $userPermissions)) {
                            $visibleMenus[$parent['id']] = $parent;
                            // 이 부모에게 또 다른 부모가 있다면, 다음 반복에서 처리하도록 큐에 추가합니다.
                            if ($parent['parent_id']) {
                                $newParentIds[$parent['parent_id']] = true;
                            }
                        }
                        break; // 해당 부모를 찾았으므로 내부 루프를 중단합니다.
                    }
                }
            }
            $parentIdsToAdd = $newParentIds;
        }
        
        // 3단계: 독립 메뉴들 (parent_id가 없고 자식도 없는) 권한 체크해서 추가
        foreach ($allMenus as $menu) {
            if (empty($menu['parent_id'])) {
                $hasChildren = false;
                foreach ($allMenus as $child) {
                    if ($child['parent_id'] == $menu['id']) {
                        $hasChildren = true;
                        break;
                    }
                }
                
                // 자식이 없는 독립 메뉴만 권한 체크
                if (!$hasChildren) {
                    if (empty($menu['permission_key']) || in_array($menu['permission_key'], $userPermissions)) {
                        $visibleMenus[$menu['id']] = $menu;
                    }
                }
            }
        }
        
        // 4단계: display_order 기준으로 정렬
        $result = array_values($visibleMenus);
        usort($result, function($a, $b) {
            // 부모가 같으면 display_order로 정렬
            if ($a['parent_id'] == $b['parent_id']) {
                return $a['display_order'] <=> $b['display_order'];
            }
            // 부모가 다르면 부모 기준으로 정렬 (null은 0으로 처리)
            return ($a['parent_id'] ?? 0) <=> ($b['parent_id'] ?? 0);
        });
        
        return $result;
    }
    
    /**
     * 하단 고정 메뉴용 - 최상위 메뉴만 가져옵니다.
     * @param array $userPermissions 사용자의 퍼미션 키 배열
     * @param string $currentUrl 현재 URL (active 상태 표시용)
     * @return array
     */
    public static function getTopLevelMenus(array $userPermissions, string $currentUrl = ''): array 
    {
        $visibleMenus = self::getVisibleMenus($userPermissions);
        
        $topMenus = [];
        foreach ($visibleMenus as $menu) {
            if (is_null($menu['parent_id'])) {
                $menu['is_active'] = self::isMenuActive($menu, $currentUrl);
                $hasChildren = self::hasVisibleChildren($menu['id'], $visibleMenus);
                $menu['has_children'] = $hasChildren;

                if ($hasChildren && (!isset($menu['url']) || $menu['url'] === '#')) {
                    $firstChildUrl = self::findFirstVisibleChildUrl($menu['id'], $visibleMenus);
                    if ($firstChildUrl) {
                        $menu['url'] = $firstChildUrl;
                    }
                }
                
                $topMenus[] = $menu;
            }
        }
        
        return $topMenus;
    }
    
    /**
     * 좌측 메뉴용 - 특정 최상위 메뉴의 하위메뉴만 계층구조로 가져옵니다.
     * @param int $parentMenuId 최상위 메뉴 ID
     * @param array $userPermissions 사용자의 퍼미션 키 배열
     * @param string $currentUrl 현재 URL (active 상태 표시용)
     * @return array
     */
    public static function getSubMenus(int $parentMenuId, array $userPermissions, string $currentUrl = ''): array 
    {
        $visibleMenus = self::getVisibleMenus($userPermissions);
        
        // 재귀적으로 모든 하위 메뉴 ID들을 찾는 함수
        $getAllDescendantIds = function($parentId, $menus) use (&$getAllDescendantIds) {
            $descendants = [];
            foreach ($menus as $menu) {
                if ($menu['parent_id'] == $parentId) {
                    $descendants[] = $menu['id'];
                    // 재귀적으로 하위 메뉴들도 찾기
                    $descendants = array_merge($descendants, $getAllDescendantIds($menu['id'], $menus));
                }
            }
            return $descendants;
        };
        
        // 특정 부모 메뉴의 모든 하위 메뉴 ID들 가져오기
        $allDescendantIds = $getAllDescendantIds($parentMenuId, $visibleMenus);
        
        // 해당 하위 메뉴들만 필터링
        $subMenus = [];
        foreach ($visibleMenus as $menu) {
            if (in_array($menu['id'], $allDescendantIds)) {
                $subMenus[] = $menu;
            }
        }
        
        // 하위메뉴들을 계층 구조로 재구성
        $tree = [];
        $references = [];
        
        // 먼저 모든 노드의 참조를 생성
        foreach ($subMenus as $key => &$node) {
            $node['is_active'] = self::isMenuActive($node, $currentUrl);
            $node['has_children'] = self::hasVisibleChildren($node['id'], $visibleMenus);
            $node['children'] = []; // children 배열 초기화
            $references[$node['id']] = &$node;
        }
        
        // 계층 구조 구성
        foreach ($subMenus as $key => &$node) {
            if ($node['parent_id'] == $parentMenuId) {
                // 직접적인 하위 메뉴 (2차 메뉴)
                $tree[$node['id']] = &$node;
            } else {
                // 더 깊은 하위 메뉴 (3차, 4차 등)
                if (isset($references[$node['parent_id']])) {
                    $references[$node['parent_id']]['children'][$node['id']] = &$node;
                }
            }
        }
        
        return $tree;
    }
    
/**
 * 현재 활성화된 최상위 메뉴 ID를 가져옵니다.
 * @param array $userPermissions 사용자의 퍼미션 키 배열
 * @param string $currentUrl 현재 URL
 * @return int|null
 */
public static function getCurrentTopMenuId(array $userPermissions, string $currentUrl): ?int 
{
    $visibleMenus = self::getVisibleMenus($userPermissions);
    
    // 정확한 매칭을 위해 모든 메뉴를 검사하고 가장 구체적인 매칭을 찾습니다
    $matchedMenus = [];
    
    foreach ($visibleMenus as $menu) {
        if (self::isMenuActive($menu, $currentUrl)) {
            $matchedMenus[] = $menu;
        }
    }
    
    if (empty($matchedMenus)) {
        return null;
    }
    
    // 가장 구체적인 매칭(URL이 가장 긴 것)을 선택
    $currentMenu = null;
    $longestUrlLength = 0;
    
    foreach ($matchedMenus as $menu) {
        $urlLength = strlen(trim($menu['url'], '/'));
        if ($urlLength > $longestUrlLength) {
            $longestUrlLength = $urlLength;
            $currentMenu = $menu;
        }
    }
    
    if (!$currentMenu) {
        return null;
    }
    
    // 최상위 메뉴까지 올라가기
    $topMenuId = $currentMenu['id'];
    $parentId = $currentMenu['parent_id'];
    
    while ($parentId !== null) {
        foreach ($visibleMenus as $menu) {
            if ($menu['id'] == $parentId) {
                $topMenuId = $menu['id'];
                $parentId = $menu['parent_id'];
                break;
            }
        }
    }
    
    return $topMenuId;
}

/**
 * 메뉴가 현재 활성 상태인지 확인합니다. (개선된 버전)
 * @param array $menu 메뉴 정보
 * @param string $currentUrl 현재 URL
 * @return bool
 */
private static function isMenuActive(array $menu, string $currentUrl): bool 
{
    if (empty($currentUrl) || empty($menu['url'])) {
        return false;
    }
    
    // URL 정규화 (앞뒤 슬래시 제거)
    $currentUrl = trim($currentUrl, '/');
    $menuUrl = trim($menu['url'], '/');
    
    // 빈 URL이면 매칭하지 않음
    if (empty($menuUrl)) {
        return false;
    }
    
    // 정확한 일치 (우선순위 1)
    if ($currentUrl === $menuUrl) {
        return true;
    }
    
    // 하위 경로 포함 체크 (우선순위 2)
    // 단, 메뉴 URL이 현재 URL보다 짧을 때만
    if (strlen($menuUrl) < strlen($currentUrl) && 
        strpos($currentUrl, $menuUrl . '/') === 0) {
        return true;
    }
    
    return false;
}
    
    /**
     * 메뉴가 보이는 하위 메뉴를 가지고 있는지 확인합니다.
     * @param int $menuId 메뉴 ID
     * @param array $visibleMenus 보이는 메뉴 목록
     * @return bool
     */
    private static function hasVisibleChildren(int $menuId, array $visibleMenus): bool 
    {
        foreach ($visibleMenus as $menu) {
            if ($menu['parent_id'] == $menuId) {
                return true;
            }
        }
        return false;
    }

    private static function findFirstVisibleChildUrl(int $parentId, array $visibleMenus): ?string
    {
        foreach ($visibleMenus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                // Since visibleMenus is sorted by display_order, this is the first child.
                // If this child also has children, recurse to find the first leaf.
                if (self::hasVisibleChildren($menu['id'], $visibleMenus)) {
                    $descendantUrl = self::findFirstVisibleChildUrl($menu['id'], $visibleMenus);
                    // If a valid URL is found in the descendants, return it.
                    if ($descendantUrl) {
                        return $descendantUrl;
                    }
                }
                
                // If it's a leaf or its descendants have no valid URL, return this menu's URL.
                // But only if it's not a placeholder.
                if (isset($menu['url']) && $menu['url'] !== '#') {
                    return $menu['url'];
                }
            }
        }
        return null;
    }

    /**
     * 메뉴 생성
     */
    public static function create(array $menuData): string {
        $sql = "INSERT INTO sys_menus (name, url, icon, parent_id, display_order, permission_key) 
                VALUES (:name, :url, :icon, :parent_id, :display_order, :permission_key)";
        Database::execute($sql, [
            ':name' => $menuData['name'],
            ':url' => $menuData['url'] ?? null,
            ':icon' => $menuData['icon'] ?? null,
            ':parent_id' => $menuData['parent_id'] ?? null,
            ':display_order' => $menuData['display_order'] ?? 0,
            ':permission_key' => $menuData['permission_key'] ?? null
        ]);
        return Database::lastInsertId();
    }

    /**
     * 메뉴 수정
     */
    public static function update(int $id, array $menuData): bool {
        $sql = "UPDATE sys_menus SET 
                name = :name, 
                url = :url, 
                icon = :icon, 
                parent_id = :parent_id, 
                display_order = :display_order, 
                permission_key = :permission_key 
                WHERE id = :id";
        return Database::execute($sql, [
            ':id' => $id,
            ':name' => $menuData['name'],
            ':url' => $menuData['url'] ?? null,
            ':icon' => $menuData['icon'] ?? null,
            ':parent_id' => $menuData['parent_id'] ?? null,
            ':display_order' => $menuData['display_order'] ?? 0,
            ':permission_key' => $menuData['permission_key'] ?? null
        ]);
    }

    /**
     * 메뉴 삭제
     */
    public static function delete(int $id): bool {
        // 하위 메뉴가 있는지 확인
        $hasChildren = Database::fetchOne("SELECT 1 FROM sys_menus WHERE parent_id = :id LIMIT 1", [':id' => $id]);
        if ($hasChildren) {
            return false; // 하위 메뉴가 있으면 삭제 불가
        }
        
        return Database::execute("DELETE FROM sys_menus WHERE id = :id", [':id' => $id]);
    }

    /**
     * 메뉴 조회
     */
    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM sys_menus WHERE id = :id", [':id' => $id]);
    }

    /**
     * 메뉴 순서 변경
     */
    public static function updateDisplayOrder(int $id, int $displayOrder): bool {
        $sql = "UPDATE sys_menus SET display_order = :display_order WHERE id = :id";
        return Database::execute($sql, [':id' => $id, ':display_order' => $displayOrder]);
    }

    /**
     * 부모 메뉴 변경
     */
    public static function updateParent(int $id, ?int $parentId): bool {
        $sql = "UPDATE sys_menus SET parent_id = :parent_id WHERE id = :id";
        return Database::execute($sql, [':id' => $id, ':parent_id' => $parentId]);
    }
    
}