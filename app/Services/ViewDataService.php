<?php

namespace App\Services;

use App\Repositories\MenuRepository;
use App\Core\Request;
// No longer using AuthManager, AuthService is instantiated directly.

/**
 * A service dedicated to preparing common data needed for views.
 * This helps to decouple controllers from the data-fetching logic for layouts.
 */
class ViewDataService
{
    /**
     * Gathers common data required by the main layout for all authenticated pages.
     * This includes user permissions, menu items, etc.
     *
     * @return array An array of data to be passed to the view.
     */
    public static function getCommonData(): array
    {
        $authService = new AuthService();
        $user = $authService->user();
        if (!$user) {
            return []; // No common data for non-authenticated users
        }

        $userPermissions = $user['permissions'] ?? [];
        $currentUrlPath = Request::uri();

        $currentTopMenuId = MenuRepository::getCurrentTopMenuId($userPermissions, $currentUrlPath);

        $sideMenuItems = [];
        if ($currentTopMenuId) {
            $sideMenuItems = MenuRepository::getSubMenus($currentTopMenuId, $userPermissions, $currentUrlPath);
        }

        return [
            'userPermissions' => $userPermissions,
            'currentUrlPath' => $currentUrlPath,
            'topLevelMenus' => MenuRepository::getTopLevelMenus($userPermissions, $currentUrlPath),
            'sideMenuItems' => $sideMenuItems,
            'currentTopMenuId' => $currentTopMenuId,
        ];
    }
}