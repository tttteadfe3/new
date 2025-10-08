<?php
/**
 * Bootstrap 스타일 메뉴 아이템을 재귀적으로 렌더링하는 함수 (개선된 버전)
 * @param array $items 렌더링할 메뉴 아이템 배열
 * @param int $level 메뉴 깊이 레벨
 */
function renderBootstrapMenuItems(array $items, int $level = 0) {
    foreach ($items as $index => $item) {
        $hasChildren = !empty($item['children']);
        $isActive = $item['is_active'] ?? false;
        $hasVisibleChildren = $item['has_children'] ?? false;
        
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
            $activeClass = $isActive ? ' active' : '';
            echo '<li class="nav-item">';
            echo '<a href="' . BASE_URL . e($item['url']) . '" class="nav-link menu-link' . $activeClass . '" data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">';
            
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
 * 고급 Bootstrap 스타일 메뉴 아이템 렌더링 함수 (아이콘 기본값 포함)
 * @param array $items 렌더링할 메뉴 아이템 배열
 * @param int $level 메뉴 깊이 레벨
 */
function renderBootstrapMenuItemsAdvanced(array $items, int $level = 0) {
    foreach ($items as $index => $item) {
        $hasChildren = !empty($item['children']);
        $isActive = $item['is_active'] ?? false;
        $hasVisibleChildren = $item['has_children'] ?? false;
        
        // Generate unique IDs for collapsible elements
        $collapseId = 'sidebar' . ucfirst(str_replace([' ', '.', '-'], '', $item['name'])) . $level . $index;
        
        if ($hasChildren) {
            // 하위 메뉴가 있는 경우
            $expandedClass = $isActive ? '' : 'collapsed';
            $ariaExpanded = $isActive ? 'true' : 'false';
            $showClass = $isActive ? 'show' : '';
            
            echo '<li class="nav-item">';
            echo '<a class="nav-link menu-link ' . $expandedClass . '" href="#' . $collapseId . '" data-bs-toggle="collapse" role="button" aria-expanded="' . $ariaExpanded . '" aria-controls="' . $collapseId . '">';
            
            // Icon with default fallback
            $iconClass = !empty($item['icon']) ? $item['icon'] : 'ri-folder-line';
            echo '<i class="' . e($iconClass) . '"></i> ';
            
            echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            echo '</a>';
            
            // Collapsible dropdown - 활성 상태면 기본적으로 펼쳐짐
            echo '<div class="collapse menu-dropdown ' . $showClass . '" id="' . $collapseId . '">';
            echo '<ul class="nav nav-sm flex-column">';
            
            // Recursively render children
            renderBootstrapMenuItemsAdvanced($item['children'], $level + 1);
            
            echo '</ul>';
            echo '</div>';
            echo '</li>';
            
        } else {
            // 단일 메뉴 아이템
            $activeClass = $isActive ? ' active' : '';
            echo '<li class="nav-item">';
            echo '<a href="' . BASE_URL . e($item['url']) . '" class="nav-link menu-link' . $activeClass . '" data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">';
            
            // Icon for top level or if specified
            if (!empty($item['icon'])) {
                echo '<i class="' . e($item['icon']) . '"></i> ';
            } elseif ($level === 0) {
                echo '<i class="ri-file-line"></i> ';
            }
            
            echo '<span data-key="t-' . strtolower(str_replace(' ', '-', $item['name'])) . '">' . e($item['name']) . '</span>';
            echo '</a>';
            echo '</li>';
        }
    }
}
?>