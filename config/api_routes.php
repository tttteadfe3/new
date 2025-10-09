<?php

// All API routes are defined here.
// These routes are prefixed with 'api/' automatically by the Router
return [
    // Employee API routes
    'GET employees' => 'EmployeeApiController@index',
    'GET employees/unlinked' => 'EmployeeApiController@unlinked',
    
    // Holiday API routes
    'GET holidays' => 'HolidayApiController@index',
    'POST holidays' => 'HolidayApiController@store',
    'GET holidays/{id}' => 'HolidayApiController@show',
    'PUT holidays/{id}' => 'HolidayApiController@update',
    'DELETE holidays/{id}' => 'HolidayApiController@destroy',
    
    // Leave API routes (user)
    'GET leaves' => 'LeaveApiController@index',
    'POST leaves' => 'LeaveApiController@store',
    'POST leaves/{id}/cancel' => 'LeaveApiController@cancel',
    'POST leaves/calculate-days' => 'LeaveApiController@calculateDays',

    // Leave Admin API routes
    'GET leaves_admin/requests' => 'LeaveAdminApiController@listRequests',
    'POST leaves_admin/requests/{id}/approve' => 'LeaveAdminApiController@approveRequest',
    'POST leaves_admin/requests/{id}/reject' => 'LeaveAdminApiController@rejectRequest',
    'POST leaves_admin/cancellations/{id}/approve' => 'LeaveAdminApiController@approveCancellation',
    'POST leaves_admin/cancellations/{id}/reject' => 'LeaveAdminApiController@rejectCancellation',
    'GET leaves_admin/entitlements' => 'LeaveAdminApiController@listEntitlements',
    'POST leaves_admin/grant-all' => 'LeaveAdminApiController@grantForAll',
    'GET leaves_admin/history/{employeeId}' => 'LeaveAdminApiController@getHistory',
    'POST leaves_admin/adjust' => 'LeaveAdminApiController@manualAdjustment',
    'POST leaves_admin/calculate' => 'LeaveAdminApiController@calculateLeaves',
    'POST leaves_admin/save-entitlements' => 'LeaveAdminApiController@saveEntitlements',
    
    // Littering API routes (user-facing for map, history, etc.)
    'GET littering' => 'LitteringApiController@index',
    'POST littering' => 'LitteringApiController@store',
    'POST littering/{id}/process' => 'LitteringApiController@process',

    // Littering Admin API routes
    'GET littering_admin/reports' => 'LitteringAdminApiController@listReports',
    'POST littering_admin/reports/{id}/confirm' => 'LitteringAdminApiController@confirm',
    'DELETE littering_admin/reports/{id}' => 'LitteringAdminApiController@destroy',
    'POST littering_admin/reports/{id}/restore' => 'LitteringAdminApiController@restore',
    'DELETE littering_admin/reports/{id}/permanent' => 'LitteringAdminApiController@permanentlyDelete',
    
    // Organization API routes
    'GET organization' => 'OrganizationApiController@index',
    'POST organization' => 'OrganizationApiController@store',
    'PUT organization/{id}' => 'OrganizationApiController@update',
    'DELETE organization/{id}' => 'OrganizationApiController@destroy',
    
    // Role and Permission API routes
    'GET roles' => 'RoleApiController@index',
    'POST roles' => 'RoleApiController@store',
    'GET roles/{id}' => 'RoleApiController@show',
    'PUT roles/{id}' => 'RoleApiController@update',
    'DELETE roles/{id}' => 'RoleApiController@destroy',
    'PUT roles/{id}/permissions' => 'RoleApiController@updatePermissions',
    
    // User API routes
    'GET users' => 'UserApiController@index',
    'GET users/{id}' => 'UserApiController@show',
    'PUT users/{id}' => 'UserApiController@update',
    'POST users/{id}/link' => 'UserApiController@linkEmployee',
    'POST users/{id}/unlink' => 'UserApiController@unlinkEmployee',
    
    // Menu API routes
    'GET menus' => 'MenuApiController@index',
    'POST menus' => 'MenuApiController@store',
    'PUT menus/order' => 'MenuApiController@updateOrder',
    'PUT menus/{id}' => 'MenuApiController@update',
    'DELETE menus/{id}' => 'MenuApiController@destroy',
    
    // Profile API routes
    'GET profile' => 'ProfileApiController@index',
    'PUT profile' => 'ProfileApiController@update',
    
    // Log API routes
    'GET logs' => 'LogApiController@index',
    'DELETE logs' => 'LogApiController@destroy',

    // Waste Collection API routes
    'GET waste-collections' => 'WasteCollectionApiController@index',
    'POST waste-collections' => 'WasteCollectionApiController@store',
    'GET waste-collections/admin' => 'WasteCollectionApiController@getAdminCollections',
    'POST waste-collections/admin/{id}/process' => 'WasteCollectionApiController@processCollection',
    'PUT waste-collections/admin/{id}/items' => 'WasteCollectionApiController@updateItems',
    'PUT waste-collections/admin/{id}/memo' => 'WasteCollectionApiController@updateMemo',
    'POST waste-collections/admin/parse-html' => 'WasteCollectionApiController@parseHtmlFile',
    'POST waste-collections/admin/batch-register' => 'WasteCollectionApiController@batchRegister',
    'DELETE waste-collections/admin/online-submissions' => 'WasteCollectionApiController@clearOnlineSubmissions',
];