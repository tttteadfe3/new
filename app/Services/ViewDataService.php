<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\MenuRepository;
use App\Core\Request;
use App\Services\ActivityLogger;

/**
 * A service dedicated to preparing common data needed for views.
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
     * Gathers common data required by the main layout for all authenticated pages.
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

        // Find the active menu to determine the page title
        $pageTitle = $this->findActiveMenuName($allMenus);
        if ($pageTitle) {
            $this->activityLogger->logMenuAccess($pageTitle);
        }

        // Fetch flash messages and add them to the data array
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
     * Recursively find the name of the active menu item.
     *
     * @param array $menus The menu tree
     * @return string|null The name of the active menu or null if not found
     */
    private function findActiveMenuName(array $menus): ?string
    {
        foreach ($menus as $menu) {
            if ($menu['is_active']) {
                // If the active menu has children, look for a more specific active child
                $activeChildName = $this->findActiveMenuName($menu['children']);
                return $activeChildName ?? $menu['name'];
            }
        }
        return null;
    }
}
