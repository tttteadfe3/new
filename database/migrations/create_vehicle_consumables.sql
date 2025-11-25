-- =====================================================
-- 차량 소모품 관리 시스템 - 재구성된 데이터베이스 스키마
-- =====================================================
-- 워크플로우:
-- 1. vehicle_consumables_categories: 분류 설정 (최소 기능)
-- 2. vehicle_consumable_stock: 재고 등록
-- 3. 정비/자체수리 시 재고 부품 사용
-- 4. vehicle_consumable_usage: 사용 이력 등록
-- =====================================================

-- =====================================================
-- 1. 소모품 분류 테이블 (간소화)
-- =====================================================
CREATE TABLE `vehicle_consumables_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '소모품명',
  `category` varchar(100) DEFAULT NULL COMMENT '카테고리 (엔진오일, 타이어, 브레이크, 필터 등)',
  `unit` varchar(50) DEFAULT '개' COMMENT '단위',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 소모품 분류';

-- =====================================================
-- 2. 소모품 재고 테이블
-- =====================================================
CREATE TABLE `vehicle_consumable_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `quantity` int(11) NOT NULL COMMENT '입고 수량',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '입고 단가',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `registered_by` int(11) DEFAULT NULL COMMENT '등록자 employee_id',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_purchase_date` (`purchase_date`),
  CONSTRAINT `fk_stock_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 재고 입고';

-- =====================================================
-- 3. 소모품 사용 이력 테이블
-- =====================================================
CREATE TABLE `vehicle_consumable_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `maintenance_id` int(11) DEFAULT NULL COMMENT '정비 작업 ID (vehicle_maintenance)',
  `vehicle_id` int(11) DEFAULT NULL COMMENT '차량 ID',
  `quantity` int(11) NOT NULL COMMENT '사용 수량',
  `used_by` int(11) DEFAULT NULL COMMENT '사용자 employee_id',
  `used_at` datetime DEFAULT current_timestamp() COMMENT '사용일시',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_maintenance_id` (`maintenance_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_consumable_usage_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_consumable_usage_maintenance` FOREIGN KEY (`maintenance_id`) REFERENCES `vehicle_maintenance` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 사용 이력';

-- =====================================================
-- 재고 현황 뷰 (현재 재고 계산)
-- =====================================================
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

-- =====================================================
-- 샘플 데이터
-- =====================================================

-- 소모품 분류 등록

-- 현재 재고 확인
SELECT * FROM v_vehicle_consumable_inventory;

-- 분류별 총 재고 가치
SELECT 
    category,
    SUM(current_stock) as total_quantity,
    SUM(current_stock * (
        SELECT AVG(unit_price) 
        FROM vehicle_consumable_stock s2 
        WHERE s2.consumable_id = v.consumable_id
    )) as estimated_value
FROM v_vehicle_consumable_inventory v
GROUP BY category;
