-- =================================================================
-- 데이터베이스 상태 값 한글화 마이그레이션 (영어→한글)
-- 버전: 3.0 (모든 테이블 통합 및 최종 수정)
-- 이 스크립트는 코드베이스와 데이터베이스의 모든 상태 값을 한글로 통일합니다.
-- =================================================================
-- 중요: 실행 전 반드시 데이터베이스 백업을 수행하세요!
-- =================================================================

-- 트랜잭션 시작
START TRANSACTION;

-- =================================================================
-- 1단계: ENUM에 한글 값 추가 (기존 영어 값과 한글 값이 공존)
-- =================================================================

-- 테이블: hr_employees
ALTER TABLE hr_employees MODIFY COLUMN profile_update_status ENUM('none','pending','rejected','대기','반려') NOT NULL DEFAULT 'none';

-- 테이블: hr_leaves
ALTER TABLE hr_leaves MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled','cancellation_requested','대기','승인','반려','취소','취소요청') NOT NULL DEFAULT 'pending';

-- 테이블: sys_users
ALTER TABLE sys_users MODIFY COLUMN status ENUM('pending','active','inactive','deleted','blocked','대기','활성','비활성','삭제','차단') NOT NULL DEFAULT 'pending';

-- 테이블: waste_collections (수거 상태)
-- 코드베이스에 맞춰 'unprocessed'/'processed' -> '미처리'/'처리완료'로 변경
ALTER TABLE waste_collections MODIFY COLUMN status ENUM('unprocessed','processed','미처리','처리완료') NOT NULL DEFAULT 'unprocessed';

-- 테이블: waste_collections (지오코딩 상태)
-- 코드베이스에 맞춰 'success'/'failure' -> '성공'/'실패'로 변경
ALTER TABLE waste_collections MODIFY COLUMN geocoding_status ENUM('success','failure','성공','실패') NOT NULL DEFAULT 'failure';


-- =================================================================
-- 2단계: 기존 영어 데이터를 한글 데이터로 업데이트
-- =================================================================

-- 테이블: hr_employees
UPDATE hr_employees SET profile_update_status =
    CASE profile_update_status
        WHEN 'pending' THEN '대기'
        WHEN 'rejected' THEN '반려'
        ELSE profile_update_status
    END
WHERE profile_update_status IN ('pending', 'rejected');

-- 테이블: hr_leaves
UPDATE hr_leaves SET status =
    CASE status
        WHEN 'pending' THEN '대기'
        WHEN 'approved' THEN '승인'
        WHEN 'rejected' THEN '반려'
        WHEN 'cancelled' THEN '취소'
        WHEN 'cancellation_requested' THEN '취소요청'
        ELSE status
    END
WHERE status IN ('pending', 'approved', 'rejected', 'cancelled', 'cancellation_requested');

-- 테이블: sys_users
UPDATE sys_users SET status =
    CASE status
        WHEN 'pending' THEN '대기'
        WHEN 'active' THEN '활성'
        WHEN 'inactive' THEN '비활성'
        WHEN 'deleted' THEN '삭제'
        WHEN 'blocked' THEN '차단'
        ELSE status
    END
WHERE status IN ('pending', 'active', 'inactive', 'deleted', 'blocked');

-- 테이블: waste_collections (수거 상태)
UPDATE waste_collections SET status =
    CASE status
        WHEN 'unprocessed' THEN '미처리'
        WHEN 'processed' THEN '처리완료'
        ELSE status
    END
WHERE status IN ('unprocessed', 'processed');

-- 테이블: waste_collections (지오코딩 상태)
UPDATE waste_collections SET geocoding_status =
    CASE geocoding_status
        WHEN 'success' THEN '성공'
        WHEN 'failure' THEN '실패'
        ELSE geocoding_status
    END
WHERE geocoding_status IN ('success', 'failure');

-- 테이블: illegal_disposal_cases2 (VARCHAR 타입이므로 직접 업데이트)
UPDATE illegal_disposal_cases2 SET status =
    CASE status
        WHEN 'pending' THEN '대기'
        WHEN 'confirmed' THEN '확인'
        WHEN 'processed' THEN '처리완료'
        ELSE status
    END
WHERE status IN ('pending', 'confirmed', 'processed');


-- =================================================================
-- 3단계: ENUM에서 더 이상 사용하지 않는 영어 값 제거
-- =================================================================

-- 테이블: hr_employees
ALTER TABLE hr_employees MODIFY COLUMN profile_update_status ENUM('none','대기','반려') NOT NULL DEFAULT 'none' COMMENT '프로필 수정 요청 상태';

-- 테이블: hr_leaves
ALTER TABLE hr_leaves MODIFY COLUMN status ENUM('대기','승인','반려','취소','취소요청') NOT NULL DEFAULT '대기' COMMENT '휴가 신청 상태';

-- 테이블: sys_users
ALTER TABLE sys_users MODIFY COLUMN status ENUM('대기','활성','비활성','삭제','차단') NOT NULL DEFAULT '대기' COMMENT '시스템 사용자 계정 상태';

-- 테이블: waste_collections (수거 상태)
ALTER TABLE waste_collections MODIFY COLUMN status ENUM('미처리','처리완료') NOT NULL DEFAULT '미처리' COMMENT '폐기물 수거 상태';

-- 테이블: waste_collections (지오코딩 상태)
ALTER TABLE waste_collections MODIFY COLUMN geocoding_status ENUM('성공','실패') NOT NULL DEFAULT '실패' COMMENT '지오코딩 성공/실패 상태';


-- =================================================================
-- 마이그레이션 완료
-- =================================================================

COMMIT;

-- =================================================================
-- 검증 쿼리 (마이그레이션 후 실행하여 확인)
-- =================================================================

SELECT 'hr_employees' as table_name, profile_update_status as status, COUNT(*) as count FROM hr_employees GROUP BY profile_update_status;
SELECT 'hr_leaves' as table_name, status, COUNT(*) as count FROM hr_leaves GROUP BY status;
SELECT 'sys_users' as table_name, status, COUNT(*) as count FROM sys_users GROUP BY status;
SELECT 'waste_collections (status)' as table_name, status, COUNT(*) as count FROM waste_collections GROUP BY status;
SELECT 'waste_collections (geocoding_status)' as table_name, geocoding_status as status, COUNT(*) as count FROM waste_collections GROUP BY geocoding_status;
SELECT 'illegal_disposal_cases2' as table_name, status, COUNT(*) as count FROM illegal_disposal_cases2 GROUP BY status;
