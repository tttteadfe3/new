
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
--
-- 테이블 구조 `hr_leave_applications`
--

CREATE TABLE `hr_leave_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '신청 ID',
  `employee_id` int(11) NOT NULL COMMENT '신청자 ID',
  `start_date` date NOT NULL COMMENT '시작일',
  `end_date` date NOT NULL COMMENT '종료일',
  `days` decimal(4,2) NOT NULL COMMENT '신청 일수',
  `leave_type` enum('연차','월차') NOT NULL COMMENT '연차 유형',
  `day_type` enum('전일','반차') DEFAULT '전일' COMMENT '전일/반차',
  `status` enum('대기','승인','반려','취소') DEFAULT '대기' COMMENT '상태',
  `reason` varchar(1000) DEFAULT NULL COMMENT '신청 사유',
  `approver_id` int(11) DEFAULT NULL COMMENT '승인자 ID',
  `approved_at` datetime DEFAULT NULL COMMENT '승인일시',
  `approval_reason` varchar(500) DEFAULT NULL COMMENT '승인/반려 사유',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '신청일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_approver_id` (`approver_id`),
  KEY `idx_leave_apps_employee_dates` (`employee_id`,`start_date`,`end_date`),
  KEY `idx_leave_apps_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_leave_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leave_app_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 신청';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_cancellations`
--

CREATE TABLE `hr_leave_cancellations` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '취소 신청 ID',
  `application_id` int(11) DEFAULT NULL COMMENT '원본 연차 신청 ID',
  `employee_id` int(11) NOT NULL COMMENT '신청자 ID',
  `reason` varchar(1000) NOT NULL COMMENT '취소 사유',
  `status` enum('대기','승인','반려') DEFAULT '대기' COMMENT '상태',
  `approver_id` int(11) DEFAULT NULL COMMENT '승인자 ID',
  `approved_at` datetime DEFAULT NULL COMMENT '승인일시',
  `approval_reason` varchar(500) DEFAULT NULL COMMENT '승인/반려 사유',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '신청일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_approver_id` (`approver_id`),
  CONSTRAINT `fk_leave_cancel_application` FOREIGN KEY (`application_id`) REFERENCES `hr_leave_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_cancel_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leave_cancel_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 취소 신청';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_logs`
--

CREATE TABLE `hr_leave_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '로그 ID',
  `employee_id` int(11) NOT NULL COMMENT '직원 ID',
  `leave_type` varchar(20) DEFAULT '연차' COMMENT '연차 구분 (연차, 월차)',
  `grant_year` int(4) DEFAULT NULL COMMENT '연차 부여연도 (해당 연차가 부여된 연도)',
  `log_type` enum('부여','사용','조정','소멸','취소') NOT NULL COMMENT '로그 유형',
  `transaction_type` varchar(50) DEFAULT NULL COMMENT '상세 거래 유형',
  `amount` decimal(4,2) NOT NULL COMMENT '연차 변동량 (+/-)',
  `balance_after` decimal(4,2) NOT NULL COMMENT '변동 후 잔여량',
  `reason` varchar(500) DEFAULT NULL COMMENT '사유',
  `reference_id` int(11) DEFAULT NULL COMMENT '참조 ID (신청서 등)',
  `created_by` int(11) NOT NULL COMMENT '생성자 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_reference_id` (`reference_id`),
  KEY `fk_leave_log_creator` (`created_by`),
  KEY `idx_leave_logs_employee_created` (`employee_id`,`created_at`),
  KEY `idx_leave_logs_grant_year` (`grant_year`),
  KEY `idx_leave_logs_employee_grant_year` (`employee_id`,`grant_year`),
  CONSTRAINT `fk_leave_log_creator` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`),
  CONSTRAINT `fk_leave_log_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 변동 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `waste_collection_items`
--

CREATE TABLE `waste_collection_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `collection_id` int(11) NOT NULL COMMENT '수거 접수 ID (waste_collections.id)',
  `item_name` varchar(100) NOT NULL COMMENT '품목명',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT '수량',
  PRIMARY KEY (`id`),
  KEY `fk_waste_item_collection_id` (`collection_id`),
  CONSTRAINT `fk_waste_item_collection_id` FOREIGN KEY (`collection_id`) REFERENCES `waste_collections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 품목';

-- --------------------------------------------------------

