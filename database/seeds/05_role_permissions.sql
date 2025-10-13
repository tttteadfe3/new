-- Renewed role-permission mappings based on the new permission scheme
-- Super Admin (Role ID: 1) gets all permissions
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),   -- dashboard.view
(1, 10),  -- employee.view
(1, 11),  -- employee.create
(1, 12),  -- employee.update
(1, 13),  -- employee.delete
(1, 14),  -- employee.approve
(1, 20),  -- leave.request
(1, 21),  -- leave.view_own
(1, 22),  -- leave.view_all
(1, 23),  -- leave.approve
(1, 24),  -- leave.manage_entitlement
(1, 30),  -- littering.view
(1, 31),  -- littering.create
(1, 32),  -- littering.process
(1, 33),  -- littering.confirm
(1, 34),  -- littering.delete
(1, 35),  -- littering.restore
(1, 40),  -- waste.view
(1, 41),  -- waste.manage_admin
(1, 50),  -- user.view
(1, 51),  -- user.update
(1, 52),  -- user.link
(1, 60),  -- role.view
(1, 61),  -- role.create
(1, 62),  -- role.update
(1, 63),  -- role.delete
(1, 64),  -- role.assign_permissions
(1, 70),  -- organization.manage
(1, 71),  -- organization.view
(1, 72),  -- department.manage_manager
(1, 80),  -- holiday.manage
(1, 90),  -- menu.manage
(1, 100), -- log.view
(1, 101), -- log.delete

-- General User (Role ID: 3) gets basic permissions
(3, 1),   -- dashboard.view
(3, 20),  -- leave.request
(3, 21),  -- leave.view_own
(3, 30),  -- littering.view
(3, 40),  -- waste.view

-- Collection/Transport Field Agent (Role ID: 7)
(7, 1),   -- dashboard.view
(7, 20),  -- leave.request
(7, 21),  -- leave.view_own
(7, 30),  -- littering.view
(7, 31),  -- littering.create
(7, 32),  -- littering.process
(7, 40),  -- waste.view

-- Street Cleaning Field Agent (Role ID: 8) - Basic employee permissions
(8, 1),   -- dashboard.view
(8, 20),  -- leave.request
(8, 21),  -- leave.view_own

-- Collection/Transport Loader (Role ID: 9) - Basic employee permissions
(9, 1),   -- dashboard.view
(9, 20),  -- leave.request
(9, 21),  -- leave.view_own

-- Collection/Transport Leader (Role ID: 10)
(10, 1),   -- dashboard.view
(10, 20),  -- leave.request
(10, 21),  -- leave.view_own
(10, 30),  -- littering.view
(10, 31),  -- littering.create
(10, 32),  -- littering.process
(10, 40),  -- waste.view

-- Collection/Transport Team Leader (Role ID: 11) - More permissions than leader
(11, 1),   -- dashboard.view
(11, 20),  -- leave.request
(11, 21),  -- leave.view_own
(11, 22),  -- leave.view_all (Can see team's leave)
(11, 23),  -- leave.approve (Can approve team's leave)
(11, 30),  -- littering.view
(11, 31),  -- littering.create
(11, 32),  -- littering.process
(11, 33),  -- littering.confirm (Can confirm reports)
(11, 40);  -- waste.view
