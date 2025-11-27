-- ============================================================
-- 추가 리소스 타입 정책 (Department, User, Holiday)
-- ============================================================

-- 1. Department 리소스 정책
INSERT INTO `permission_policies` 
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES

-- 본인 부서 조회
('본인 부서 조회', 
 '소속 부서 정보만 조회 가능', 
 (SELECT id FROM permission_resource_types WHERE name='department'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'department', 
 NULL, 
 10),

-- 관리 부서 조회
('관리 부서 조회',
 '관리하는 부서 정보 조회 가능 (하위 부서 포함)',
 (SELECT id FROM permission_resource_types WHERE name='department'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);

-- 2. User 리소스 정책
INSERT INTO `permission_policies`
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES

-- 본인 계정 조회
('본인 계정 조회',
 '자신의 사용자 계정만 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='user'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'own',
 NULL,
 10),

-- 관리 부서 사용자 조회
('관리 부서 사용자 조회',
 '관리하는 부서의 사용자 계정 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='user'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);

-- 3. Holiday 리소스 정책
INSERT INTO `permission_policies`
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES

-- 휴일 조회 (전체 공개)
('휴일 조회',
 '모든 휴일 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='holiday'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'global',
 NULL,
 10);
