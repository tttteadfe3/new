<?php

namespace App\Services;

use App\Core\Database;
use App\Core\SessionManager;
use App\Repositories\MenuRepository;
use App\Core\Request;

/**
 * A service dedicated to preparing common data needed for views.
 */
class ViewDataService
{
    private AuthService $authService;
    private MenuRepository $menuRepository;
    private SessionManager $sessionManager;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->sessionManager = new SessionManager();
        $db = \App\Core\Database::getInstance();
        $this->menuRepository = new MenuRepository($db);
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

        // Fetch flash messages and add them to the data array
        $flashSuccess = $this->sessionManager->getFlash('success');
        $flashError = $this->sessionManager->getFlash('error');

        return [
            'userPermissions' => $userPermissions,
            'currentUrlPath' => $currentUrlPath,
            'allMenus' => $allMenus,
            'user' => $user,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ];
    }
}
