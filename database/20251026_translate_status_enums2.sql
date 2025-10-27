-- 데이터베이스 상태 값 한글화 추가 수정 스크립트
-- 버전: 2.1
-- 변경 사항:
-- 1. `waste_collections`의 기본 상태 값을 '대기'에서 '미처리'로 변경.
-- 2. `illegal_disposal_cases2`에 '삭제' 상태 추가.

-- =================================================================
-- 1. 데이터 마이그레이션 (UPDATE)
-- =================================================================

-- waste_collections: status
UPDATE `waste_collections` SET `status` =
    CASE
        WHEN `status` = '대기' THEN '미처리'
        ELSE `status`
    END;

-- illegal_disposal_cases2: status
UPDATE `illegal_disposal_cases2` SET `status` =
    CASE
        WHEN `status` = 'deleted' THEN '삭제'
        ELSE `status`
    END;

-- =================================================================
-- 2. 스키마 변경 (ALTER TABLE)
-- =================================================================

-- waste_collections
ALTER TABLE `waste_collections` MODIFY `status` ENUM('미처리','처리완료') NOT NULL DEFAULT '미처리' COMMENT '수거 상태';

-- illegal_disposal_cases2
ALTER TABLE `illegal_disposal_cases2` MODIFY `status` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '처리 상태 (대기, 확인, 처리완료, 삭제)';
