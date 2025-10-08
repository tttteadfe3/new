<?php

// All web application routes are defined here.
return [
    '' => 'AuthController@login',
    // Authentication routes
    'login' => 'AuthController@login',
    'auth/kakao/callback' => 'AuthController@kakaoCallback',
    'logout' => 'AuthController@logout',

    // Dashboard and status
    'dashboard' => 'DashboardController@index',
    'status' => 'StatusController@index',

    // Employee management routes
    'employees' => 'EmployeeController@index',
    'employees/create' => 'EmployeeController@create',
    'employees/store' => 'EmployeeController@store',
    'employees/edit' => 'EmployeeController@edit',
    'employees/update' => 'EmployeeController@update',
    'employees/delete' => 'EmployeeController@delete',

    // Holiday management routes
    'holidays' => 'HolidayController@index',
    'holidays/create' => 'HolidayController@create',
    'holidays/store' => 'HolidayController@store',
    'holidays/edit' => 'HolidayController@edit',
    'holidays/update' => 'HolidayController@update',
    'holidays/delete' => 'HolidayController@delete',

    // Leave management routes
    'leaves' => 'LeaveController@index',
    'leaves/my' => 'LeaveController@my',
    'leaves/create' => 'LeaveController@create',
    'leaves/store' => 'LeaveController@store',
    'leaves/approval' => 'LeaveController@approval',
    'leaves/approve' => 'LeaveController@approve',
    'leaves/reject' => 'LeaveController@reject',
    'leaves/granting' => 'LeaveController@granting',
    'leaves/grant' => 'LeaveController@grant',
    'leaves/history' => 'LeaveController@history',

    // Littering management routes
    'littering' => 'LitteringController@index',
    'littering/map' => 'LitteringController@map',
    'littering/history' => 'LitteringController@history',
    'littering/deleted' => 'LitteringController@deleted',
    'littering/create' => 'LitteringController@create',
    'littering/store' => 'LitteringController@store',
    'littering/edit' => 'LitteringController@edit',
    'littering/update' => 'LitteringController@update',
    'littering/delete' => 'LitteringController@delete',
    'littering/restore' => 'LitteringController@restore',

    // Waste collection routes
    'waste' => 'WasteCollectionController@index',
    'waste/collection' => 'WasteCollectionController@collection',
    'waste/admin' => 'WasteCollectionController@admin',
    'waste/create' => 'WasteCollectionController@create',
    'waste/store' => 'WasteCollectionController@store',
    'waste/edit' => 'WasteCollectionController@edit',
    'waste/update' => 'WasteCollectionController@update',
    'waste/delete' => 'WasteCollectionController@delete',
    
    // Legacy page routes for waste (for menu compatibility)
    'pages/waste_collection.php' => 'WasteCollectionController@index',
    'pages/waste_admin.php' => 'WasteCollectionController@admin',

    // Admin routes
    'admin/organization' => 'AdminController@organization',
    'admin/role-permissions' => 'AdminController@rolePermissions',
    'admin/users' => 'AdminController@users',
    'admin/menus' => 'AdminController@menus',
    
    // Legacy admin routes (for backward compatibility)
    'pages/organization_admin.php' => 'AdminController@organization',
    'pages/role_permission_admin.php' => 'AdminController@rolePermissions',
    'pages/user_list.php' => 'AdminController@users',
    'pages/menu_admin.php' => 'AdminController@menus',

    // Profile and logs
    'profile' => 'ProfileController@index',
    'profile/update' => 'ProfileController@update',
    'logs' => 'LogController@index',

    // Utility routes
    'blank' => 'UtilityController@blank',
];