--
-- 뷰 구조 `v_employee_leave_status`
--
DROP TABLE IF EXISTS `v_employee_leave_status`;

CREATE VIEW `v_employee_leave_status` AS
SELECT
    e.id AS employee_id,
    e.name AS employee_name,
    e.hire_date AS hire_date,
    d.name AS department_name,
    p.name AS position_name,

    -- 현재 잔여량 (로그 기반 계산)
    COALESCE(SUM(
        CASE
            WHEN ll.transaction_type IN ('초기부여', '연차부여', '근속연차부여', '월차부여', '연차추가', '사용취소') THEN ll.amount
            WHEN ll.transaction_type IN ('연차사용', '연차소멸', '연차차감') THEN -ABS(ll.amount)
            WHEN ll.transaction_type = '연차조정' THEN ll.amount
            ELSE 0
        END
    ), 0) AS current_balance,

    -- 부여된 연차 (부여 로그만)
    COALESCE(SUM(
        CASE
            WHEN ll.transaction_type IN ('초기부여', '연차부여', '근속연차부여', '월차부여') THEN ll.amount
            ELSE 0
        END
    ), 0) AS granted_days,

    -- 승인 대기 중인 신청 수
    COUNT(CASE WHEN la.status = '대기' THEN 1 END) AS pending_applications,

    -- 올해 승인된 신청 수
    COUNT(CASE WHEN la.status = '승인' AND YEAR(la.start_date) = YEAR(CURDATE()) THEN 1 END) AS approved_this_year,

    -- 올해 사용한 연차 (로그 기반 - 연차사용 타입만)
    COALESCE(SUM(
        CASE
            WHEN ll.transaction_type = '연차사용'
                AND YEAR(ll.created_at) = YEAR(CURDATE())
            THEN ll.amount
            ELSE 0
        END
    ), 0) AS used_days_this_year,

    -- 잔여 연차 (current_balance와 동일)
    COALESCE(SUM(
        CASE
            WHEN ll.transaction_type IN ('초기부여', '연차부여', '근속연차부여', '월차부여', '연차추가', '사용취소') THEN ll.amount
            WHEN ll.transaction_type IN ('연차사용', '연차소멸', '연차차감') THEN -ABS(ll.amount)
            WHEN ll.transaction_type = '연차조정' THEN ll.amount
            ELSE 0
        END
    ), 0) AS remaining_days

FROM hr_employees e
LEFT JOIN hr_departments d ON e.department_id = d.id
LEFT JOIN hr_positions p ON e.position_id = p.id
LEFT JOIN hr_leave_logs ll ON e.id = ll.employee_id
LEFT JOIN hr_leave_applications la ON e.id = la.employee_id

WHERE e.termination_date IS NULL

GROUP BY
    e.id,
    e.name,
    e.hire_date,
    d.name,
    p.name;

-- --------------------------------------------------------

-- Vehicle Maintenance System Schema
-- All tables are prefixed with 'vm_' to avoid conflicts and group them logically.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `vm_vehicles`
--

