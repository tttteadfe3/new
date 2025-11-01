-- 연차 관리 시스템 개편을 위한 신규 테이블 생성
-- 기존 상태 기반 테이블(hr_leaves, hr_leave_entitlements, hr_leave_adjustments_log)은 더 이상 사용하지 않음.
-- 모든 연차/월차 변동은 hr_leave_logs 테이블에 로그로 기록됨.

-- 1. 연차 신청 및 승인/반려/취소 이력 관리 테이블
CREATE TABLE `hr_leave_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` INT(11) NOT NULL COMMENT '신청 직원 ID (hr_employees.id)',
  `leave_type` ENUM('annual', 'monthly') NOT NULL COMMENT '휴가 종류 (연차/월차)',

  -- 신청 내용
  `request_unit` ENUM('full', 'half_am', 'half_pm') NOT NULL COMMENT '신청 단위 (전일/오전반차/오후반차)',
  `start_date` DATE NOT NULL COMMENT '휴가 시작일',
  `end_date` DATE NOT NULL COMMENT '휴가 종료일',
  `days_count` DECIMAL(4, 2) NOT NULL COMMENT '휴가 일수 (0.5, 1.0, 1.5 등)',
  `reason` TEXT DEFAULT NULL COMMENT '휴가 신청 사유',

  -- 처리 상태 및 이력
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'cancellation_requested', 'cancellation_approved') NOT NULL DEFAULT 'pending' COMMENT '신청 상태',
  `approver_employee_id` INT(11) DEFAULT NULL COMMENT '최종 처리한 관리자 ID (hr_employees.id)',
  `rejection_reason` TEXT DEFAULT NULL COMMENT '반려 사유',
  `cancellation_reason` TEXT DEFAULT NULL COMMENT '취소 사유',

  -- 타임스탬프
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '신청일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',

  PRIMARY KEY (`id`),
  KEY `idx_employee_id_status` (`employee_id`, `status`),
  CONSTRAINT `fk_leave_requests_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_requests_approver_id` FOREIGN KEY (`approver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='(신) 직원 휴가 신청 내역';


-- 2. 모든 연차/월차 변동 로그 기록 테이블
CREATE TABLE `hr_leave_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` INT(11) NOT NULL COMMENT '직원 ID (hr_employees.id)',
  `leave_request_id` INT(11) DEFAULT NULL COMMENT '관련된 휴가 신청 ID (hr_leave_requests.id)',

  -- 로그 유형 정보
  `leave_type` ENUM('annual', 'monthly') NOT NULL COMMENT '변동된 휴가 종류 (연차/월차)',
  `transaction_type` ENUM('grant_initial', 'grant_annual', 'grant_service_year', 'grant_monthly', 'use', 'cancel_use', 'adjust_add', 'adjust_subtract', 'expire') NOT NULL COMMENT '트랜잭션 유형',

  -- 수량 및 사유
  `amount` DECIMAL(5, 2) NOT NULL COMMENT '변동 수량 (증가: 양수, 감소: 음수)',
  `reason` VARCHAR(255) DEFAULT NULL COMMENT '변동 사유 (예: 2026년 정기 부여, 우수사원 포상)',

  -- 실행자 정보
  `actor_employee_id` INT(11) DEFAULT NULL COMMENT '해당 로그를 발생시킨 행위자 ID (관리자 조정 등)',

  -- 타임스탬프
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '기록일시',

  PRIMARY KEY (`id`),
  KEY `idx_employee_id_leave_type` (`employee_id`, `leave_type`),
  CONSTRAINT `fk_leave_logs_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_logs_request_id` FOREIGN KEY (`leave_request_id`) REFERENCES `hr_leave_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leave_logs_actor_id` FOREIGN KEY (`actor_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='(신) 연차/월차 변동 전체 로그';

COMMIT;
