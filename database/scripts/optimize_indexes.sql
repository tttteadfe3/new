-- ============================================================
-- 데이터베이스 인덱스 최적화 스크립트
-- 생성일: 2025-11-27
-- 목적: 중복 인덱스 제거하여 성능 및 저장공간 최적화
-- ============================================================

-- 스키마 분석 결과, 다음 2개의 중복 인덱스가 발견되었습니다:
-- 1. supply_items.idx_item_code: UNIQUE(item_code)로 충분
-- 2. supply_stocks.idx_item_id: UNIQUE(item_id)로 충분

-- ============================================================
-- 1. supply_items 테이블 - 중복 인덱스 제거
-- ============================================================

-- 현재 상태:
--   - UNIQUE KEY `item_code` (item_code)  ← 이미 UNIQUE 인덱스 존재
--   - KEY `idx_item_code` (item_code)     ← 중복 인덱스

-- 중복 인덱스 확인
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'erp'
  AND TABLE_NAME = 'supply_items'
  AND COLUMN_NAME = 'item_code'
ORDER BY INDEX_NAME;

-- 중복 인덱스 제거
ALTER TABLE supply_items 
DROP INDEX idx_item_code;

-- 확인: UNIQUE 인덱스만 남았는지 확인
SHOW INDEX FROM supply_items WHERE Column_name = 'item_code';


-- ============================================================
-- 2. supply_stocks 테이블 - 중복 인덱스 제거
-- ============================================================

-- 현재 상태:
--   - UNIQUE KEY `item_id` (item_id)  ← 이미 UNIQUE 인덱스 존재
--   - KEY `idx_item_id` (item_id)     ← 중복 인덱스

-- 중복 인덱스 확인
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'erp'
  AND TABLE_NAME = 'supply_stocks'
  AND COLUMN_NAME = 'item_id'
ORDER BY INDEX_NAME;

-- 중복 인덱스 제거
ALTER TABLE supply_stocks 
DROP INDEX idx_item_id;

-- 확인: UNIQUE 인덱스만 남았는지 확인
SHOW INDEX FROM supply_stocks WHERE Column_name = 'item_id';


-- ============================================================
-- 최종 검증
-- ============================================================

-- supply_items 테이블의 모든 인덱스 확인
SHOW INDEX FROM supply_items;

-- supply_stocks 테이블의 모든 인덱스 확인
SHOW INDEX FROM supply_stocks;

-- 최적화 완료 메시지
SELECT '중복 인덱스 제거 완료' AS status,
       '2개의 중복 인덱스가 제거되었습니다' AS message;


-- ============================================================
-- 참고사항
-- ============================================================

-- 1. UNIQUE 인덱스는 일반 인덱스의 기능을 모두 포함합니다
--    - WHERE item_code = 'XXX' 조회에 사용 가능
--    - 중복 값 방지 기능 추가
--    - 별도의 일반 인덱스가 불필요함

-- 2. 인덱스 제거의 효과:
--    - 저장 공간 절약
--    - INSERT/UPDATE/DELETE 성능 향상
--    - 인덱스 유지보수 부하 감소

-- 3. 외래 키 인덱스는 모두 적절하게 설정되어 있음
--    - 총 51개의 외래 키
--    - 모든 외래 키 컬럼에 인덱스 존재 (검증 완료)

-- 4. 백업 테이블 정리 (선택사항):
--    - backup_vehicle_consumable_stock_20251125
--    - backup_vehicle_consumable_usage_20251125
--    - backup_vehicle_consumables_categories_20251125
--    위 테이블들은 2025-11-25 백업으로, 필요시 삭제 가능

-- ============================================================
-- 백업 테이블 삭제 (선택사항 - 주석 해제하여 실행)
-- ============================================================

-- DROP TABLE IF EXISTS backup_vehicle_consumable_stock_20251125;
-- DROP TABLE IF EXISTS backup_vehicle_consumable_usage_20251125;
-- DROP TABLE IF EXISTS backup_vehicle_consumables_categories_20251125;
