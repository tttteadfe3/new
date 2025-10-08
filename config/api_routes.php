<?php

// All API routes are defined here.
// These routes are prefixed with 'api/' automatically by the Router
return [
    // Employee API routes
    'employees' => 'EmployeeApiController@index',
    
    // Holiday API routes  
    'holidays' => 'HolidayApiController@index',
    
    // Leave API routes (user)
    'leaves' => 'LeaveApiController@index',
    
    // Leave Admin API routes
    'leaves_admin' => 'LeaveAdminApiController@index',
    
    // Littering API routes (user)
    'littering' => 'LitteringApiController@index',
    
    // Littering Admin API routes
    'littering_admin' => 'LitteringAdminApiController@index',
    
    // Organization API routes
    'organization' => 'OrganizationApiController@index',
    
    // Role and Permission API routes
    'roles' => 'RoleApiController@index',
    
    // User API routes
    'users' => 'UserApiController@index',
    
    // Menu API routes
    'menus' => 'MenuApiController@index',
    
    // Profile API routes
    'profile' => 'ProfileApiController@index',
    
    // Log API routes
    'logs' => 'LogApiController@index',
];