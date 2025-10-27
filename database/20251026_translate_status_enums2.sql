-- 데이터베이스 상태 값 추가 수정 및 안전한 마이그레이션 적용
-- 버전: 2.3 (안전한 ENUM 마이그레이션 적용)
-- 변경 사항:
-- 1. waste_collections.status의 '대기'를 '미처리'로 변경 (코드와 일치시키기 위함).
-- 2. waste_collections.geocoding_status를 한글로 변경.
-- 3. illegal_disposal_cases2.status를 ENUM 타입으로 변경하고 '삭제' 상태 추가.
-- =================================================================
-- 중요: 실행 전 반드시 데이터베이스 백업을 수행하세요!
-- =================================================================

START TRANSACTION;

-- =================================================================
-- 1단계: ENUM에 새로운 값 추가 (임시 공존)
-- =================================================================

-- waste_collections: status ('미처리' 추가)
ALTER TABLE `waste_collections` MODIFY `status`
    ENUM('대기','처리완료','미처리') NOT NULL DEFAULT '대기';

-- waste_collections: geocoding_status (한글 값 추가)
ALTER TABLE `waste_collections` MODIFY `geocoding_status`
    ENUM('success','failure','성공','실패') NOT NULL DEFAULT 'failure';

-- =================================================================
-- 2단계: 데이터 마이그레이션
-- =================================================================

-- waste_collections: status ('대기' -> '미처리')
UPDATE `waste_collections` SET `status` = '미처리' WHERE `status` = '대기';

-- waste_collections: geocoding_status (영어 -> 한글)
UPDATE `waste_collections` SET `geocoding_status` =
    CASE
        WHEN `geocoding_status` = 'success' THEN '성공'
        WHEN `geocoding_status` = 'failure' THEN '실패'
        ELSE `geocoding_status`
    END
WHERE `geocoding_status` IN ('success', 'failure');

-- illegal_disposal_cases2: status ('deleted' -> '삭제')
-- 이 테이블은 현재 VARCHAR 타입이므로, ENUM으로 변경하기 전에 데이터 정제
UPDATE `illegal_disposal_cases2` SET `status` = '삭제' WHERE `status` = 'deleted';

-- =================================================================
-- 3단계: ENUM에서 이전 값 제거 및 타입 변경
-- =================================================================

-- waste_collections: status ('대기' 제거)
ALTER TABLE `waste_collections` MODIFY `status`
    ENUM('미처리','처리완료') NOT NULL DEFAULT '미처리' COMMENT '수거 상태';

-- waste_collections: geocoding_status (영어 값 제거)
ALTER TABLE `waste_collections` MODIFY `geocoding_status`
    ENUM('성공','실패') NOT NULL DEFAULT '실패' COMMENT '지오코딩 상태';

-- illegal_disposal_cases2 (VARCHAR -> ENUM으로 변경 및 '삭제' 포함)
-- 이 시점에는 모든 status 값이 목표 ENUM 값들 중 하나여야 함
ALTER TABLE `illegal_disposal_cases2` MODIFY `status`
    ENUM('대기','확인','처리완료','승인완료','삭제') NOT NULL DEFAULT '대기' COMMENT '처리 상태';

COMMIT;
