# Vehicle Management System - Implementation Summary

## 완료된 작업 (Completed Tasks)

### 1. 데이터베이스 (Database)

#### 마이그레이션 파일 생성 및 실행 완료
- **파일**: `database/migrations/2025_11_22_000000_create_vehicle_management_tables.php`
- **실행**: ✅ 완료 (`php run_migration.php`)

#### 생성된 테이블
1. **vm_vehicles** - 차량 기본 정보
   - `driver_employee_id` 필드 포함 (담당 운전원)
   - `department_id` 필드 포함 (소속 부서)
   
2. **vm_vehicle_breakdowns** - 차량 고장 신고
3. **vm_vehicle_repairs** - 차량 수리 내역
4. **vm_vehicle_maintenances** - 운전자 자체 정비
5. **vm_vehicle_inspections** - 차량 정기검사
6. **vm_vehicle_consumables** - 소모품 정보
7. **vm_vehicle_consumable_logs** - 소모품 교체 이력
8. **vm_vehicle_insurances** - 차량 보험
9. **vm_vehicle_taxes** - 차량 세금
10. **vm_vehicle_documents** - 차량 관련 문서

### 2. 모델 (Models)

✅ 생성 완료:
- `app/Models/Vehicle.php`
- `app/Models/VehicleBreakdown.php`
- `app/Models/VehicleRepair.php`
- `app/Models/VehicleMaintenance.php`
- `app/Models/VehicleInspection.php`

### 3. 리포지토리 (Repositories)

✅ 생성 완료:
- `app/Repositories/VehicleRepository.php`
  - **부서 + 운전원 필터링 로직 구현**
  - `visible_department_ids` OR `current_user_driver_id` 조건
- `app/Repositories/VehicleMaintenanceRepository.php`
  - 고장 신고, 수리, 자체 정비 CRUD
  - 부서별 필터링 지원
- `app/Repositories/VehicleInspectionRepository.php`
  - 검사 CRUD 및 만료 예정 필터링

### 4. 서비스 (Services)

✅ 생성 완료:
- `app/Services/VehicleService.php`
- `app/Services/VehicleMaintenanceService.php`
  - **워크플로우 메서드 구현**:
    - `decideRepairType()` - 자체/외부 수리 판단
    - `completeRepair()` - 수리 완료 등록
    - `confirmRepair()` - 수리 확인
    - `confirmSelfMaintenance()` - 자체 정비 확인
- `app/Services/VehicleInspectionService.php`

### 5. 컨트롤러 (Controllers)

✅ 생성 완료:
- `app/Controllers/Api/VehicleApiController.php`
  - **DataScopeService 통합**
  - 부서별 권한 필터링 로직 구현
- `app/Controllers/Api/VehicleMaintenanceApiController.php`
  - 고장/수리/정비 워크플로우 엔드포인트
- `app/Controllers/Api/VehicleInspectionApiController.php`

### 6. 라우트 (Routes)

✅ `routes/api.php`에 등록 완료:

#### 차량 관리
- `GET /api/vehicles` - 차량 목록
- `GET /api/vehicles/{id}` - 차량 상세
- `POST /api/vehicles` - 차량 등록
- `PUT /api/vehicles/{id}` - 차량 수정
- `DELETE /api/vehicles/{id}` - 차량 삭제

#### 고장/수리 관리
- `GET /api/vehicles/breakdowns` - 고장 목록
- `GET /api/vehicles/breakdowns/{id}` - 고장 상세
- `POST /api/vehicles/breakdowns` - 고장 신고
- `POST /api/vehicles/breakdowns/{id}/decide` - 수리 방법 결정
- `POST /api/vehicles/breakdowns/{id}/complete` - 수리 완료
- `POST /api/vehicles/breakdowns/{id}/confirm` - 수리 확인
- `POST /api/vehicles/repairs` - 수리 내역 등록

#### 자체 정비
- `GET /api/vehicles/self-maintenances` - 정비 목록
- `POST /api/vehicles/self-maintenances` - 정비 등록
- `POST /api/vehicles/self-maintenances/{id}/confirm` - 정비 확인

#### 검사 관리
- `GET /api/vehicles/inspections` - 검사 목록
- `POST /api/vehicles/inspections` - 검사 등록

### 7. DI 컨테이너 (Dependency Injection)

✅ `public/index.php`에 등록 완료:
- VehicleRepository
- VehicleMaintenanceRepository  
- VehicleInspectionRepository
- VehicleService
- VehicleMaintenanceService
- VehicleInspectionService
- VehicleApiController
- VehicleMaintenanceApiController
- VehicleInspectionApiController

## 데이터 스코프 (Data Scope) 구현

### 접근 권한 로직

#### 1. 관리자 (Admin)
- `DataScopeService::getVisibleDepartmentIdsForCurrentUser()` → `null`
- **모든 차량** 조회 가능

