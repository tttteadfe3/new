<?php
/**
 * 하단 Footer 메뉴를 렌더링하는 함수
 * @param array $topMenus 최상위 메뉴 배열
 * @param int $maxItems 최대 표시할 메뉴 개수 (기본값: 4)
 */
function renderFooterMenu(array $topMenus, int $maxItems = 5) {
    if (empty($topMenus)) {
        return;
    }
    
    // 최대 개수만큼만 표시
    $displayMenus = array_slice($topMenus, 0, $maxItems);
    
    foreach ($displayMenus as $menu) {
        $activeClass = isset($menu['is_active']) && $menu['is_active'] ? ' active' : '';
        if($menu['url'] != "#"){
            $url = BASE_URL . $menu['url'];
        }
        else {
            $url = $menu['url'];
        }
        
        echo '<a href="' . e($url) . '" class="item' . $activeClass . '">';
        echo '<div class="col">';
        
        // 아이콘 처리 - 없으면 기본 아이콘 사용
        $iconClass = !empty($menu['icon']) ? $menu['icon'] : 'las la-circle';
        echo '<i class="icon ' . e($iconClass) . '"></i>';
        
        // 메뉴명
        echo '<strong>' . e($menu['name']) . '</strong>';
        
        echo '</div>';
        echo '</a>';
    }
}

/**
 * 고급 Footer 메뉴 렌더링 함수 (더 많은 옵션 포함)
 * @param array $topMenus 최상위 메뉴 배열
 * @param array $options 옵션 배열
 */
function renderFooterMenuAdvanced(array $topMenus, array $options = []) {
    // 기본 옵션 설정
    $defaultOptions = [
        'max_items' => 4,
        'show_badge' => true,        // 하위메뉴 표시 여부
        'show_labels' => true,       // 라벨 표시 여부
        'default_icon' => 'las la-circle',  // 기본 아이콘
        'active_class' => 'active',  // 활성 상태 CSS 클래스
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    if (empty($topMenus)) {
        return;
    }
    
    // 최대 개수만큼만 표시
    $displayMenus = array_slice($topMenus, 0, $options['max_items']);
    
    foreach ($displayMenus as $menu) {
        $activeClass = isset($menu['is_active']) && $menu['is_active'] ? ' ' . $options['active_class'] : '';
        $url = BASE_URL . ltrim($menu['url'], '/');
        
        echo '<a href="' . e($url) . '" class="item' . $activeClass . '">';
        echo '<div class="col">';
        
        // 아이콘 처리
        $iconClass = !empty($menu['icon']) ? $menu['icon'] : $options['default_icon'];
        echo '<i class="icon ' . e($iconClass) . '"></i>';
        
        // 라벨 표시
        if ($options['show_labels']) {
            echo '<strong>' . e($menu['name']) . '</strong>';
        }
        
        // 하위메뉴 배지 표시
        if ($options['show_badge'] && isset($menu['has_children']) && $menu['has_children']) {
            echo '<small class="badge">•</small>';
        }
        
        echo '</div>';
        echo '</a>';
    }
}

/**
 * 모바일 최적화된 Footer 메뉴 렌더링 함수
 * @param array $topMenus 최상위 메뉴 배열
 * @param string $currentUrl 현재 URL
 */
function renderMobileFooterMenu(array $topMenus, string $currentUrl = '') {
    if (empty($topMenus)) {
        return;
    }
    
    // 모바일에서는 보통 4-5개 메뉴가 적당
    $displayMenus = array_slice($topMenus, 0, 5);
    
    foreach ($displayMenus as $index => $menu) {
        $activeClass = isset($menu['is_active']) && $menu['is_active'] ? ' active' : '';
        $url = BASE_URL . ltrim($menu['url'], '/');
        
        // 메뉴명을 모바일에 맞게 줄임
        $shortName = mb_strlen($menu['name']) > 6 ? mb_substr($menu['name'], 0, 6) . '..' : $menu['name'];
        
        echo '<a href="' . e($url) . '" class="item' . $activeClass . '" data-menu-id="' . $menu['id'] . '">';
        echo '<div class="col">';
        
        // 아이콘
        $iconClass = !empty($menu['icon']) ? $menu['icon'] : 'las la-circle';
        echo '<i class="icon ' . e($iconClass) . '"></i>';
        
        // 짧은 메뉴명
        echo '<strong>' . e($shortName) . '</strong>';
        
        // 알림이나 배지 (하위메뉴 있음을 표시)
        if (isset($menu['has_children']) && $menu['has_children']) {
            echo '<span class="indicator"></span>';
        }
        
        echo '</div>';
        echo '</a>';
    }
}

/**
 * 아이콘만 있는 간단한 Footer 메뉴 렌더링 함수
 * @param array $topMenus 최상위 메뉴 배열
 * @param bool $showTooltip 툴팁 표시 여부
 */
function renderIconOnlyFooterMenu(array $topMenus, bool $showTooltip = true) {
    if (empty($topMenus)) {
        return;
    }
    
    $displayMenus = array_slice($topMenus, 0, 5);
    
    foreach ($displayMenus as $menu) {
        $activeClass = isset($menu['is_active']) && $menu['is_active'] ? ' active' : '';
        $url = BASE_URL . ltrim($menu['url'], '/');
        $tooltipAttr = $showTooltip ? ' title="' . e($menu['name']) . '" data-bs-toggle="tooltip"' : '';
        
        echo '<a href="' . e($url) . '" class="item' . $activeClass . '"' . $tooltipAttr . '>';
        echo '<div class="col">';
        
        // 아이콘만 표시
        $iconClass = !empty($menu['icon']) ? $menu['icon'] : 'las la-circle';
        echo '<i class="icon ' . e($iconClass) . '"></i>';
        
        // 하위메뉴 인디케이터
        if (isset($menu['has_children']) && $menu['has_children']) {
            echo '<span class="sub-indicator"></span>';
        }
        
        echo '</div>';
        echo '</a>';
    }
}

/**
 * Footer 메뉴 컨테이너까지 포함한 완전한 렌더링 함수
 * @param array $topMenus 최상위 메뉴 배열
 * @param array $options 옵션 배열
 */
function renderCompleteFooterMenu(array $topMenus, array $options = []) {
    $defaultOptions = [
        'container_class' => 'footer',
        'max_items' => 4,
        'style' => 'default', // 'default', 'mobile', 'icon-only'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    if (empty($topMenus)) {
        return;
    }
    
    echo '<footer class="' . e($options['container_class']) . '">';
    
    switch ($options['style']) {
        case 'mobile':
            renderMobileFooterMenu($topMenus);
            break;
        case 'icon-only':
            renderIconOnlyFooterMenu($topMenus);
            break;
        default:
            renderFooterMenu($topMenus, $options['max_items']);
            break;
    }
    
    echo '</footer>';
}
?>