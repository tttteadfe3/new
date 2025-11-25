# 차량 소모품 관리 시스템 - 재구성된 데이터베이스 스키마

## 개요

차량 소모품 관리를 위한 간소화된 3-테이블 구조입니다.

## 워크플로우

```
1. 분류 설정 (vehicle_consumables_categories)
   ↓
2. 재고 등록 (vehicle_consumable_stock)
   ↓
3. 정비/자체수리 시 재고 사용
   ↓
4. 사용 이력 기록 (vehicle_consumable_usage)
```

---

## 테이블 구조

### 1. vehicle_consumables_categories (소모품 분류)

**목적**: 소모품 카테고리 및 기본 정보 관리 (간소화됨)

```sql
CREATE TABLE `vehicle_consumables_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '소모품명',
  `category` varchar(100) DEFAULT NULL COMMENT '카테고리',
  `unit` varchar(50) DEFAULT '개' COMMENT '단위',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**주요 필드**:
- `name`: 소모품명 (예: 엔진오일 5W-30)
- `category`: 카테고리 (엔진오일, 타이어, 브레이크, 필터 등)
- `unit`: 단위 (개, 리터, 세트 등)

**제거된 필드** (기존 대비):
- ~~`part_number`~~ (부품번호) - 필요시 note에 기록
- ~~`minimum_stock`~~ (최소재고량) - 뷰에서 계산
- ~~`location`~~ (보관위치) - 필요시 note에 기록
- ~~`current_stock`~~ - 뷰에서 계산

---

### 2. vehicle_consumable_stock (재고 입고)

**목적**: 소모품 입고 이력 관리

```sql
CREATE TABLE `vehicle_consumable_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `quantity` int(11) NOT NULL COMMENT '입고 수량',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '입고 단가',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `registered_by` int(11) DEFAULT NULL COMMENT '등록자 employee_id',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_purchase_date` (`purchase_date`),
  CONSTRAINT `fk_stock_consumable` FOREIGN KEY (`consumable_id`) 
    REFERENCES `vehicle_consumables_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**주요 필드**:
- `consumable_id`: 소모품 분류 ID (FK)
- `quantity`: 입고 수량
- `unit_price`: 단가
- `purchase_date`: 구매일

**제거된 필드** (기존 대비):
- ~~`supplier`~~ (공급업체) - 필요시 note에 기록

---

### 3. vehicle_consumable_usage (사용 이력)

**목적**: 정비 작업 시 소모품 사용 이력 기록

```sql
CREATE TABLE `vehicle_consumable_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `maintenance_id` int(11) DEFAULT NULL COMMENT '정비 작업 ID',
  `vehicle_id` int(11) DEFAULT NULL COMMENT '차량 ID',
  `quantity` int(11) NOT NULL COMMENT '사용 수량',
  `used_by` int(11) DEFAULT NULL COMMENT '사용자 employee_id',
  `used_at` datetime DEFAULT current_timestamp() COMMENT '사용일시',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_maintenance_id` (`maintenance_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_consumable_usage_consumable` FOREIGN KEY (`consumable_id`) 
    REFERENCES `vehicle_consumables_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_consumable_usage_maintenance` FOREIGN KEY (`maintenance_id`) 
    REFERENCES `vehicle_maintenance` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**주요 필드**:
- `consumable_id`: 소모품 분류 ID (FK)
- `maintenance_id`: 정비 작업 ID (FK to vehicle_maintenance)
- `vehicle_id`: 차량 ID
- `quantity`: 사용 수량
- `used_by`: 사용자 직원 ID

---

## 재고 현황 뷰

현재 재고를 실시간 계산하는 뷰:

```sql
CREATE OR REPLACE VIEW `v_vehicle_consumable_inventory` AS
SELECT 
    c.id AS consumable_id,
    c.name AS consumable_name,
    c.category,
    c.unit,
    COALESCE(SUM(s.quantity), 0) AS total_stock_in,
    COALESCE(SUM(u.quantity), 0) AS total_used,
    COALESCE(SUM(s.quantity), 0) - COALESCE(SUM(u.quantity), 0) AS current_stock
FROM 
    vehicle_consumables_categories c
    LEFT JOIN vehicle_consumable_stock s ON c.id = s.consumable_id
    LEFT JOIN vehicle_consumable_usage u ON c.id = u.consumable_id
GROUP BY 
    c.id, c.name, c.category, c.unit;
```

**사용 예시**:
```sql
-- 현재 재고 확인
SELECT * FROM v_vehicle_consumable_inventory;

-- 재고 부족 항목 확인 (현재고 < 5)
SELECT * FROM v_vehicle_consumable_inventory WHERE current_stock < 5;
```

---

## 실행 방법

```bash
mysql -u [username] -p [database_name] < database/migrations/create_vehicle_consumables.sql
```

---

## 샘플 데이터

```sql
-- 1. 분류 등록
INSERT INTO vehicle_consumables_categories (name, category, unit) VALUES
('엔진오일 5W-30', '엔진오일', '리터'),
('에어필터', '필터', '개'),
('브레이크패드 전륜', '브레이크', '세트'),
('타이어 195/65R15', '타이어', '개');

-- 2. 재고 입고
INSERT INTO vehicle_consumable_stock (consumable_id, quantity, unit_price, purchase_date) VALUES
(1, 20, 35000.00, '2025-01-15'),  -- 엔진오일 20리터
(2, 10, 15000.00, '2025-01-15'),  -- 에어필터 10개
(3, 5, 80000.00, '2025-01-20'),   -- 브레이크패드 5세트
(4, 8, 120000.00, '2025-01-20');  -- 타이어 8개

-- 3. 사용 이력 (정비 작업과 연동)
INSERT INTO vehicle_consumable_usage (consumable_id, maintenance_id, vehicle_id, quantity, used_by) VALUES
(1, 1, 1, 4, 1),  -- 정비 ID 1번에서 엔진오일 4리터 사용
(2, 1, 1, 1, 1);  -- 정비 ID 1번에서 에어필터 1개 사용
```

---

## 주요 변경사항 요약

| 항목 | 기존 | 변경 후 |
|------|------|---------|
| 테이블명 | `vehicle_consumables` | `vehicle_consumables_categories` |
| 테이블명 | `vehicle_consumable_stock_in` | `vehicle_consumable_stock` |
| 테이블명 | `vehicle_works` | `vehicle_maintenance` |
| categories 필드 제거 | - | `part_number`, `minimum_stock`, `location` |
| stock 필드 제거 | - | `supplier` |
| usage 필드 변경 | `work_id` | `maintenance_id` |

---

## 마이그레이션 실행 순서

> [!IMPORTANT]
> 반드시 다음 순서로 실행하세요:

```bash
# 1. vehicle_works 테이블 리네임 (먼저 실행)
mysql -u [username] -p [database] < database/migrations/rename_vehicle_works_to_maintenance.sql

# 2. 소모품 테이블 생성 (나중 실행)
mysql -u [username] -p [database] < database/migrations/create_vehicle_consumables.sql
```

---

## 권한 및 메뉴

기존과 동일:
- 권한: `vehicle.consumable.view`, `vehicle.consumable.manage`, `vehicle.consumable.stock`
- 메뉴: `/vehicles/consumables`

시드 파일: `database/seeds/10_vehicle_consumable_permissions.sql`, `11_vehicle_consumable_menu.sql`