CREATE TABLE `vm_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `vehicle_number` varchar(255) NOT NULL COMMENT '차량번호',
  `model` varchar(255) NOT NULL COMMENT '차종/모델',
  `year` year(4) DEFAULT NULL COMMENT '연식',
  `department_id` int(11) DEFAULT NULL COMMENT '배정 부서 ID',
  `status_code` varchar(50) NOT NULL DEFAULT 'NORMAL' COMMENT '차량 상태 코드 (NORMAL, REPAIRING, DISPOSED)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicle_number` (`vehicle_number`),
  KEY `fk_vehicle_department` (`department_id`),
  CONSTRAINT `fk_vehicle_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 기본 정보';

--
-- Table structure for table `vm_vehicle_breakdowns`
--

CREATE TABLE `vm_vehicle_breakdowns` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `driver_employee_id` int(11) NOT NULL COMMENT '신고한 운전자 직원 ID',
  `breakdown_item` varchar(255) NOT NULL COMMENT '고장 항목',
  `description` text COMMENT '고장 상세 내용',
  `mileage` int(11) DEFAULT NULL COMMENT '고장 당시 주행거리',
  `photo_path` varchar(255) DEFAULT NULL COMMENT '고장 사진 파일 경로',
  `status` varchar(50) NOT NULL DEFAULT 'REGISTERED' COMMENT '고장 처리 상태 (REGISTERED, RECEIVED, DECIDED, COMPLETED, APPROVED)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `fk_breakdown_vehicle` (`vehicle_id`),
  KEY `fk_breakdown_driver` (`driver_employee_id`),
  CONSTRAINT `fk_breakdown_driver` FOREIGN KEY (`driver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `fk_breakdown_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 고장 처리 관리';

--
-- Table structure for table `vm_vehicle_maintenances`
--

CREATE TABLE `vm_vehicle_maintenances` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `driver_employee_id` int(11) NOT NULL COMMENT '정비 수행 운전자 직원 ID',
  `maintenance_item` varchar(255) NOT NULL COMMENT '정비 항목',
  `description` text COMMENT '정비 상세 내용',
  `used_parts` text COMMENT '사용 부품',
  `photo_path` varchar(255) DEFAULT NULL COMMENT '정비 사진 파일 경로',
  `status` varchar(50) NOT NULL DEFAULT 'COMPLETED' COMMENT '정비 상태 (COMPLETED, APPROVED)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `fk_maintenance_vehicle` (`vehicle_id`),
  KEY `fk_maintenance_driver` (`driver_employee_id`),
  CONSTRAINT `fk_maintenance_driver` FOREIGN KEY (`driver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `fk_maintenance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='운전자 자체 정비 관리';

--
-- Table structure for table `vm_vehicle_consumables`
--

CREATE TABLE `vm_vehicle_consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '소모품명 (예: 엔진 오일, 타이어)',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '단가',
  `unit` varchar(50) DEFAULT NULL COMMENT '단위 (예: L, 개)',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_consumable_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 정보';

--
-- Table structure for table `vm_vehicle_consumable_logs`
--

CREATE TABLE `vm_vehicle_consumable_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 ID',
  `quantity` decimal(10,2) NOT NULL COMMENT '사용 수량',
  `total_cost` decimal(10,2) NOT NULL COMMENT '총 비용',
  `replaced_by_employee_id` int(11) DEFAULT NULL COMMENT '교체자 직원 ID',
  `replacement_date` date NOT NULL COMMENT '교체일',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_log_vehicle` (`vehicle_id`),
  KEY `fk_log_consumable` (`consumable_id`),
  KEY `fk_log_employee` (`replaced_by_employee_id`),
  CONSTRAINT `fk_log_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vm_vehicle_consumables` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_log_employee` FOREIGN KEY (`replaced_by_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 교체 이력';

--
-- Table structure for table `vm_vehicle_insurance`
--

CREATE TABLE `vm_vehicle_insurance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `insurer_name` varchar(255) NOT NULL COMMENT '보험사명',
  `policy_number` varchar(255) NOT NULL COMMENT '증권번호',
  `start_date` date NOT NULL COMMENT '보험 시작일',
  `end_date` date NOT NULL COMMENT '보험 종료일',
  `premium` decimal(10,2) NOT NULL COMMENT '보험료',
  `document_path` varchar(255) DEFAULT NULL COMMENT '보험증서 파일 경로',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_insurance_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_insurance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 보험 정보';

--
-- Table structure for table `vm_vehicle_inspections`
--

CREATE TABLE `vm_vehicle_inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL COMMENT '검사일',
  `expiry_date` date NOT NULL COMMENT '다음 검사 예정일 (만료일)',
  `inspector_name` varchar(255) DEFAULT NULL COMMENT '검사소/검사자명',
  `result` varchar(50) NOT NULL COMMENT '검사 결과 (예: 합격, 불합격)',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '검사 비용',
  `document_path` varchar(255) DEFAULT NULL COMMENT '검사 결과 파일 경로',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inspection_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_inspection_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 정기검사 관리';

--
-- Table structure for table `vm_vehicle_taxes`
--

CREATE TABLE `vm_vehicle_taxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `payment_date` date NOT NULL COMMENT '납부일',
  `amount` decimal(10,2) NOT NULL COMMENT '납부액',
  `tax_type` varchar(100) DEFAULT NULL COMMENT '세금 종류 (예: 자동차세)',
  `document_path` varchar(255) DEFAULT NULL COMMENT '납부 증빙 파일 경로',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tax_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_tax_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vm_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 세금 납부 내역';
COMMIT;
