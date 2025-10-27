-- 데이터베이스 상태 값 한글화를 위한 마이그레이션 스크립트
-- 버전: 2.0 (리뷰 피드백 반영)
-- 변경 사항:
-- 1. 모든 영어 상태 값을 일관된 한글 값으로 변경
-- 2. `sys_users` 테이블에 '차단' 상태 추가
-- 3. `illegal_disposal_cases2`의 'processed' 상태 누락 수정 및 값 통일
-- 4. `waste_collections`의 'processed' 상태 누락 수정

-- =================================================================
-- 1. 데이터 마이그레이션 (UPDATE)
-- =================================================================

-- hr_employees: profile_update_status
UPDATE `hr_employees` SET `profile_update_status` =
    CASE
        WHEN `profile_update_status` = 'pending' THEN '대기'
        WHEN `profile_update_status` = 'rejected' THEN '반려'
        ELSE `profile_update_status`
    END;

-- hr_leaves: status
UPDATE `hr_leaves` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'approved' THEN '승인'
        WHEN `status` = 'rejected' THEN '반려'
        WHEN `status` = 'cancelled' THEN '취소'
        WHEN `status` = 'cancellation_requested' THEN '취소요청'
        ELSE `status`
    END;

-- illegal_disposal_cases2: status
UPDATE `illegal_disposal_cases2` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'confirmed' THEN '확인'
        WHEN `status` = 'processed' THEN '처리완료'
        ELSE `status`
    END;

-- sys_users: status
UPDATE `sys_users` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'active' THEN '활성'
        WHEN `status` = 'inactive' THEN '비활성'
        WHEN `status` = 'deleted' THEN '삭제'
        WHEN `status` = 'blocked' THEN '차단' -- 'blocked' 상태 추가
        ELSE `status`
    END;

-- waste_collections: status, geocoding_status
UPDATE `waste_collections` SET `status` =
    CASE
        WHEN `status` = 'pending' THEN '대기'
        WHEN `status` = 'processed' THEN '처리완료'
        ELSE `status`
    END;

UPDATE `waste_collections` SET `geocoding_status` =
    CASE
        WHEN `geocoding_status` = 'success' THEN '성공'
        WHEN `geocoding_status` = 'failure' THEN '실패'
        ELSE `geocoding_status`
    END;

-- =================================================================
-- 2. 스키마 변경 (ALTER TABLE)
-- =================================================================

-- hr_employees
ALTER TABLE `hr_employees` MODIFY `profile_update_status` ENUM('none','대기','반려') NOT NULL DEFAULT 'none' COMMENT '프로필 수정 요청 상태';

-- hr_leaves
ALTER TABLE `hr_leaves` MODIFY `status` ENUM('대기','승인','반려','취소','취소요청') NOT NULL DEFAULT '대기' COMMENT '신청 상태';

-- illegal_disposal_cases2
ALTER TABLE `illegal_disposal_cases2` MODIFY `status` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '처리 상태 (대기, 확인, 처리완료)';

-- sys_users
ALTER TABLE `sys_users` MODIFY `status` ENUM('대기','활성','비활성','삭제','차단') NOT NULL DEFAULT '대기' COMMENT '계정 상태';

-- waste_collections
ALTER TABLE `waste_collections` MODIFY `status` ENUM('대기','처리완료') NOT NULL DEFAULT '대기' COMMENT '수거 상태';
ALTER TABLE `waste_collections` MODIFY `geocoding_status` ENUM('성공','실패') NOT NULL DEFAULT '실패' COMMENT '지오코딩 상태';
