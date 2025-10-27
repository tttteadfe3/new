-- 데이터베이스 상태 값 한글화 마이그레이션 (영어→한글)
-- 버전: 2.2 (ENUM 제약 오류 완전 해결)
-- =================================================================
-- 중요: 실행 전 반드시 데이터베이스 백업을 수행하세요!
-- =================================================================

-- 트랜잭션 시작
START TRANSACTION;

-- =================================================================
-- 1단계: ENUM에 한글 값 추가 (영어+한글 공존)
-- =================================================================

-- hr_employees
ALTER TABLE `hr_employees` MODIFY `profile_update_status` 
    ENUM('none','pending','rejected','대기','반려') NOT NULL DEFAULT 'none';

-- hr_leaves
ALTER TABLE `hr_leaves` MODIFY `status` 
    ENUM('pending','approved','rejected','cancelled','cancellation_requested','대기','승인','반려','취소','취소요청') NOT NULL DEFAULT 'pending';

-- sys_users
ALTER TABLE `sys_users` MODIFY `status` 
    ENUM('pending','active','inactive','deleted','blocked','대기','활성','비활성','삭제','차단') NOT NULL DEFAULT 'pending';

-- waste_collections: status
ALTER TABLE `waste_collections` MODIFY `status` 
    ENUM('pending','processed','대기','처리완료') NOT NULL DEFAULT 'pending';

-- waste_collections: geocoding_status
ALTER TABLE `waste_collections` MODIFY `geocoding_status` 
    ENUM('success','failure','성공','실패') NOT NULL DEFAULT 'failure';

-- =================================================================
-- 2단계: 데이터를 영어에서 한글로 변경
-- =================================================================

-- hr_employees
UPDATE `hr_employees` SET `profile_update_status` =
    CASE
        WHEN `profile_update_status` = 'pending' THEN '대기'
        WHEN `profile_update_status` = 'rejected' THEN '반려'
        ELSE `profile_update_status`
    END
WHERE `profile_update_status` IN ('pending', 'rejected');

-- hr_leaves
UPDATE `hr_leaves` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'approved' THEN '승인'
        WHEN `status` = 'rejected' THEN '반려'
        WHEN `status` = 'cancelled' THEN '취소'
        WHEN `status` = 'cancellation_requested' THEN '취소요청'
        ELSE `status`
    END
WHERE `status` IN ('pending', 'approved', 'rejected', 'cancelled', 'cancellation_requested');

-- illegal_disposal_cases2 (VARCHAR 타입이므로 바로 변경 가능)
UPDATE `illegal_disposal_cases2` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'confirmed' THEN '확인'
        WHEN `status` = 'processed' THEN '처리완료'
        ELSE `status`
    END
WHERE `status` IN ('pending', 'confirmed', 'processed');

-- sys_users
UPDATE `sys_users` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'active' THEN '활성'
        WHEN `status` = 'inactive' THEN '비활성'
        WHEN `status` = 'deleted' THEN '삭제'
        WHEN `status` = 'blocked' THEN '차단'
        ELSE `status`
    END
WHERE `status` IN ('pending', 'active', 'inactive', 'deleted', 'blocked');

-- waste_collections: status
UPDATE `waste_collections` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'processed' THEN '처리완료'
        ELSE `status`
    END
WHERE `status` IN ('pending', 'processed');

-- waste_collections: geocoding_status
UPDATE `waste_collections` SET `geocoding_status` =
    CASE
        WHEN `geocoding_status` = 'success' THEN '성공'
        WHEN `geocoding_status` = 'failure' THEN '실패'
        ELSE `geocoding_status`
    END
WHERE `geocoding_status` IN ('success', 'failure');

-- =================================================================
-- 3단계: ENUM에서 영어 값 제거 (한글만 남김)
-- =================================================================

-- hr_employees
ALTER TABLE `hr_employees` MODIFY `profile_update_status` 
    ENUM('none','대기','반려') NOT NULL DEFAULT 'none' COMMENT '프로필 수정 요청 상태';

-- hr_leaves
ALTER TABLE `hr_leaves` MODIFY `status` 
    ENUM('대기','승인','반려','취소','취소요청') NOT NULL DEFAULT '대기' COMMENT '신청 상태';

-- sys_users
ALTER TABLE `sys_users` MODIFY `status` 
    ENUM('대기','활성','비활성','삭제','차단') NOT NULL DEFAULT '대기' COMMENT '계정 상태';

-- waste_collections: status
ALTER TABLE `waste_collections` MODIFY `status` 
    ENUM('대기','처리완료') NOT NULL DEFAULT '대기' COMMENT '수거 상태';

-- waste_collections: geocoding_status
ALTER TABLE `waste_collections` MODIFY `geocoding_status` 
    ENUM('성공','실패') NOT NULL DEFAULT '실패' COMMENT '지오코딩 상태';

-- =================================================================
-- 마이그레이션 완료 - 커밋
-- =================================================================

COMMIT;

-- =================================================================
-- 검증 쿼리 (마이그레이션 후 실행하여 확인)
-- =================================================================

-- 각 테이블의 상태 값 분포 확인
SELECT 'hr_employees' as table_name, profile_update_status as status, COUNT(*) as count 
FROM hr_employees GROUP BY profile_update_status;

SELECT 'hr_leaves' as table_name, status, COUNT(*) as count 
FROM hr_leaves GROUP BY status;

SELECT 'illegal_disposal_cases2' as table_name, status, COUNT(*) as count 
FROM illegal_disposal_cases2 GROUP BY status;

SELECT 'sys_users' as table_name, status, COUNT(*) as count 
FROM sys_users GROUP BY status;

SELECT 'waste_collections (status)' as table_name, status, COUNT(*) as count 
FROM waste_collections GROUP BY status;

SELECT 'waste_collections (geocoding)' as table_name, geocoding_status as status, COUNT(*) as count 
FROM waste_collections GROUP BY geocoding_status;
