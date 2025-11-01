-- 기존 연차 관리 시스템의 레거시 테이블 삭제

-- 주의: 이 스크립트는 기존 데이터를 영구적으로 삭제합니다.
-- 새로운 로그 기반 시스템으로 마이그레이션이 완료된 후에만 실행해야 합니다.

DROP TABLE IF EXISTS `hr_leaves`;
DROP TABLE IF EXISTS `hr_leave_adjustments_log`;
DROP TABLE IF EXISTS `hr_leave_entitlements`;

COMMIT;
