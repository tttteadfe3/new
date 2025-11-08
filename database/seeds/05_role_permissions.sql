INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`) VALUES
-- Dashboard
(1, 1), (7, 1), (8, 1), (16, 1), (19, 1), (21, 1), (22, 1),

-- Employee Management
(1, 10), (21, 10),
(1, 11), (21, 11),
(1, 12), (21, 12),
(1, 13), (21, 13),
(1, 14), (21, 14),
(1, 15), (21, 15),
(1, 16), (21, 16),

-- Leave Management (NEW STRUCTURE)
-- leave.view (id 20) - Grant to all roles
(1, 20), (7, 20), (8, 20), (16, 20), (19, 20), (21, 20), (22, 20),
-- leave.approve (id 21) - Grant to admin and HR manager roles
(1, 21), (7, 21), (8, 21), (16, 21), (21, 21),
-- leave.manage (id 22) - Grant to super-admin and HR manager roles
(1, 22), (21, 22),

-- Littering Management
(1, 30), (7, 30), (16, 30), (19, 30), (21, 30), (22, 30),
(1, 31), (7, 31), (16, 31), (19, 31), (21, 31), (22, 31),
(1, 32), (7, 32), (16, 32), (19, 32), (21, 32), (22, 32),
(1, 33), (7, 33), (21, 33),
(1, 34), (7, 34), (21, 34),
(1, 35), (21, 35),
(1, 36), (7, 36), (21, 36),
(1, 37), (21, 37),

-- Waste Collection Management
(1, 40), (7, 40), (16, 40), (19, 40), (21, 40), (22, 40),
(1, 41), (21, 41),
(1, 42), (21, 42),

-- User Management
(1, 50),
(1, 51),
(1, 52),

-- Role & Permission Management
(1, 60),
(1, 61),
(1, 62),
(1, 63),
(1, 64),

-- Organization Management
(1, 70),
(1, 71), (21, 71),
(1, 72), (21, 72),

-- Holiday Management
(1, 80), (21, 80),

-- Menu Management
(1, 90),

-- Log Management
(1, 100),
(1, 101);
