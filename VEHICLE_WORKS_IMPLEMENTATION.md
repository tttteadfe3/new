# 차량 관리 시스템 - 통합 테이블 구현 완료

## ✅ 완료된 작업

### 1. 데이터베이스
- ✅ **vehicle_works 통합 테이블** 생성
  - 고장과 정비를 하나의 테이블로 통합
  - type: '고장', '정비'
  - status: '신고' → '처리결정' → '작업중' → '완료'

### 2. 백엔드 API
- ✅ `VehicleWork` Model
- ✅ `VehicleWorkRepository` - 필터링 및 권한 관리
- ✅ `VehicleWorkService` - 워크플로우 로직
- ✅ `VehicleWorkApiController` - REST API 엔드포인트
- ✅ DI Container 등록

### 3. API 엔드포인트

```
GET    /api/vehicles/works              # 작업 목록 (type, status 필터)
GET    /api/vehicles/works/{id}         # 작업 상세
POST   /api/vehicles/works              # 작업 신고 (고장 or 정비)
DELETE /api/vehicles/works/{id}         # 작업 삭제

POST   /api/vehicles/works/{id}/decide   # 수리 방법 결정 (고장만)
POST   /api/vehicles/works/{id}/start    # 작업 시작
POST   /api/vehicles/works/{id}/complete # 작업 완료
POST   /api/vehicles/works/{id}/confirm  # 작업 확인 (Manager)
```

## ⚠️ 수동 작업 필요

### API Routes 등록
`routes/api.php` 파일에서 다음 변경 필요:

**제거할 라우트:**
```php
// 기존 breakdown, maintenance 라우트 삭제
/vehicles/breakdowns/*
/vehicles/self-maintenances/*
```

**추가할 라우트:**
```php
// Vehicle Works (통합)
$router->get('/vehicles/works', [VehicleWorkApiController::class, 'index']);
$router->get('/vehicles/works/{id}', [VehicleWorkApiController::class, 'show']);  
$router->post('/vehicles/works', [VehicleWorkApiController::class, 'store']);
$router->delete('/vehicles/works/{id}', [VehicleWorkApiController::class, 'destroy']);

$router->post('/vehicles/works/{id}/decide', [VehicleWorkApiController::class, 'decide']);
$router->post('/vehicles/works/{id}/start', [VehicleWorkApiController::class, 'start']);
$router->post('/vehicles/works/{id}/complete', [VehicleWorkApiController::class, 'complete']);
$router->post('/vehicles/works/{id}/confirm', [VehicleWorkApiController::class, 'confirm']);
```

## 🔄 워크플로우

### 고장 처리
```
1. 신고 (운전원) → type: '고장', status: '신고'
2. 처리결정 (Manager) → status: '처리결정', repair_type: '자체수리' or '외부수리'
3. 작업중 → status: '작업중'
4. 완료 (운전원/정비사) → status: '완료', completed_at 기록
5. 확인 (Manager) → confirmed_at, confirmed_by 기록
```

### 정비 처리
```
1. 신고 (운전원) → type: '정비', status: '신고'
2. 작업중 → status: '작업중' (처리결정 생략)
3. 완료 (운전원) → status: '완료'
4. 확인 (Manager) → confirmed_at 기록
```

## 📋 다음 단계

1. ✅ **마이그레이션 실행**
   ```bash
   php run_migration.php
   ```

2. ⏳ **API Routes 수동 등록**
   - routes/api.php 파일 수정

3. ⏳ **프론트엔드 구현**
   - Web Controllers (3개)
   - Views (3개)
   - JavaScript (3개)

4. ⏳ **테스트**
   - API 테스트
   - 워크플로우 테스트

## 📊 테이블 구조

```sql
CREATE TABLE `vehicle_works` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `type` VARCHAR(20) NOT NULL,           -- '고장', '정비'
    `status` VARCHAR(20) NOT NULL,         -- '신고', '처리결정', '작업중', '완료'
    `reporter_id` INT NOT NULL,
    `work_item` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `mileage` INT,
    `photo_path` VARCHAR(255),
    `repair_type` VARCHAR(20),             -- '자체수리', '외부수리' (고장만)
    `decided_at` DATETIME,
    `decided_by` INT,
    `parts_used` TEXT,
    `cost` DECIMAL(10, 2),
    `worker_id` INT,
    `repair_shop` VARCHAR(255),
    `completed_at` DATETIME,
    `confirmed_at` DATETIME,
    `confirmed_by` INT,
    `created_at` DATETIME,
    `updated_at` DATETIME
);
```

## 🎯 장점

1. **간소화** - 테이블 3개 → 1개
2. **일관성** - 동일한 워크플로우 로직
3. **유지보수** - 코드 재사용 증가
4. **권한 관리** - DataScopeService 통합
