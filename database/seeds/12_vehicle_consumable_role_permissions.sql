-- Assign Vehicle Consumables permissions to roles
-- Role IDs: 1=Super Admin, 16=차량관리자
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`) VALUES
-- vehicle.consumable.view (157) - Admin and Vehicle Manager
(1, 157), (16, 157),
-- vehicle.consumable.manage (158) - Admin and Vehicle Manager
(1, 158), (16, 158),
-- vehicle.consumable.stock (159) - Admin and Vehicle Manager
(1, 159), (16, 159);
