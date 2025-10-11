<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\MenuRepository;
use App\Core\Request;

/**
 * A service dedicated to preparing common data needed for views.
 * This helps to decouple controllers from the data-fetching logic for layouts.
 */
class ViewDataService
{
    private AuthService $authService;
    private MenuRepository $menuRepository;

    public function __construct()
    {
        $this->authService = new AuthService();
        // MenuRepository requires a Database instance
        $db = \App\Core\Database::getInstance();
        $this->menuRepository = new MenuRepository($db);
    }

    /**
     * Gathers common data required by the main layout for all authenticated pages.
     * This includes user permissions, menu items, etc.
     *
     * @return array An array of data to be passed to the view.
     */
    public function getCommonData(): array
    {
        $user = $this->authService->user();
        if (!$user) {
            return []; // No common data for non-authenticated users
        }

        $userPermissions = $user['permissions'] ?? [];
        $currentUrlPath = Request::uri();

        $allMenus = $this->menuRepository->getAllVisibleMenus($userPermissions, $currentUrlPath);

        return [
            'userPermissions' => $userPermissions,
            'currentUrlPath' => $currentUrlPath,
            'allMenus' => $allMenus,
            'user' => $user // Pass user data to the view as well
        ];
    }
}
