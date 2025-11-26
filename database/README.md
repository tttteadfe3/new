# Database 디렉토리

이 디렉토리는 데이터베이스 스키마, 마이그레이션, 시드 데이터, 스크립트를 관리합니다.

## 디렉토리 구조

```
database/
├── schema.sql              # 최신 데이터베이스 스키마 (전체 테이블 구조)
├── schema_old.sql          # 이전 스키마 백업
├── migrations/             # 데이터베이스 마이그레이션 파일
├── seeds/                  # 초기 데이터 시드 파일
└── scripts/                # 데이터베이스 관련 유틸리티 스크립트
```

## 파일 설명

### schema.sql
- **목적**: 현재 데이터베이스의 전체 스키마 정의
- **최종 업데이트**: 2025-11-27 (최적화 적용)
- **포함 내용**:
  - 37개의 테이블 (HR, Supply, Vehicle, Waste, System 등)
  - 2개의 뷰 (v_employee_leave_status, v_vehicle_consumable_inventory)
  - 모든 인덱스, 외래 키, 제약 조건
  - AUTO_INCREMENT 설정

### migrations/
마이그레이션 파일들:
- `2025_11_22_000000_create_vehicle_management_tables.php` - 차량 관리 테이블 생성
- `2025_11_24_000000_add_photo_columns_to_vehicle_works.php` - 차량 작업 사진 컬럼 추가
- `2025_11_24_000001_add_details_to_vehicles.php` - 차량 상세 정보 추가
- `2025_11_24_000002_add_more_photos_to_vehicle_works.php` - 추가 사진 컬럼
- `add_category_tree_structure.sql` - 카테고리 트리 구조 추가
- `create_vehicle_consumables.sql` - 차량 소모품 테이블 생성
- `rename_vehicle_works_to_maintenance.sql` - 테이블명 변경

### seeds/
초기 데이터 시드 파일들:
- `01_departments.sql` - 부서 기본 데이터
- `02_positions.sql` - 직급 기본 데이터
- `03_roles.sql` - 역할 정보
- `04_permissions.sql` - 권한 정보
- `05_role_permissions.sql` - 역할-권한 매핑
- `06_employees.sql` - 직원 정보
- `07_users.sql` - 사용자 계정
- `08_user_roles.sql` - 사용자-역할 매핑
- `09_menus.sql` - 메뉴 구성
- `10_vehicle_consumable_permissions.sql` - 차량 소모품 권한
- `11_vehicle_consumable_menu.sql` - 차량 소모품 메뉴
- `12_vehicle_consumable_role_permissions.sql` - 차량 소모품 역할 권한

### scripts/
유틸리티 스크립트들:
- `export_schema.php` - mysqldump를 사용한 스키마 추출 (레거시)
- `export_schema_pdo.php` - PHP PDO를 사용한 스키마 추출 (권장)

## 스키마 업데이트 방법

### 1. 현재 데이터베이스에서 스키마 추출

```bash
php database/scripts/export_schema_pdo.php
```

이 명령은 현재 데이터베이스의 스키마를 추출하여 `schema_new.sql` 파일을 생성합니다.

### 2. 스키마 비교 및 교체

생성된 `schema_new.sql` 파일을 검토한 후:
- 기존 `schema.sql`을 `schema_old.sql`로 백업
- `schema_new.sql`을 `schema.sql`로 교체

## 데이터베이스 테이블 카테고리

### HR (Human Resources) 모듈
- `hr_departments` - 부서 정보
- `hr_employees` - 직원 정보
- `hr_positions` - 직급 정보
- `hr_leave_applications` - 연차 신청
- `hr_leave_logs` - 연차 변동 로그
- `hr_holidays` - 휴일 정보

### Supply (지급품) 모듈
- `supply_categories` - 지급품 분류
- `supply_items` - 지급품 마스터
- `supply_stocks` - 재고 관리
- `supply_purchases` - 구매 내역
- `supply_distributions` - 지급 내역
- `supply_plans` - 연간 계획

### Vehicle (차량) 모듈
- `vehicles` - 차량 정보
- `vehicle_maintenance` - 정비 이력
- `vehicle_inspections` - 검사 이력
- `vehicle_consumables_categories` - 소모품 분류
- `vehicle_consumable_stock` - 소모품 재고
- `vehicle_consumable_usage` - 소모품 사용 이력

### Waste (폐기물) 모듈
- `waste_collections` - 대형폐기물 수거
- `waste_collection_items` - 수거 품목
- `illegal_disposal_cases2` - 부적정 배출 정보

### System (시스템) 모듈
- `sys_users` - 사용자 계정
- `sys_roles` - 역할 정보
- `sys_permissions` - 권한 정보
- `sys_menus` - 메뉴 구성
- `sys_activity_logs` - 활동 로그

## 주의사항

### 백업 테이블
현재 데이터베이스에는 다음 백업 테이블들이 존재합니다:
- `backup_vehicle_consumable_stock_20251125`
- `backup_vehicle_consumable_usage_20251125`
- `backup_vehicle_consumables_categories_20251125`

이 테이블들은 2025-11-25에 생성된 백업이며, 필요시 삭제 가능합니다.

### 마이그레이션 실행

마이그레이션을 실행하려면:
```bash
php run_migration.php
```

## 참고사항

- 스키마는 정기적으로 업데이트해야 합니다
- 중요한 변경사항은 반드시 백업을 먼저 수행하세요
- 프로덕션 데이터베이스 변경 시 충분한 테스트를 거쳐야 합니다
