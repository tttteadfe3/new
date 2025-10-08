<?php

// All web application routes are defined here.
// The new router requires an explicit HTTP method (GET, POST, etc.) for each route.
return [
    'GET ' => 'AuthController@login',
    // Authentication routes
    'GET login' => 'AuthController@login',
    'GET auth/kakao/callback' => 'AuthController@kakaoCallback',
    'GET logout' => 'AuthController@logout',

    // Dashboard and status
    'GET dashboard' => 'DashboardController@index',
    'GET status' => 'StatusController@index',

    // Employee management routes - Note: These were mixed before. Now separated.
    'GET employees' => 'EmployeeController@index',
    'GET employees/create' => 'EmployeeController@create',
    'GET employees/edit' => 'EmployeeController@edit',
    // POST routes for data manipulation are now handled by the API controller.
    // These web routes were pointing to methods that have been removed.
    // 'POST employees/store' => 'EmployeeController@store',
    // 'POST employees/update' => 'EmployeeController@update',
    // 'POST employees/delete' => 'EmployeeController@delete',

    // Holiday management routes
    'GET holidays' => 'HolidayController@index',
    // create, store, edit, update, delete are now handled by API and JS on the index page.

    // Leave management routes
    'GET leaves' => 'LeaveController@index',
    'GET leaves/my' => 'LeaveController@my',
    'GET leaves/approval' => 'LeaveController@approval',
    'GET leaves/granting' => 'LeaveController@granting',
    'GET leaves/history' => 'LeaveController@history',
    // Actions like store, approve, reject, grant are now handled by the API.

    // Littering management routes
    'GET littering' => 'LitteringController@index',
    'GET littering/map' => 'LitteringController@map',
    'GET littering/history' => 'LitteringController@history',
    'GET littering/deleted' => 'LitteringController@deleted',
    'GET littering/create' => 'LitteringController@create',
    'GET littering/edit' => 'LitteringController@edit',
    // Actions like store, update, delete, restore are now handled by the API.

    // Waste collection routes
    'GET waste' => 'WasteCollectionController@index',
    'GET waste/collection' => 'WasteCollectionController@collection',
    'GET waste/admin' => 'WasteCollectionController@admin',
    
    // Legacy page routes for waste (for menu compatibility)
    'GET pages/waste_collection.php' => 'WasteCollectionController@index',
    'GET pages/waste_admin.php' => 'WasteCollectionController@admin',

    // Admin routes
    'GET admin/organization' => 'AdminController@organization',
    'GET admin/role-permissions' => 'AdminController@rolePermissions',
    'GET admin/users' => 'AdminController@users',
    'GET admin/menus' => 'AdminController@menus',
    
    // Legacy admin routes (for backward compatibility)
    'GET pages/organization_admin.php' => 'AdminController@organization',
    'GET pages/role_permission_admin.php' => 'AdminController@rolePermissions',
    'GET pages/user_list.php' => 'AdminController@users',
    'GET pages/menu_admin.php' => 'AdminController@menus',

    // Profile and logs
    'GET profile' => 'ProfileController@index',
    'GET logs' => 'LogController@index',

    // Utility routes
    'GET blank' => 'UtilityController@blank',

    // Temporary route for testing
    'GET test-login' => 'TestLoginController@login',
];