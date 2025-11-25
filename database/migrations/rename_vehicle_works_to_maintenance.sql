-- =====================================================
-- vehicle_works 테이블을 vehicle_maintenance로 리네임
-- =====================================================
-- 실행 순서: create_vehicle_consumables.sql 보다 먼저 실행

-- 1. 테이블 리네임
RENAME TABLE `vehicle_works` TO `vehicle_maintenance`;

-- 2. 확인
SHOW TABLES LIKE 'vehicle_maintenance';
DESC vehicle_maintenance;
