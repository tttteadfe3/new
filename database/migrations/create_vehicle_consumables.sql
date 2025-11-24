-- 차량 소모품/부품 관리 테이블
CREATE TABLE `vehicle_consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '소모품명',
  `category` varchar(100) DEFAULT NULL COMMENT '카테고리 (엔진오일, 타이어, 브레이크, 필터 등)',
  `part_number` varchar(100) DEFAULT NULL COMMENT '부품 번호',
  `unit` varchar(50) DEFAULT '개' COMMENT '단위',
  `unit_price` decimal(10,2) DEFAULT 0.00 COMMENT '단가',
  `current_stock` int(11) DEFAULT 0 COMMENT '현재 재고',
  `minimum_stock` int(11) DEFAULT 0 COMMENT '최소 재고량',
  `location` varchar(255) DEFAULT NULL COMMENT '보관 위치',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 소모품/부품 재고';

-- 소모품 사용 이력 테이블
CREATE TABLE `vehicle_consumable_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 ID',
  `work_id` int(11) DEFAULT NULL COMMENT '작업 ID (vehicle_works)',
  `vehicle_id` int(11) DEFAULT NULL COMMENT ' 차량 ID',
  `quantity` int(11) NOT NULL COMMENT '사용 수량',
  `used_by` int(11) DEFAULT NULL COMMENT '사용자 employee_id',
  `used_at` datetime DEFAULT current_timestamp() COMMENT '사용일시',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_work_id` (`work_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_consumable_usage_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_consumable_usage_work` FOREIGN KEY (`work_id`) REFERENCES `vehicle_works` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 사용 이력';

-- 소모품 입고 이력 테이블
CREATE TABLE `vehicle_consumable_stock_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 ID',
  `quantity` int(11) NOT NULL COMMENT '입고 수량',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '입고 단가',
  `supplier` varchar(255) DEFAULT NULL COMMENT '공급업체',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `registered_by` int(11) DEFAULT NULL COMMENT '등록자 employee_id',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_purchase_date` (`purchase_date`),
  CONSTRAINT `fk_stock_in_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 입고 이력';
