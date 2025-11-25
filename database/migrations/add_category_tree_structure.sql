-- ============================================================
-- 차량 소모품 관리 카테고리 트리 구조 마이그레이션
-- ============================================================

-- 안전을 위해 기존 데이터 백업
CREATE TABLE IF NOT EXISTS backup_vehicle_consumables_categories_20251125 
AS SELECT * FROM vehicle_consumables_categories;

CREATE TABLE IF NOT EXISTS backup_vehicle_consumable_stock_20251125 
AS SELECT * FROM vehicle_consumable_stock;

CREATE TABLE IF NOT EXISTS backup_vehicle_consumable_usage_20251125 
AS SELECT * FROM vehicle_consumable_usage;

-- ============================================================
-- 1. 카테고리 테이블에 트리 구조 추가
-- ============================================================

ALTER TABLE vehicle_consumables_categories
    ADD COLUMN parent_id INT NULL AFTER id,
    ADD COLUMN level INT DEFAULT 1 AFTER parent_id,
    ADD COLUMN path VARCHAR(500) NULL AFTER level,
    ADD COLUMN sort_order INT DEFAULT 0 AFTER path;

-- 외래 키 및 인덱스 추가
ALTER TABLE vehicle_consumables_categories
    ADD CONSTRAINT fk_category_parent 
        FOREIGN KEY (parent_id) REFERENCES vehicle_consumables_categories(id) 
        ON DELETE RESTRICT,
    ADD INDEX idx_parent (parent_id),
    ADD INDEX idx_level (level),
    ADD INDEX idx_path (path);

-- 기존 데이터에 level, path 설정 (모두 최상위로)
UPDATE vehicle_consumables_categories 
SET level = 1, 
    path = CAST(id AS CHAR),
    sort_order = id;

-- ============================================================
-- 2. 입고 테이블 수정 (consumable_id → category_id + item_name)
-- ============================================================

-- 새 컬럼 추가
ALTER TABLE vehicle_consumable_stock
    ADD COLUMN category_id INT NULL AFTER id,
    ADD COLUMN item_name VARCHAR(200) NULL AFTER category_id;

-- 기존 데이터 마이그레이션: consumable_id를 category_id로, name을 item_name으로
UPDATE vehicle_consumable_stock s
INNER JOIN vehicle_consumables_categories c ON s.consumable_id = c.id
SET s.category_id = c.id,
    s.item_name = c.name;

-- NULL 체크 (마이그레이션 검증)
SELECT COUNT(*) AS unmigrated_count 
FROM vehicle_consumable_stock 
WHERE category_id IS NULL OR item_name IS NULL;

-- NOT NULL 제약 조건 추가
ALTER TABLE vehicle_consumable_stock
    MODIFY COLUMN category_id INT NOT NULL,
    MODIFY COLUMN item_name VARCHAR(200) NOT NULL;

-- 외래 키 추가
ALTER TABLE vehicle_consumable_stock
    ADD CONSTRAINT fk_stock_category 
        FOREIGN KEY (category_id) REFERENCES vehicle_consumables_categories(id) 
        ON DELETE RESTRICT,
    ADD INDEX idx_stock_category (category_id),
    ADD INDEX idx_stock_item_name (item_name);

-- consumable_id 제거 (외래 키 먼저 제거)
ALTER TABLE vehicle_consumable_stock
    DROP FOREIGN KEY IF EXISTS fk_stock_consumable,
    DROP COLUMN consumable_id;

-- ============================================================
-- 3. 사용 테이블 수정 (consumable_id → category_id + item_name)
-- ============================================================

-- 새 컬럼 추가
ALTER TABLE vehicle_consumable_usage
    ADD COLUMN category_id INT NULL AFTER id,
    ADD COLUMN item_name VARCHAR(200) NULL AFTER category_id;

-- 기존 데이터 마이그레이션
UPDATE vehicle_consumable_usage u
INNER JOIN vehicle_consumables_categories c ON u.consumable_id = c.id
SET u.category_id = c.id,
    u.item_name = c.name;

-- NULL 체크
SELECT COUNT(*) AS unmigrated_count 
FROM vehicle_consumable_usage 
WHERE category_id IS NULL;

-- NOT NULL 제약 조건 추가 (item_name은 NULL 허용)
ALTER TABLE vehicle_consumable_usage
    MODIFY COLUMN category_id INT NOT NULL;

