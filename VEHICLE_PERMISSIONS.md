# 차량 관리 시스템 권한 설정

## 추가된 권한 (Permissions)

차량 관리 시스템을 위해 다음 권한들이 추가되었습니다:

| ID  | Key | 설명 | 대상 사용자 |
|-----|-----|------|------------|
| 150 | `vehicle.view` | 차량 목록 조회 | 모든 사용자 |
| 151 | `vehicle.manage` | 차량 정보 관리 (등록/수정/삭제) | 관리자 |
| 152 | `vehicle.work.view` | 차량 작업 조회 | 모든 사용자 |
| 153 | `vehicle.work.report` | 차량 작업 신고 (고장/정비) | 운전원 |
| 154 | `vehicle.work.manage` | 차량 작업 처리 및 승인 | Manager |
| 155 | `vehicle.inspection.view` | 차량 검사 조회 | 모든 사용자 |
| 156 | `vehicle.inspection.manage` | 차량 검사 관리 | 관리자 |

## 역할별 권한 매핑 예시

### 1. 운전원 (Driver)
```sql
-- 차량 조회, 작업 신고
INSERT INTO sys_role_permissions (role_id, permission_id) VALUES
((SELECT id FROM sys_roles WHERE name = 'Driver'), 150), -- vehicle.view
((SELECT id FROM sys_roles WHERE name = 'Driver'), 152), -- vehicle.work.view
((SELECT id FROM sys_roles WHERE name = 'Driver'), 153), -- vehicle.work.report
((SELECT id FROM sys_roles WHERE name = 'Driver'), 155); -- vehicle.inspection.view
```

### 2. Manager
```sql
-- 차량 조회, 작업 처리
INSERT INTO sys_role_permissions (role_id, permission_id) VALUES
((SELECT id FROM sys_roles WHERE name = 'Manager'), 150), -- vehicle.view
((SELECT id FROM sys_roles WHERE name = 'Manager'), 152), -- vehicle.work.view
((SELECT id FROM sys_roles WHERE name = 'Manager'), 154), -- vehicle.work.manage
((SELECT id FROM sys_roles WHERE name = 'Manager'), 155); -- vehicle.inspection.view
```

### 3. 관리자 (Admin)
```sql
-- 모든 권한
INSERT INTO sys_role_permissions (role_id, permission_id) VALUES
((SELECT id FROM sys_roles WHERE name = 'Admin'), 150), -- vehicle.view
((SELECT id FROM sys_roles WHERE name = 'Admin'), 151), -- vehicle.manage
((SELECT id FROM sys_roles WHERE name = 'Admin'), 152), -- vehicle.work.view
((SELECT id FROM sys_roles WHERE name = 'Admin'), 153), -- vehicle.work.report
((SELECT id FROM sys_roles WHERE name = 'Admin'), 154), -- vehicle.work.manage
((SELECT id FROM sys_roles WHERE name = 'Admin'), 155), -- vehicle.inspection.view
((SELECT id FROM sys_roles WHERE name = 'Admin'), 156); -- vehicle.inspection.manage
```

## 사용 예시

### Routes에서 권한 체크
```php
// 차량 목록 (모든 사용자)
$router->get('/vehicles', [VehicleController::class, 'index'])
    ->middleware('auth')
    ->middleware('permission', 'vehicle.view');

// 운전원 작업 페이지 (운전원 전용)
$router->get('/vehicles/my-work', [VehicleDriverController::class, 'index'])
    ->middleware('auth')
    ->middleware('permission', 'vehicle.work.report');

// Manager 작업 처리 (Manager 전용)
$router->get('/vehicles/manager/work', [VehicleManagerController::class, 'index'])
    ->middleware('auth')
    ->middleware('permission', 'vehicle.work.manage');
```

## 권한 적용 파일

- `database/seeds/04_permissions.sql` - 권한 정의
- `database/seeds/05_role_permissions.sql` - 역할별 권한 매핑 (수동 추가 필요)
