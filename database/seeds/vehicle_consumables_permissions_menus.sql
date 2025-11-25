-- =====================================================
-- 차량 소모품 관리 시스템 - 권한 및 메뉴 설정
-- =====================================================

-- =====================================================
-- 1. 권한 (Permissions) 추가
-- =====================================================

-- 차량 소모품 관련 권한 추가
INSERT INTO sys_permissions (`key`, description) VALUES
('vehicle.consumable.view', '차량 소모품 조회 권한'),
('vehicle.consumable.manage', '차량 소모품 관리 권한 (등록/수정/삭제)'),
('vehicle.consumable.stock', '차량 소모품 입출고 처리 권한'),
('vehicle.consumable.usage', '차량 소모품 사용 등록 권한');

-- =====================================================
-- 2. 메뉴 (Menus) 추가
-- =====================================================

-- 차량 관리 최상위 메뉴 ID 확인 (기존에 있다고 가정)
-- 차량 관리 메뉴가 없다면 먼저 생성:
-- INSERT INTO sys_menus (parent_id, name, url, icon, permission_key, display_order) 
-- VALUES (NULL, '차량 관리', '#', 'ri-car-line', 'vehicle.view', 50);

-- 차량 소모품 메뉴 추가 (차량 관리 하위 메뉴로)
-- 주의: parent_id는 실제 '차량 관리' 메뉴의 ID로 변경하세요
INSERT INTO sys_menus (parent_id, name, url, icon, permission_key, display_order) 
VALUES (
    (SELECT id FROM sys_menus WHERE name = '차량 관리' LIMIT 1), 
    '소모품 관리', 
    '/vehicles/consumables', 
    'ri-tools-line', 
    'vehicle.consumable.view', 
    60
);

-- =====================================================
-- 3. 역할-권한 매핑 (Role-Permission Mapping)
-- =====================================================

-- 관리자 역할에 모든 차량 소모품 권한 부여
INSERT INTO sys_role_permissions (role_id, permission_id)
SELECT 
    r.id as role_id,
    p.id as permission_id
FROM sys_roles r
CROSS JOIN sys_permissions p
WHERE r.name = 'admin'
  AND p.key IN (
    'vehicle.consumable.view',
    'vehicle.consumable.manage',
    'vehicle.consumable.stock',
    'vehicle.consumable.usage'
  )
ON DUPLICATE KEY UPDATE role_id = role_id; -- 중복 방지

-- 차량 담당자 역할에 소모품 권한 부여 (역할이 존재하는 경우)
INSERT INTO sys_role_permissions (role_id, permission_id)
SELECT 
    r.id as role_id,
    p.id as permission_id
FROM sys_roles r
CROSS JOIN sys_permissions p
WHERE r.name IN ('vehicle_manager', 'vehicle_admin') -- 실제 역할명에 맞게 수정
  AND p.key IN (
    'vehicle.consumable.view',
    'vehicle.consumable.manage',
    'vehicle.consumable.stock',
    'vehicle.consumable.usage'
  )
ON DUPLICATE KEY UPDATE role_id = role_id; -- 중복 방지

-- 일반 사용자는 조회만 가능 (필요한 경우)
INSERT INTO sys_role_permissions (role_id, permission_id)
SELECT 
    r.id as role_id,
    p.id as permission_id
FROM sys_roles r
CROSS JOIN sys_permissions p
WHERE r.name = 'user'
  AND p.key = 'vehicle.consumable.view'
ON DUPLICATE KEY UPDATE role_id = role_id; -- 중복 방지

-- =====================================================
-- 4. 확인 쿼리
-- =====================================================

-- 추가된 권한 확인
SELECT * FROM sys_permissions WHERE `key` LIKE 'vehicle.consumable%';

-- 추가된 메뉴 확인
SELECT m.*, p.name as parent_name 
FROM sys_menus m
LEFT JOIN sys_menus p ON m.parent_id = p.id
WHERE m.name = '소모품 관리';

-- 역할별 권한 매핑 확인
SELECT r.name as role_name, p.key as permission_key, p.description
FROM sys_role_permissions rp
JOIN sys_roles r ON rp.role_id = r.id
JOIN sys_permissions p ON rp.permission_id = p.id
WHERE p.key LIKE 'vehicle.consumable%'
ORDER BY r.name, p.key;