-- 외래 키 추가
ALTER TABLE vehicle_consumable_usage
    ADD CONSTRAINT fk_usage_category 
        FOREIGN KEY (category_id) REFERENCES vehicle_consumables_categories(id) 
        ON DELETE RESTRICT,
    ADD INDEX idx_usage_category (category_id),
    ADD INDEX idx_usage_item_name (item_name);

-- consumable_id 제거
ALTER TABLE vehicle_consumable_usage
    DROP FOREIGN KEY IF EXISTS fk_usage_consumable,
    DROP COLUMN consumable_id;

-- ============================================================
-- 4. 샘플 카테고리 트리 데이터 (선택사항)
-- ============================================================

-- 기존 카테고리들을 그대로 사용하거나, 새로운 트리 구조 생성
-- 예시: 대분류 추가
/*
INSERT INTO vehicle_consumables_categories (name, parent_id, level, path, unit, sort_order) VALUES
('엔진 소모품', NULL, 1, NULL, '', 1),
('구동계 소모품', NULL, 1, NULL, '', 2),
('전기 소모품', NULL, 1, NULL, '', 3);

-- 중분류 추가 (parent_id를 대분류의 id로 설정)
INSERT INTO vehicle_consumables_categories (name, parent_id, level, path, unit, sort_order) VALUES
('엔진오일', 1, 2, '1/4', '리터', 1),
('필터류', 1, 2, '1/5', '개', 2);

-- 소분류 추가
INSERT INTO vehicle_consumables_categories (name, parent_id, level, path, unit, sort_order) VALUES
('5W-30', 4, 3, '1/4/6', '리터', 1),
('10W-40', 4, 3, '1/4/7', '리터', 2),
('에어필터', 5, 3, '1/5/8', '개', 1);
*/

-- ============================================================
-- 검증 쿼리
-- ============================================================

-- 카테고리 트리 확인
SELECT 
    id,
    CONCAT(REPEAT('  ', level - 1), name) AS category_tree,
    unit,
    parent_id,
    level,
    path
FROM vehicle_consumables_categories
ORDER BY path;

-- 재고 확인 (카테고리별)
SELECT 
    c.name AS category,
    c.unit,
    COUNT(DISTINCT s.item_name) AS item_count,
    SUM(s.quantity) AS total_stock_in,
    COALESCE(SUM(u.quantity), 0) AS total_usage,
    SUM(s.quantity) - COALESCE(SUM(u.quantity), 0) AS current_stock
FROM vehicle_consumables_categories c
LEFT JOIN vehicle_consumable_stock s ON c.id = s.category_id
LEFT JOIN vehicle_consumable_usage u ON c.id = u.category_id
GROUP BY c.id
ORDER BY c.path;

-- 품명별 재고 확인
SELECT 
    c.name AS category,
    s.item_name,
    c.unit,
    SUM(s.quantity) AS stock_in,
    COALESCE((
        SELECT SUM(quantity) 
        FROM vehicle_consumable_usage 
        WHERE category_id = c.id AND item_name = s.item_name
    ), 0) AS used,
    SUM(s.quantity) - COALESCE((
        SELECT SUM(quantity) 
        FROM vehicle_consumable_usage 
        WHERE category_id = c.id AND item_name = s.item_name
    ), 0) AS current_stock
FROM vehicle_consumable_stock s
INNER JOIN vehicle_consumables_categories c ON s.category_id = c.id
GROUP BY c.id, s.item_name
ORDER BY c.path, s.item_name;

-- ============================================================
-- 롤백 스크립트 (문제 발생 시 사용)
-- ============================================================

/*
-- 백업에서 복원
DROP TABLE IF EXISTS vehicle_consumables_categories;
DROP TABLE IF EXISTS vehicle_consumable_stock;
DROP TABLE IF EXISTS vehicle_consumable_usage;

CREATE TABLE vehicle_consumables_categories AS 
    SELECT * FROM backup_vehicle_consumables_categories_20251125;
CREATE TABLE vehicle_consumable_stock AS 
    SELECT * FROM backup_vehicle_consumable_stock_20251125;
CREATE TABLE vehicle_consumable_usage AS 
    SELECT * FROM backup_vehicle_consumable_usage_20251125;

-- 인덱스 및 제약조건 재생성 필요
*/
