<?php
/**
 * Bootstrap 스타일 메뉴 아이템을 재귀적으로 렌더링하는 함수 (개선된 버전)
 * @param array $items 렌더링할 메뉴 아이템 배열
 * @param int $level 메뉴 깊이 레벨
 */
function renderBootstrapMenuItems(array $items, int $level = 0) {
    foreach ($items as $index => $item) {
        $hasChildren = !empty($item['children']);
        
        // Generate unique IDs for collapsible elements
        $collapseId = 'sidebar' . ucfirst(str_replace([' ', '.', '-'], '', $item['name'])) . $level . $index;
        
        if ($hasChildren) {
            // 하위 메뉴가 있는 경우 - 접기/펼치기 가능한 메뉴
            echo '<li class="nav-item">';
            echo '<a class="nav-link menu-link" href="#' . $collapseId . '" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="' . $collapseId . '">';
            
            // Icon
            if (!empty($item['icon'])) {
                echo '<i class="' . e($item['icon']) . '"></i> ';
            }
            
            echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            echo '</a>';
            
            // Collapsible dropdown
            echo '<div class="collapse menu-dropdown" id="' . $collapseId . '">';
            echo '<ul class="nav nav-sm flex-column">';
            
            // Recursively render children
            renderBootstrapMenuItems($item['children'], $level + 1);
            
            echo '</ul>';
            echo '</div>';
            echo '</li>';
            
        } else {
            // 단일 메뉴 아이템
            echo '<li class="nav-item">';
            echo '<a href="' . BASE_URL . e($item['url']) . '" class="nav-link menu-link" data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">';
            
            // Icon (only for top level items without children)
            if (!empty($item['icon']) && $level === 0) {
                echo '<i class="' . e($item['icon']) . '"></i> ';
            }
            
            echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            echo '</a>';
            echo '</li>';
        }
    }
}

/**
 * Velzon 테마 스타일에 맞게 메뉴 아이템을 렌더링하는 함수
 * @param array $items 렌더링할 메뉴 아이템 배열
 * @param int $level 메뉴 깊이 레벨
 */
 function renderBootstrapMenuItemsAdvanced(array $items, int $level = 0) {
    foreach ($items as $index => $item) {
        $hasChildren = !empty($item['children']);
        
        // Collapse ID 생성
        $safeName = preg_replace('/[^a-zA-Z0-9]/', '', $item['name']);
        $collapseId = 'sidebar' . ucfirst($safeName) . ($item['id'] ?? uniqid());

        // --- 최상위 부모 메뉴 (menu-title) ---
        if ($level === 0 && $hasChildren) {
            echo '<li class="menu-title">';
            echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            echo '</li>';

            renderBootstrapMenuItemsAdvanced($item['children'], $level + 1);
        }

        // --- 하위 부모 메뉴 ---
        elseif ($hasChildren) {
            echo '<li class="nav-item">';

            // menu-link 클래스는 $level < 2일 때만 추가
            $linkClass = ($level < 2) ? 'menu-link' : '';
            
            echo '<a class="nav-link ' . $linkClass . '" href="#' . $collapseId . '" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="' . $collapseId . '">';

            if (!empty($item['icon']) && $level < 2) {
                echo '<i class="' . e($item['icon']) . '"></i> ';
            }

            if ($level < 2) {
                echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            } else {
                echo e($item['name']);
            }

            echo '</a>';

            echo '<div class="collapse menu-dropdown" id="' . $collapseId . '">';
            echo '<ul class="nav nav-sm flex-column">';
            renderBootstrapMenuItemsAdvanced($item['children'], $level + 1);
            echo '</ul>';
            echo '</div>';
            echo '</li>';
        }

        // --- 단일 메뉴 ---
        else {
            $url = (isset($item['url']) && $item['url'] !== '#') ? BASE_URL . e($item['url']) : 'javascript:void(0);';
            echo '<li class="nav-item">';

            // menu-link 클래스는 $level < 2일 때만 추가
            $linkClass = ($level < 2) ? 'menu-link' : '';
            
            echo '<a class="nav-link ' . $linkClass . '" href="' . $url . '">';

            if (!empty($item['icon']) && $level < 2) {
                echo '<i class="' . e($item['icon']) . '"></i> ';
            }

            if ($level < 2) {
                echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            } else {
                echo e($item['name']);
            }

            echo '</a>';
            echo '</li>';
        }
    }
}
?>
