<?php

namespace App\Controllers\Web;

use App\Services\OrganizationService;
use App\Services\RolePermissionService;
use App\Services\UserManagementService;
use App\Services\MenuManagementService;

class AdminController extends BaseController
{
    private OrganizationService $organizationService;
    private RolePermissionService $rolePermissionService;
    private UserManagementService $userManagementService;
    private MenuManagementService $menuManagementService;

    public function __construct()
    {
        parent::__construct();
        $this->organizationService = new OrganizationService();
        $this->rolePermissionService = new RolePermissionService();
        $this->userManagementService = new UserManagementService();
        $this->menuManagementService = new MenuManagementService();
    }

    /**
     * 부서/직급 관리 페이지
     */
    public function organization(): void
    {
        $this->requireAuth('organization_admin');
        
        $pageTitle = "부서/직급 관리";
        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/pages/organization-admin-app.js');
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/organization', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 역할 및 권한 관리 페이지
     */
    public function rolePermissions(): void
    {
        $this->requireAuth('role_admin');
        
        $pageTitle = "역할 및 권한 관리";
        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/pages/roles-app.js');
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/role-permissions', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 사용자 관리 페이지
     */
    public function users(): void
    {
        $this->requireAuth('user_admin');
        
        $pageTitle = "사용자 관리";
        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/pages/users-app.js');
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/users', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

    /**
     * 메뉴 관리 페이지
     */
    public function menus(): void
    {
        $this->requireAuth('menu_admin');
        
        $pageTitle = "메뉴 관리";
        \App\Core\View::addJs("https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/menu-admin-app.js");
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        echo $this->render('pages/admin/menus', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}