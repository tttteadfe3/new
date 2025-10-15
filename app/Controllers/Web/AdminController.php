<?php

namespace App\Controllers\Web;

use App\Services\OrganizationService;
use App\Services\RolePermissionService;
use App\Services\UserService;
use App\Services\MenuManagementService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class AdminController extends BaseController
{
    private OrganizationService $organizationService;
    private RolePermissionService $rolePermissionService;
    private UserService $userService;
    private MenuManagementService $menuManagementService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        OrganizationService $organizationService,
        RolePermissionService $rolePermissionService,
        UserService $userService,
        MenuManagementService $menuManagementService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->organizationService = $organizationService;
        $this->rolePermissionService = $rolePermissionService;
        $this->userService = $userService;
        $this->menuManagementService = $menuManagementService;
    }

    /**
     * 부서/직급 관리 페이지
     */
    public function organization(): void
    {
        $pageTitle = "부서/직급 관리";

        // Add Choices.js CSS and JS
        View::getInstance()->addCss('https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
        View::getInstance()->addJs('https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js');

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/organization-admin.js');
        
        // Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/organization', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 역할 및 권한 관리 페이지
     */
    public function rolePermissions(): void
    {
        $pageTitle = "역할 및 권한 관리";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/roles.js');
        
        // Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/role-permissions', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 사용자 관리 페이지
     */
    public function users(): void
    {
        $pageTitle = "사용자 관리";
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/users.js');
        
        // Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/users', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 메뉴 관리 페이지
     */
    public function menus(): void
    {
        $pageTitle = "메뉴 관리";
        View::getInstance()->addJs("https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js");
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/menu-admin.js");
        
        // Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/menus', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}
