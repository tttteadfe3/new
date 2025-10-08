<?php

namespace App\Controllers;

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
    public function organization(): string
    {
        $this->requireAuth('organization_admin');
        
        $pageTitle = "부서/직급 관리";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/organization_admin.js'
        ];
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        return $this->render('pages/admin/organization', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

    /**
     * 역할 및 권한 관리 페이지
     */
    public function rolePermissions(): string
    {
        $this->requireAuth('role_admin');
        
        $pageTitle = "역할 및 권한 관리";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/roles.js'
        ];
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        return $this->render('pages/admin/role-permissions', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

    /**
     * 사용자 관리 페이지
     */
    public function users(): string
    {
        $this->requireAuth('user_admin');
        
        $pageTitle = "사용자 관리";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/users.js'
        ];
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        return $this->render('pages/admin/users', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

    /**
     * 메뉴 관리 페이지
     */
    public function menus(): string
    {
        $this->requireAuth('menu_admin');
        
        $pageTitle = "메뉴 관리";
        
        // Log menu access
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);
        
        return $this->render('pages/admin/menus', [
            'pageTitle' => $pageTitle
        ]);
    }
}