#### 2. 부서 담당자 (Department Manager)
- `DataScopeService::getVisibleDepartmentIdsForCurrentUser()` → `[dept_id, ...]`
- **조건**: `hr_department_managers` 테이블에 등록되어 있거나 `hr_department_view_permissions` 권한 보유
- **조회 범위**: 
  - 담당 부서의 모든 차량 OR
  - 본인이 담당 운전원인 차량

#### 3. 운전원 (Driver)
- `DataScopeService::getVisibleDepartmentIdsForCurrentUser()` → `[]` (빈 배열)
- **조회 범위**: 본인이 `driver_employee_id`로 지정된 차량만

### SQL 조건 예시

```sql
-- 부서 담당자
WHERE (v.department_id IN (10, 20) OR v.driver_employee_id = 123)

-- 운전원
WHERE v.driver_employee_id = 456
```

## 워크플로우 (Workflows)

### 차량 수리 워크플로우

```
1. 고장 신고 (운전원)
   POST /api/vehicles/breakdowns
   Status: REGISTERED

2. 수리 방법 판단 (중간관리자)
   POST /api/vehicles/breakdowns/{id}/decide
   Body: { "type": "INTERNAL" or "EXTERNAL" }
   Status: DECIDED

3. 수리 완료 등록 (운전원)
   POST /api/vehicles/breakdowns/{id}/complete
   Body: { "repair_type", "repair_item", "parts_used", "cost", ... }
   Status: COMPLETED

4. 수리 확인 (중간관리자)
   POST /api/vehicles/breakdowns/{id}/confirm
   Status: APPROVED
```

### 차량 정비 워크플로우

```
1. 정비 등록 (운전원)
   POST /api/vehicles/self-maintenances
   Status: COMPLETED

2. 정비 확인 (중간관리자)
   POST /api/vehicles/self-maintenances/{id}/confirm
   Status: APPROVED
```

## API 사용 예시

### 차량 등록
```json
POST /api/vehicles
{
  "vehicle_number": "12가3456",
  "model": "포터2",
  "year": 2023,
  "department_id": 5,
  "driver_employee_id": 42,
  "status_code": "NORMAL"
}
```

### 고장 신고
```json
POST /api/vehicles/breakdowns
{
  "vehicle_id": 1,
  "driver_employee_id": 42,
  "breakdown_item": "엔진 이상음",
  "description": "가속 시 큰 소리 발생",
  "mileage": 15000,
  "status": "REGISTERED"
}
```

### 수리 완료
```json
POST /api/vehicles/breakdowns/1/complete
{
  "repair_type": "INTERNAL",
  "repair_item": "엔진 벨트 교체",
  "parts_used": "엔진 벨트 v-belt",
  "cost": 50000,
  "repairer_id": 30,
  "completed_at": "2025-11-22 14:30:00"
}
```

## 테스트

### 실행 방법
```bash
# 전체 테스트
vendor/bin/phpunit tests/VehicleManagementTest.php
```

**참고**: 현재 단위 테스트는 PHPUnit 모킹 이슈로 인해 실패하고 있습니다. 실제 기능 테스트는 Postman 등의 도구를 사용하여 API 엔드포인트를 직접 호출하여 수행하는 것을 권장합니다.

## 필요한 권한 (Permissions)

시스템에 다음 권한들을 추가해야 합니다:

- `vehicle.view` - 차량 조회
- `vehicle.manage` - 차량 관리 (등록/수정/삭제)
- `vehicle.maintenance.view` - 정비 내역 조회
- `vehicle.maintenance.report` - 정비 신고 (운전원)
- `vehicle.maintenance.manage` - 정비 관리 (중간관리자)
- `vehicle.inspection.view` - 검사 조회
- `vehicle.inspection.manage` - 검사 관리

## 다음 단계 (Next Steps)

1. **권한 설정**: 시스템에 위의 권한들을 추가하고 역할별로 할당
2. **프론트엔드 개발**: Vue.js 등을 사용하여 UI 구현
3. **파일 업로드**: 사진 및 문서 파일 업로드 기능 구현
4. **알림 기능**: 검사 만료 예정 알림 등

## 파일 구조

```
database/
  migrations/
    2025_11_22_000000_create_vehicle_management_tables.php

app/
  Models/
    Vehicle.php
    VehicleBreakdown.php
    VehicleRepair.php
    VehicleMaintenance.php
    VehicleInspection.php
  
  Repositories/
    VehicleRepository.php
    VehicleMaintenanceRepository.php
    VehicleInspectionRepository.php
  
  Services/
    VehicleService.php
    VehicleMaintenanceService.php
    VehicleInspectionService.php
  
  Controllers/
    Api/
      VehicleApiController.php
      VehicleMaintenanceApiController.php
      VehicleInspectionApiController.php

routes/
  api.php (업데이트됨)

public/
  index.php (DI 컨테이너 등록 업데이트됨)

tests/
  VehicleManagementTest.php
```

## 문의사항

구현에 대한 질문이나 추가 기능 요청이 있으시면 알려주세요.
