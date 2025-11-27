-- ============================================================
-- 권한 시스템 기본 정책 데이터
-- ============================================================

-- 1. 직원 정보 조회 정책
INSERT INTO `permission_policies` 
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES
-- 본인 정보만 조회
('본인 직원정보 조회', 
 '자신의 직원 정보만 조회 가능', 
 (SELECT id FROM permission_resource_types WHERE name='employee'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'own', 
 NULL, 
 10),

-- 관리 부서 직원 조회
('관리 부서 직원 조회',
 '관리하는 부서의 직원 정보 조회 가능 (하위 부서 포함)',
 (SELECT id FROM permission_resource_types WHERE name='employee'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);

-- 2. 연차 정보 조회 정책
INSERT INTO `permission_policies`
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES
-- 본인 연차 조회
('본인 연차 조회',
 '자신의 연차 정보만 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='leave'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'own',
 NULL,
 10),

-- 같은 과 전체 연차 조회 (핵심!)
('같은 과 전체 연차 조회',
 '상위 부서(과)의 모든 하위 부서(팀) 연차 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='leave'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'parent_department_tree',
 JSON_OBJECT('scope', 'parent_department_tree', 'include_self', true),
 15),

-- 관리 부서 연차 조회
('관리 부서 연차 조회',
 '관리하는 부서의 연차 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='leave'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);

-- 3. 차량 정보 조회 정책
INSERT INTO `permission_policies`
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES
-- 본인 부서 차량 조회
('본인 부서 차량 조회',
 '소속 부서의 차량 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='vehicle'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'department',
 NULL,
 10),

-- 관리 부서 차량 조회
('관리 부서 차량 조회',
 '관리하는 부서의 차량 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='vehicle'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);

-- 4. 지급품 정보 조회 정책
INSERT INTO `permission_policies`
(`name`, `description`, `resource_type_id`, `action_id`, `scope_type`, `scope_config`, `priority`) VALUES
-- 본인 부서 지급품 조회
('본인 부서 지급품 조회',
 '소속 부서의 지급품 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='supply'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'department',
 NULL,
 10),

-- 관리 부서 지급품 조회
('관리 부서 지급품 조회',
 '관리하는 부서의 지급품 정보 조회 가능',
 (SELECT id FROM permission_resource_types WHERE name='supply'),
 (SELECT id FROM permission_actions WHERE name='view'),
 'managed_departments',
 NULL,
 20);
