-- ============================================================
-- 추가 정책 역할 매핑 업데이트
-- ============================================================

-- 1. 모든 역할에 휴일 조회 정책 추가
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE p.name = '휴일 조회';

-- 2. 팀장 역할에 department, user 관리 정책 추가
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name LIKE '%팀장%'
  AND p.name IN ('관리 부서 조회', '관리 부서 사용자 조회');

-- 3. 현장대리인 역할에 department, user 관리 정책 추가
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name LIKE '%현장대리인%'
  AND p.name IN ('관리 부서 조회', '관리 부서 사용자 조회');

-- 4. 모든 역할에 본인 부서, 본인 계정 조회 정책 추가
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE p.name IN ('본인 부서 조회', '본인 계정 조회');

-- 5. 최고관리자에게 모든 신규 정책 추가 (혹시 누락됐을 경우 대비)
INSERT IGNORE INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name = '최고관리자'
  AND p.id NOT IN (SELECT policy_id FROM role_policies WHERE role_id = r.id);
