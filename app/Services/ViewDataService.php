<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\MenuRepository;
use App\Core\Request;
use App\Services\ActivityLogger;

/**
 * 뷰에 필요한 공통 데이터를 준비하는 전용 서비스입니다.
 */
class ViewDataService
{
    private AuthService $authService;
    private MenuRepository $menuRepository;
    private SessionManager $sessionManager;
    private ActivityLogger $activityLogger;

    public function __construct(
        AuthService $authService,
        SessionManager $sessionManager,
        MenuRepository $menuRepository,
        ActivityLogger $activityLogger
    ) {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
        $this->menuRepository = $menuRepository;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 모든 인증된 페이지의 기본 레이아웃에 필요한 공통 데이터를 수집합니다.
     * @return array
     */
    public function getCommonData(): array
    {
        $user = $this->authService->user();
        if (!$user) {
            return [];
        }

        $userPermissions = $user['permissions'] ?? [];
        $currentUrlPath = Request::uri();
        $allMenus = $this->menuRepository->getAllVisibleMenus($userPermissions, $currentUrlPath);

        // 활성 메뉴를 찾아 페이지 제목을 결정합니다.
        $pageTitle = $this->findActiveMenuName($allMenus);

        // 플래시 메시지를 가져와 데이터 배열에 추가합니다.
        $flashSuccess = $this->sessionManager->getFlash('success');
        $flashError = $this->sessionManager->getFlash('error');

        return [
            'userPermissions' => $userPermissions,
            'currentUrlPath' => $currentUrlPath,
            'allMenus' => $allMenus,
            'user' => $user,
            'pageTitle' => $pageTitle,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ];
    }

    /**
     * 활성 메뉴 항목의 이름을 재귀적으로 찾습니다.
     *
     * @param array $menus 메뉴 트리
     * @return string|null 활성 메뉴의 이름 또는 찾을 수 없는 경우 null
     */
    private function findActiveMenuName(array $menus): ?string
    {
        foreach ($menus as $menu) {
            if ($menu['is_active']) {
                // 활성 메뉴에 자식이 있는 경우 더 구체적인 활성 자식을 찾습니다.
                $activeChildName = $this->findActiveMenuName($menu['children']);
                return $activeChildName ?? $menu['name'];
            }
        }
        return null;
    }
}
