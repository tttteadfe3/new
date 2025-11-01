-- 연차 관리 시스템 개편을 위한 신규 테이블 생성
CREATE TABLE `hr_leave_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `leave_type` ENUM('annual', 'monthly') NOT NULL,
  `request_unit` ENUM('full', 'half_am', 'half_pm') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days_count` DECIMAL(4, 2) NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'cancellation_requested') NOT NULL DEFAULT 'pending',
  `approver_employee_id` INT(11) DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id_status` (`employee_id`, `status`),
  CONSTRAINT `fk_leave_requests_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_requests_approver_id` FOREIGN KEY (`approver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='(신) 직원 휴가 신청 내역';

CREATE TABLE `hr_leave_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `leave_request_id` INT(11) DEFAULT NULL,
  `leave_type` ENUM('annual', 'monthly') NOT NULL,
  `transaction_type` ENUM('grant_initial', 'grant_annual', 'grant_service_year', 'use', 'cancel_use', 'adjust_add', 'adjust_subtract', 'expire') NOT NULL,
  `amount` DECIMAL(5, 2) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `actor_employee_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id_leave_type` (`employee_id`, `leave_type`),
  CONSTRAINT `fk_leave_logs_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_logs_request_id` FOREIGN KEY (`leave_request_id`) REFERENCES `hr_leave_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leave_logs_actor_id` FOREIGN KEY (`actor_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='(신) 연차/월차 변동 전체 로그';
COMMIT;
