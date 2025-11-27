-- ============================================================
-- 역할별 권한 정책 매핑 (실제 역할명 기반)
-- ============================================================

-- 기존 매핑 삭제
DELETE FROM `role_policies`;

-- 1. 최고관리자 - 모든 정책 부여 (전체 조회 가능)
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name = '최고관리자';

-- 2. 팀장 역할들 (수집운반-팀장, 가로청소-팀장)
-- 관리 부서의 모든 정보 조회 가능
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name LIKE '%팀장%'
  AND p.name IN (
    '본인 직원정보 조회',
    '관리 부서 직원 조회',
    '본인 연차 조회',
    '같은 과 전체 연차 조회',
    '관리 부서 연차 조회',
    '본인 부서 차량 조회',
    '관리 부서 차량 조회',
    '본인 부서 지급품 조회',
    '관리 부서 지급품 조회'
  );

-- 3. 현장대리인 역할들 (수집운반-현장대리인, 가로청소-현장대리인)
-- 관리 부서의 정보 조회 가능 (팀장과 동일)
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name LIKE '%현장대리인%'
  AND p.name IN (
    '본인 직원정보 조회',
    '관리 부서 직원 조회',
    '본인 연차 조회',
    '같은 과 전체 연차 조회',
    '관리 부서 연차 조회',
    '본인 부서 차량 조회',
    '관리 부서 차량 조회',
    '본인 부서 지급품 조회',
    '관리 부서 지급품 조회'
  );

-- 4. 조장 역할 (수집운반-조장)
-- 본인 부서 정보 + 같은 과 연차 조회
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE r.name LIKE '%조장%'
  AND p.name IN (
    '본인 직원정보 조회',
    '본인 연차 조회',
    '같은 과 전체 연차 조회',
    '본인 부서 차량 조회',
    '본인 부서 지급품 조회'
  );

-- 5. 일반 직원 (상차원, 청소원)
-- 본인 정보 + 같은 과 연차 조회
INSERT INTO `role_policies` (`role_id`, `policy_id`)
SELECT 
  r.id,
  p.id
FROM sys_roles r
CROSS JOIN permission_policies p
WHERE (r.name LIKE '%상차원%' OR r.name LIKE '%청소원%')
  AND p.name IN (
    '본인 직원정보 조회',
    '본인 연차 조회',
    '같은 과 전체 연차 조회',
    '본인 부서 차량 조회',
    '본인 부서 지급품 조회'
  );
