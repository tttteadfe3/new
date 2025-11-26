-- Database Schema Export
-- Generated: 2025-11-27 06:56:47
-- Database: erp

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_vehicle_consumable_stock_20251125`
--

CREATE TABLE `backup_vehicle_consumable_stock_20251125` (
  `id` int(11) NOT NULL DEFAULT 0 COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `quantity` int(11) NOT NULL COMMENT '입고 수량',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '입고 단가',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `registered_by` int(11) DEFAULT NULL COMMENT '등록자 employee_id',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_vehicle_consumable_usage_20251125`
--

CREATE TABLE `backup_vehicle_consumable_usage_20251125` (
  `id` int(11) NOT NULL DEFAULT 0 COMMENT '고유 ID',
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `maintenance_id` int(11) DEFAULT NULL COMMENT '정비 작업 ID (vehicle_maintenance)',
  `vehicle_id` int(11) DEFAULT NULL COMMENT '차량 ID',
  `quantity` int(11) NOT NULL COMMENT '사용 수량',
  `used_by` int(11) DEFAULT NULL COMMENT '사용자 employee_id',
  `used_at` datetime DEFAULT current_timestamp() COMMENT '사용일시',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_vehicle_consumables_categories_20251125`
--

CREATE TABLE `backup_vehicle_consumables_categories_20251125` (
  `id` int(11) NOT NULL DEFAULT 0 COMMENT '고유 ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '소모품명',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '카테고리 (엔진오일, 타이어, 브레이크, 필터 등)',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '개' COMMENT '단위',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_department_managers`
--

CREATE TABLE `hr_department_managers` (
  `department_id` int(11) NOT NULL COMMENT '부서 ID',
  `employee_id` int(11) NOT NULL COMMENT '부서장 직원 ID',
  PRIMARY KEY (`department_id`,`employee_id`),
  KEY `fk_manager_employee_id` (`employee_id`),
  CONSTRAINT `fk_manager_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_manager_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서별 부서장 정보';

-- --------------------------------------------------------

--
-- Table structure for table `hr_department_view_permissions`
--

CREATE TABLE `hr_department_view_permissions` (
  `department_id` int(11) NOT NULL COMMENT '정보를 조회 당하는 부서 ID',
  `permitted_department_id` int(11) NOT NULL COMMENT '정보를 조회하는 부서 ID',
  PRIMARY KEY (`department_id`,`permitted_department_id`),
  KEY `fk_view_permission_permitted_department_id` (`permitted_department_id`),
  CONSTRAINT `fk_view_permission_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_view_permission_permitted_department_id` FOREIGN KEY (`permitted_department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='특정 부서가 다른 부서 정보를 조회할 수 있는 권한';

-- --------------------------------------------------------

--
-- Table structure for table `hr_departments`
--

CREATE TABLE `hr_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '부서명',
  `parent_id` int(11) DEFAULT NULL COMMENT '상위 부서 ID (최상위 부서는 NULL)',
  `path` varchar(255) DEFAULT NULL COMMENT '계층 구조 경로 (예: /1/3/)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `fk_department_parent` (`parent_id`),
  CONSTRAINT `fk_department_parent` FOREIGN KEY (`parent_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서 정보';

-- --------------------------------------------------------

--
-- Table structure for table `hr_employee_change_logs`
--

CREATE TABLE `hr_employee_change_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '변경 대상 직원 ID',
  `changer_employee_id` int(11) DEFAULT NULL COMMENT '변경 수행한 관리자 employee_id (시스템 변경 시 NULL)',
  `field_name` varchar(100) NOT NULL COMMENT '변경된 필드명',
  `old_value` text DEFAULT NULL COMMENT '변경 전 값',
  `new_value` text DEFAULT NULL COMMENT '변경 후 값',
  `changed_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '변경일시',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `fk_log_changer_employee_id` (`changer_employee_id`),
  CONSTRAINT `fk_log_changer_employee_id` FOREIGN KEY (`changer_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보 변경 감사 로그';

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '직원명',
  `employee_number` varchar(50) DEFAULT NULL COMMENT '사번',
  `department_id` int(11) DEFAULT NULL COMMENT '부서 ID',
  `position_id` int(11) DEFAULT NULL COMMENT '직급 ID',
  `hire_date` date DEFAULT NULL COMMENT '입사일',
  `termination_date` date DEFAULT NULL COMMENT '퇴사일 (재직 중일 경우 NULL)',
  `phone_number` varchar(255) DEFAULT NULL COMMENT '전화번호',
  `address` text DEFAULT NULL COMMENT '주소',
  `clothing_top_size` varchar(50) DEFAULT NULL COMMENT '상의 사이즈',
  `clothing_bottom_size` varchar(50) DEFAULT NULL COMMENT '하의 사이즈',
  `shoe_size` varchar(50) DEFAULT NULL COMMENT '신발 사이즈',
  `emergency_contact_name` varchar(255) DEFAULT NULL COMMENT '비상연락처 이름',
  `emergency_contact_relation` varchar(50) DEFAULT NULL COMMENT '비상연락처 관계',
  `profile_update_status` enum('none','대기','반려') NOT NULL DEFAULT 'none' COMMENT '프로필 수정 요청 상태',
  `pending_profile_data` text DEFAULT NULL COMMENT '수정 요청 중인 데이터 (JSON)',
  `profile_update_rejection_reason` text DEFAULT NULL COMMENT '프로필 수정 요청 반려 사유',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `idx_name` (`name`),
  KEY `fk_employees_department` (`department_id`),
  KEY `fk_employees_position` (`position_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보';

-- --------------------------------------------------------

--
-- Table structure for table `hr_holidays`
--

CREATE TABLE `hr_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '휴일명 또는 이벤트명',
  `date` date NOT NULL COMMENT '날짜',
  `type` enum('holiday','workday') NOT NULL COMMENT '유형 (holiday: 법정/회사지정 휴일, workday: 대체 근무일)',
  `department_id` int(11) DEFAULT NULL COMMENT '특정 부서에만 적용 시 부서 ID (전체 적용 시 NULL)',
  `deduct_leave` tinyint(1) NOT NULL DEFAULT 0 COMMENT '연차 차감 휴일 여부 (1: 차감, 0: 미차감)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `fk_holidays_department` (`department_id`),
  CONSTRAINT `fk_holidays_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회사 지정 휴일 및 대체 근무일';

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_applications`
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 신청';

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_cancellations`
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 취소 신청';

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_logs`
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
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 변동 로그';

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_policies`
--

CREATE TABLE `hr_leave_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_year` year(4) NOT NULL COMMENT '적용 연도',
  `base_annual_days` int(11) DEFAULT 15 COMMENT '기본 연차 일수',
  `max_annual_days` int(11) DEFAULT 25 COMMENT '최대 연차 일수',
  `service_year_increment` int(11) DEFAULT 2 COMMENT '근속 연차 증가 주기(년)',
  `service_year_days` int(11) DEFAULT 1 COMMENT '근속 연차 증가 일수',
  `attendance_rate_threshold` decimal(5,2) DEFAULT 80.00 COMMENT '출근율 기준(%)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성 여부',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_policy_year` (`policy_year`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 정책';

-- --------------------------------------------------------

--
-- Table structure for table `hr_positions`
--

CREATE TABLE `hr_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '직급명',
  `level` int(11) NOT NULL DEFAULT 99 COMMENT '직급 레벨 (숫자가 낮을수록 높은 직급)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직급 정보';

-- --------------------------------------------------------

--
-- Table structure for table `illegal_disposal_cases2`
--

CREATE TABLE `illegal_disposal_cases2` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `latitude` decimal(15,10) NOT NULL COMMENT '위도',
  `longitude` decimal(15,10) NOT NULL COMMENT '경도',
  `jibun_address` varchar(255) DEFAULT NULL COMMENT '지번 주소',
  `road_address` varchar(255) DEFAULT NULL COMMENT '도로명 주소',
  `waste_type` varchar(50) NOT NULL COMMENT '폐기물 주성상',
  `waste_type2` varchar(50) DEFAULT NULL COMMENT '폐기물 부성상 (혼합배출 시)',
  `reg_photo_path` varchar(255) NOT NULL COMMENT '등록 사진 (처리 전)',
  `reg_photo_path2` varchar(255) DEFAULT NULL COMMENT '등록 사진 (처리 후)',
  `proc_photo_path` varchar(255) DEFAULT NULL COMMENT '담당자 처리 사진',
  `status` enum('대기','확인','처리완료','승인완료','대기삭제','처리삭제','삭제') NOT NULL DEFAULT '대기' COMMENT '	처리 상태 (''대기'',''확인'',''처리완료'',''승인완료'',''대기삭제'',''처리삭제'',''삭제'')',
  `corrected` enum('o','x','=') DEFAULT NULL COMMENT '개선 여부 (o: 개선, x: 미개선, =: 사라짐)',
  `note` text DEFAULT NULL COMMENT '관리자 메모',
  `created_by` int(11) DEFAULT NULL COMMENT '최초 등록한 직원 ID',
  `confirmed_by` int(11) DEFAULT NULL COMMENT '내용 확인한 직원 ID',
  `processed_by` int(11) DEFAULT NULL COMMENT '개선여부 처리한 직원 ID',
  `completed_by` int(11) DEFAULT NULL COMMENT '완료 처리한 직원 ID',
  `deleted_by` int(11) DEFAULT NULL COMMENT '삭제 처리한 직원 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `confirmed_at` datetime DEFAULT NULL COMMENT '확인일시',
  `processed_at` datetime DEFAULT NULL COMMENT '개선여부 처리일시',
  `completed_at` datetime DEFAULT NULL COMMENT '완료일시',
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제일시',
  PRIMARY KEY (`id`),
  KEY `idx_coords` (`latitude`,`longitude`),
  KEY `idx_waste_type` (`waste_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1035 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부적정 배출 정보';

-- --------------------------------------------------------

--
-- Table structure for table `supply_categories`
--

CREATE TABLE `supply_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '상위 분류 ID (대분류는 NULL)',
  `category_code` varchar(20) NOT NULL COMMENT '분류 코드',
  `category_name` varchar(100) NOT NULL COMMENT '분류명',
  `level` tinyint(4) NOT NULL DEFAULT 1 COMMENT '분류 레벨 (1: 대분류, 2: 소분류)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '사용 여부',
  `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`),
  KEY `idx_level` (`level`),
  KEY `idx_active` (`is_active`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `supply_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `supply_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 분류';

-- --------------------------------------------------------

--
-- Table structure for table `supply_distribution_document_employees`
--

CREATE TABLE `supply_distribution_document_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `supply_distribution_document_employees_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supply_distribution_document_employees_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_distribution_document_items`
--

CREATE TABLE `supply_distribution_document_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `supply_distribution_document_items_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supply_distribution_document_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_distribution_documents`
--

CREATE TABLE `supply_distribution_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `distribution_date` date NOT NULL DEFAULT curdate(),
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_distribution_date` (`distribution_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `supply_distribution_documents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_distributions`
--

CREATE TABLE `supply_distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `employee_id` int(11) NOT NULL COMMENT '지급 대상 직원 ID',
  `department_id` int(11) NOT NULL COMMENT '지급 대상 부서 ID',
  `distribution_date` date NOT NULL COMMENT '지급일',
  `quantity` int(11) NOT NULL COMMENT '지급 수량',
  `notes` text DEFAULT NULL COMMENT '비고',
  `distributed_by` int(11) NOT NULL COMMENT '지급 처리자 ID',
  `is_cancelled` tinyint(1) DEFAULT 0 COMMENT '취소 여부',
  `cancelled_at` datetime DEFAULT NULL COMMENT '취소일시',
  `cancelled_by` int(11) DEFAULT NULL COMMENT '취소 처리자 ID',
  `cancel_reason` text DEFAULT NULL COMMENT '취소 사유',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `idx_employee_date` (`employee_id`,`distribution_date`),
  KEY `idx_department_date` (`department_id`,`distribution_date`),
  KEY `idx_item_date` (`item_id`,`distribution_date`),
  KEY `idx_distributed_by` (`distributed_by`),
  KEY `idx_cancelled` (`is_cancelled`),
  CONSTRAINT `supply_distributions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  CONSTRAINT `supply_distributions_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`),
  CONSTRAINT `supply_distributions_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  CONSTRAINT `supply_distributions_ibfk_4` FOREIGN KEY (`distributed_by`) REFERENCES `hr_employees` (`id`),
  CONSTRAINT `supply_distributions_ibfk_5` FOREIGN KEY (`cancelled_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 지급 내역';

-- --------------------------------------------------------

--
-- Table structure for table `supply_items`
--

CREATE TABLE `supply_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `item_code` varchar(30) NOT NULL COMMENT '품목 코드',
  `item_name` varchar(200) NOT NULL COMMENT '품목명',
  `category_id` int(11) NOT NULL COMMENT '분류 ID',
  `unit` varchar(20) DEFAULT '개' COMMENT '단위',
  `description` text DEFAULT NULL COMMENT '품목 설명',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '사용 여부',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `idx_category` (`category_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_item_code` (`item_code`),
  CONSTRAINT `supply_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `supply_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 마스터';

-- --------------------------------------------------------

--
-- Table structure for table `supply_plans`
--

CREATE TABLE `supply_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `year` year(4) NOT NULL COMMENT '계획 연도',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `planned_quantity` int(11) NOT NULL COMMENT '계획 수량',
  `unit_price` decimal(10,2) NOT NULL COMMENT '단가',
  `total_budget` decimal(12,2) GENERATED ALWAYS AS (`planned_quantity` * `unit_price`) VIRTUAL COMMENT '총 예산 (자동 계산)',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) NOT NULL COMMENT '생성자 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_item` (`year`,`item_id`),
  KEY `idx_year` (`year`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `supply_plans_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  CONSTRAINT `supply_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연간 지급품 계획';

-- --------------------------------------------------------

--
-- Table structure for table `supply_purchases`
--

CREATE TABLE `supply_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `purchase_date` date NOT NULL COMMENT '구매일',
  `quantity` int(11) NOT NULL COMMENT '구매 수량',
  `unit_price` decimal(10,2) NOT NULL COMMENT '단가',
  `total_amount` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) VIRTUAL COMMENT '총 금액 (자동 계산)',
  `supplier` varchar(200) DEFAULT NULL COMMENT '공급업체',
  `is_received` tinyint(1) DEFAULT 0 COMMENT '입고 여부',
  `received_date` date DEFAULT NULL COMMENT '입고일',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) NOT NULL COMMENT '생성자 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_item_date` (`item_id`,`purchase_date`),
  KEY `idx_received` (`is_received`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `supply_purchases_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  CONSTRAINT `supply_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 구매';

-- --------------------------------------------------------

--
-- Table structure for table `supply_stocks`
--

CREATE TABLE `supply_stocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `total_purchased` int(11) DEFAULT 0 COMMENT '총 구매량',
  `total_distributed` int(11) DEFAULT 0 COMMENT '총 지급량',
  `current_stock` int(11) GENERATED ALWAYS AS (`total_purchased` - `total_distributed`) VIRTUAL COMMENT '현재 재고 (자동 계산)',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '최종 업데이트 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_id` (`item_id`),
  KEY `idx_current_stock` (`current_stock`),
  KEY `idx_item_id` (`item_id`),
  CONSTRAINT `supply_stocks_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 재고 관리';

-- --------------------------------------------------------

--
-- Table structure for table `sys_activity_logs`
--

CREATE TABLE `sys_activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `user_id` int(11) DEFAULT NULL COMMENT '행위자 user_id (시스템 로그는 NULL)',
  `employee_id` int(11) DEFAULT NULL COMMENT '관련 직원 ID (직원 관련 활동 시)',
  `user_name` varchar(255) DEFAULT NULL COMMENT '행위자 이름 (비로그인 사용자 등)',
  `action` varchar(255) NOT NULL COMMENT '활동 종류 (예: login, employee_update)',
  `details` text DEFAULT NULL COMMENT '활동 상세 내용 (JSON 형식 권장)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '활동일시',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  KEY `fk_activity_log_employee_id` (`employee_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14703 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 감사 로그';

-- --------------------------------------------------------

--
-- Table structure for table `sys_menus`
--

CREATE TABLE `sys_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '부모 메뉴 ID (최상위 메뉴는 NULL)',
  `name` varchar(100) NOT NULL COMMENT '메뉴명',
  `url` varchar(255) DEFAULT NULL COMMENT '메뉴 URL',
  `icon` varchar(100) DEFAULT NULL COMMENT '아이콘 클래스명',
  `permission_key` varchar(100) DEFAULT NULL COMMENT '메뉴 접근에 필요한 권한 키',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT '표시 순서 (낮을수록 먼저 표시)',
  PRIMARY KEY (`id`),
  KEY `fk_menu_parent_id` (`parent_id`),
  CONSTRAINT `fk_menu_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `sys_menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=959 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='UI 동적 메뉴 정보';

-- --------------------------------------------------------

--
-- Table structure for table `sys_permissions`
--

CREATE TABLE `sys_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `key` varchar(100) NOT NULL COMMENT '권한 키 (코드에서 참조, 예: manage_users)',
  `description` varchar(255) DEFAULT NULL COMMENT '권한에 대한 상세 설명',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 권한 정보';

-- --------------------------------------------------------

--
-- Table structure for table `sys_role_permissions`
--

CREATE TABLE `sys_role_permissions` (
  `role_id` int(11) NOT NULL COMMENT '역할 ID',
  `permission_id` int(11) NOT NULL COMMENT '권한 ID',
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='역할과 권한 매핑';

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(100) NOT NULL COMMENT '역할명 (예: admin, user)',
  `description` varchar(255) DEFAULT NULL COMMENT '역할에 대한 상세 설명',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 역할 정보';

-- --------------------------------------------------------

--
-- Table structure for table `sys_user_roles`
--

CREATE TABLE `sys_user_roles` (
  `user_id` int(11) NOT NULL COMMENT '사용자 ID',
  `role_id` int(11) NOT NULL COMMENT '역할 ID',
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자와 역할 매핑';

-- --------------------------------------------------------

--
-- Table structure for table `sys_users`
--

CREATE TABLE `sys_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) DEFAULT NULL COMMENT '연결된 직원 ID (hr_employees.id)',
  `kakao_id` varchar(255) NOT NULL COMMENT '카카오 고유 ID',
  `email` varchar(255) NOT NULL COMMENT '카카오 계정 이메일',
  `nickname` varchar(255) NOT NULL COMMENT '카카오 닉네임',
  `profile_image_url` varchar(512) DEFAULT NULL COMMENT '카카오 프로필 이미지 URL',
  `status` enum('대기','활성','비활성','삭제','차단') NOT NULL DEFAULT '대기' COMMENT '시스템 사용자 계정 상태',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `kakao_id` (`kakao_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  CONSTRAINT `users_fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 사용자 계정 정보';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_consumable_stock`
--

CREATE TABLE `vehicle_consumable_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `category_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL COMMENT '입고 수량',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '입고 단가',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `registered_by` int(11) DEFAULT NULL COMMENT '등록자 employee_id',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_stock_category` (`category_id`),
  KEY `idx_stock_item_name` (`item_name`),
  CONSTRAINT `fk_stock_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_consumables_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 재고 입고';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_consumable_usage`
--

CREATE TABLE `vehicle_consumable_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `category_id` int(11) NOT NULL,
  `item_name` varchar(200) DEFAULT NULL,
  `consumable_id` int(11) NOT NULL COMMENT '소모품 분류 ID',
  `maintenance_id` int(11) DEFAULT NULL COMMENT '정비 작업 ID (vehicle_maintenance)',
  `vehicle_id` int(11) DEFAULT NULL COMMENT '차량 ID',
  `quantity` int(11) NOT NULL COMMENT '사용 수량',
  `used_by` int(11) DEFAULT NULL COMMENT '사용자 employee_id',
  `used_at` datetime DEFAULT current_timestamp() COMMENT '사용일시',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_maintenance_id` (`maintenance_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_used_at` (`used_at`),
  KEY `idx_usage_category` (`category_id`),
  KEY `idx_usage_item_name` (`item_name`),
  CONSTRAINT `fk_consumable_usage_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables_categories` (`id`),
  CONSTRAINT `fk_consumable_usage_maintenance` FOREIGN KEY (`maintenance_id`) REFERENCES `vehicle_maintenance` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usage_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_consumables_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='소모품 사용 이력';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_consumables_categories`
--

CREATE TABLE `vehicle_consumables_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL COMMENT '카테고리 (엔진오일, 타이어, 브레이크, 필터 등)	',
  `level` int(11) DEFAULT 1,
  `path` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT '개' COMMENT '단위',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_level` (`level`),
  KEY `idx_path` (`path`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `vehicle_consumables_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 소모품 분류';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `document_type` varchar(50) NOT NULL COMMENT '문서 유형',
  `file_path` varchar(255) NOT NULL COMMENT '파일 경로',
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_document_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_document_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 관련 문서';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_inspections`
--

CREATE TABLE `vehicle_inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `inspection_date` date NOT NULL COMMENT '검사일',
  `expiry_date` date NOT NULL COMMENT '만료일',
  `inspector_name` varchar(100) DEFAULT NULL COMMENT '검사자',
  `result` varchar(50) NOT NULL COMMENT '검사 결과',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '검사 비용',
  `document_path` varchar(255) DEFAULT NULL COMMENT '검사 증명서 파일 경로',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inspection_vehicle` (`vehicle_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  CONSTRAINT `fk_inspection_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 정기검사';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_maintenance`
--

CREATE TABLE `vehicle_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL COMMENT '차량 ID',
  `type` varchar(20) NOT NULL COMMENT '작업 유형 (고장, 정비)',
  `status` varchar(20) NOT NULL DEFAULT '신고' COMMENT '상태 (신고, 처리결정, 작업중, 완료)',
  `reporter_id` int(11) NOT NULL COMMENT '신고자 (운전원) ID',
  `work_item` varchar(100) NOT NULL COMMENT '작업 항목',
  `description` text DEFAULT NULL COMMENT '상세 내용',
  `mileage` int(11) DEFAULT NULL COMMENT '주행거리',
  `photo_path` varchar(255) DEFAULT NULL COMMENT '사진 경로',
  `photo2_path` varchar(255) DEFAULT NULL COMMENT '추가 사진 2',
  `photo3_path` varchar(255) DEFAULT NULL COMMENT '추가 사진 3',
  `repair_type` varchar(20) DEFAULT NULL COMMENT '수리 유형 (자체수리, 외부수리)',
  `decided_at` datetime DEFAULT NULL COMMENT '처리결정 일시',
  `decided_by` int(11) DEFAULT NULL COMMENT '결정자 ID',
  `parts_used` text DEFAULT NULL COMMENT '사용 부품',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '비용',
  `worker_id` int(11) DEFAULT NULL COMMENT '작업자 ID',
  `repair_shop` varchar(255) DEFAULT NULL COMMENT '외부 수리업체',
  `completed_at` datetime DEFAULT NULL COMMENT '작업 완료 일시',
  `confirmed_at` datetime DEFAULT NULL COMMENT '확인 일시',
  `confirmed_by` int(11) DEFAULT NULL COMMENT '확인자 ID',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`,`status`),
  KEY `fk_work_vehicle` (`vehicle_id`),
  KEY `fk_work_reporter` (`reporter_id`),
  CONSTRAINT `fk_work_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `hr_employees` (`id`),
  CONSTRAINT `fk_work_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 작업 (고장+정비 통합)';

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(20) NOT NULL COMMENT '차량번호',
  `model` varchar(255) NOT NULL COMMENT '차종/모델',
  `payload_capacity` varchar(50) DEFAULT NULL COMMENT '적재량',
  `year` year(4) DEFAULT NULL COMMENT '연식',
  `release_date` date DEFAULT NULL COMMENT '출고일자',
  `vehicle_type` varchar(50) DEFAULT NULL COMMENT '차종',
  `department_id` int(11) DEFAULT NULL COMMENT '배정 부서 ID',
  `driver_employee_id` int(11) DEFAULT NULL COMMENT '담당 운전원 ID',
  `status_code` varchar(50) NOT NULL DEFAULT '정상' COMMENT '차량 상태 (정상, 수리중, 폐차)',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicle_number` (`vehicle_number`),
  KEY `fk_vehicle_department` (`department_id`),
  KEY `fk_vehicle_driver` (`driver_employee_id`),
  KEY `idx_vehicle_type` (`vehicle_type`),
  CONSTRAINT `fk_vehicle_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 기본 정보';

-- --------------------------------------------------------

--
-- Table structure for table `waste_collection_items`
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
-- Table structure for table `waste_collections`
--

CREATE TABLE `waste_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `type` enum('field','online') NOT NULL DEFAULT 'field' COMMENT '등록 유형 (field: 현장, online: 인터넷)',
  `latitude` decimal(15,10) NOT NULL COMMENT '위도',
  `longitude` decimal(15,10) NOT NULL COMMENT '경도',
  `address` varchar(255) NOT NULL COMMENT '수거지 주소',
  `geocoding_status` enum('성공','실패') NOT NULL DEFAULT '실패' COMMENT '지오코딩 성공/실패 상태',
  `issue_date` datetime NOT NULL COMMENT '배출일시',
  `photo_path` varchar(255) DEFAULT NULL COMMENT '배출 사진 경로',
  `discharge_number` varchar(100) DEFAULT NULL COMMENT '배출번호 (온라인 접수 시)',
  `submitter_name` varchar(100) DEFAULT NULL COMMENT '배출자 성명 (온라인 접수 시)',
  `submitter_phone` varchar(100) DEFAULT NULL COMMENT '배출자 연락처 (온라인 접수 시)',
  `fee` int(11) NOT NULL DEFAULT 0 COMMENT '수수료',
  `status` enum('미처리','처리완료') NOT NULL DEFAULT '미처리' COMMENT '폐기물 수거 상태',
  `admin_memo` text DEFAULT NULL COMMENT '관리자 메모',
  `created_by` int(11) DEFAULT NULL COMMENT '등록한 직원 ID',
  `completed_by` int(11) DEFAULT NULL COMMENT '완료 처리한 직원 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `completed_at` datetime DEFAULT NULL COMMENT '완료일시',
  PRIMARY KEY (`id`),
  KEY `idx_coords` (`latitude`,`longitude`),
  KEY `fk_waste_collection_created_by` (`created_by`),
  KEY `fk_waste_collection_completed_by` (`completed_by`),
  CONSTRAINT `fk_waste_collection_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_waste_collection_created_by` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 접수 정보';

-- --------------------------------------------------------

--
-- View structure for view `v_employee_leave_status`
--

DROP TABLE IF EXISTS `v_employee_leave_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`erp`@`localhost` SQL SECURITY DEFINER VIEW `v_employee_leave_status` AS select `e`.`id` AS `employee_id`,`e`.`name` AS `employee_name`,`e`.`hire_date` AS `hire_date`,`d`.`name` AS `department_name`,`p`.`name` AS `position_name`,coalesce(`leave_balance`.`current_balance`,0) AS `current_balance`,coalesce(`leave_balance`.`granted_days`,0) AS `granted_days`,coalesce(`app_stats`.`pending_applications`,0) AS `pending_applications`,coalesce(`app_stats`.`approved_this_year`,0) AS `approved_this_year`,coalesce(`leave_balance`.`used_days_this_year`,0) AS `used_days_this_year`,coalesce(`leave_balance`.`current_balance`,0) AS `remaining_days` from ((((`hr_employees` `e` left join `hr_departments` `d` on(`e`.`department_id` = `d`.`id`)) left join `hr_positions` `p` on(`e`.`position_id` = `p`.`id`)) left join (select `hr_leave_logs`.`employee_id` AS `employee_id`,sum(case when `hr_leave_logs`.`transaction_type` in ('초기부여','연차부여','근속연차부여','월차부여','연차추가','사용취소') then `hr_leave_logs`.`amount` when `hr_leave_logs`.`transaction_type` in ('연차사용','연차소멸','연차차감') then -abs(`hr_leave_logs`.`amount`) when `hr_leave_logs`.`transaction_type` = '연차조정' then `hr_leave_logs`.`amount` else 0 end) AS `current_balance`,sum(case when `hr_leave_logs`.`transaction_type` in ('초기부여','연차부여','근속연차부여','월차부여') then `hr_leave_logs`.`amount` else 0 end) AS `granted_days`,sum(case when `hr_leave_logs`.`transaction_type` = '연차사용' and year(`hr_leave_logs`.`created_at`) = year(curdate()) then abs(`hr_leave_logs`.`amount`) when `hr_leave_logs`.`transaction_type` = '사용취소' and year(`hr_leave_logs`.`created_at`) = year(curdate()) then -abs(`hr_leave_logs`.`amount`) else 0 end) AS `used_days_this_year` from `hr_leave_logs` group by `hr_leave_logs`.`employee_id`) `leave_balance` on(`e`.`id` = `leave_balance`.`employee_id`)) left join (select `hr_leave_applications`.`employee_id` AS `employee_id`,count(case when `hr_leave_applications`.`status` = '대기' then 1 end) AS `pending_applications`,count(case when `hr_leave_applications`.`status` = '승인' and year(`hr_leave_applications`.`start_date`) = year(curdate()) then 1 end) AS `approved_this_year` from `hr_leave_applications` group by `hr_leave_applications`.`employee_id`) `app_stats` on(`e`.`id` = `app_stats`.`employee_id`)) where `e`.`termination_date` is null order by `e`.`id`;

-- --------------------------------------------------------

--
-- View structure for view `v_vehicle_consumable_inventory`
--

DROP TABLE IF EXISTS `v_vehicle_consumable_inventory`;

CREATE ALGORITHM=UNDEFINED DEFINER=`erp`@`localhost` SQL SECURITY DEFINER VIEW `v_vehicle_consumable_inventory` AS select `c`.`id` AS `consumable_id`,`c`.`name` AS `consumable_name`,`c`.`category` AS `category`,`c`.`unit` AS `unit`,coalesce(sum(`s`.`quantity`),0) AS `total_stock_in`,coalesce(sum(`u`.`quantity`),0) AS `total_used`,coalesce(sum(`s`.`quantity`),0) - coalesce(sum(`u`.`quantity`),0) AS `current_stock` from ((`vehicle_consumables_categories` `c` left join `vehicle_consumable_stock` `s` on(`c`.`id` = `s`.`consumable_id`)) left join `vehicle_consumable_usage` `u` on(`c`.`id` = `u`.`consumable_id`)) group by `c`.`id`,`c`.`name`,`c`.`category`,`c`.`unit`;

--
-- Indexes for dumped tables
--

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hr_departments`
--
ALTER TABLE `hr_departments`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hr_employee_change_logs`
--
ALTER TABLE `hr_employee_change_logs`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `hr_employees`
--
ALTER TABLE `hr_employees`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `hr_holidays`
--
ALTER TABLE `hr_holidays`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `hr_leave_applications`
--
ALTER TABLE `hr_leave_applications`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hr_leave_cancellations`
--
ALTER TABLE `hr_leave_cancellations`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hr_leave_logs`
--
ALTER TABLE `hr_leave_logs`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `hr_leave_policies`
--
ALTER TABLE `hr_leave_policies`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_positions`
--
ALTER TABLE `hr_positions`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `illegal_disposal_cases2`
--
ALTER TABLE `illegal_disposal_cases2`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1035;

--
-- AUTO_INCREMENT for table `supply_categories`
--
ALTER TABLE `supply_categories`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `supply_distribution_document_employees`
--
ALTER TABLE `supply_distribution_document_employees`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `supply_distribution_document_items`
--
ALTER TABLE `supply_distribution_document_items`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `supply_distribution_documents`
--
ALTER TABLE `supply_distribution_documents`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `supply_distributions`
--
ALTER TABLE `supply_distributions`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `supply_items`
--
ALTER TABLE `supply_items`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `supply_plans`
--
ALTER TABLE `supply_plans`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `supply_purchases`
--
ALTER TABLE `supply_purchases`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `supply_stocks`
--
ALTER TABLE `supply_stocks`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  MODIFY `id` BIGINT(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14703;

--
-- AUTO_INCREMENT for table `sys_menus`
--
ALTER TABLE `sys_menus`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=959;

--
-- AUTO_INCREMENT for table `sys_permissions`
--
ALTER TABLE `sys_permissions`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sys_users`
--
ALTER TABLE `sys_users`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `vehicle_consumable_stock`
--
ALTER TABLE `vehicle_consumable_stock`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `vehicle_consumable_usage`
--
ALTER TABLE `vehicle_consumable_usage`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `vehicle_consumables_categories`
--
ALTER TABLE `vehicle_consumables_categories`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_maintenance`
--
ALTER TABLE `vehicle_maintenance`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `waste_collection_items`
--
ALTER TABLE `waste_collection_items`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `waste_collections`
--
ALTER TABLE `waste_collections`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hr_department_managers`
--
ALTER TABLE `hr_department_managers`
  ADD CONSTRAINT `fk_manager_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `fk_manager_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `hr_department_view_permissions`
--
ALTER TABLE `hr_department_view_permissions`
  ADD CONSTRAINT `fk_view_permission_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `fk_view_permission_permitted_department_id` FOREIGN KEY (`permitted_department_id`) REFERENCES `hr_departments` (`id`);

--
-- Constraints for table `hr_departments`
--
ALTER TABLE `hr_departments`
  ADD CONSTRAINT `fk_department_parent` FOREIGN KEY (`parent_id`) REFERENCES `hr_departments` (`id`);

--
-- Constraints for table `hr_employee_change_logs`
--
ALTER TABLE `hr_employee_change_logs`
  ADD CONSTRAINT `fk_log_changer_employee_id` FOREIGN KEY (`changer_employee_id`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `fk_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`id`);

--
-- Constraints for table `hr_holidays`
--
ALTER TABLE `hr_holidays`
  ADD CONSTRAINT `fk_holidays_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`);

--
-- Constraints for table `hr_leave_applications`
--
ALTER TABLE `hr_leave_applications`
  ADD CONSTRAINT `fk_leave_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`),
  ADD CONSTRAINT `fk_leave_app_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `hr_leave_cancellations`
--
ALTER TABLE `hr_leave_cancellations`
  ADD CONSTRAINT `fk_leave_cancel_application` FOREIGN KEY (`application_id`) REFERENCES `hr_leave_applications` (`id`),
  ADD CONSTRAINT `fk_leave_cancel_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`),
  ADD CONSTRAINT `fk_leave_cancel_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `hr_leave_logs`
--
ALTER TABLE `hr_leave_logs`
  ADD CONSTRAINT `fk_leave_log_creator` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `fk_leave_log_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_categories`
--
ALTER TABLE `supply_categories`
  ADD CONSTRAINT `supply_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `supply_categories` (`id`);

--
-- Constraints for table `supply_distribution_document_employees`
--
ALTER TABLE `supply_distribution_document_employees`
  ADD CONSTRAINT `supply_distribution_document_employees_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`),
  ADD CONSTRAINT `supply_distribution_document_employees_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_distribution_document_items`
--
ALTER TABLE `supply_distribution_document_items`
  ADD CONSTRAINT `supply_distribution_document_items_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`),
  ADD CONSTRAINT `supply_distribution_document_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`);

--
-- Constraints for table `supply_distribution_documents`
--
ALTER TABLE `supply_distribution_documents`
  ADD CONSTRAINT `supply_distribution_documents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_distributions`
--
ALTER TABLE `supply_distributions`
  ADD CONSTRAINT `supply_distributions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_4` FOREIGN KEY (`distributed_by`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_5` FOREIGN KEY (`cancelled_by`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_items`
--
ALTER TABLE `supply_items`
  ADD CONSTRAINT `supply_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `supply_categories` (`id`);

--
-- Constraints for table `supply_plans`
--
ALTER TABLE `supply_plans`
  ADD CONSTRAINT `supply_plans_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_purchases`
--
ALTER TABLE `supply_purchases`
  ADD CONSTRAINT `supply_purchases_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `supply_stocks`
--
ALTER TABLE `supply_stocks`
  ADD CONSTRAINT `supply_stocks_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`);

--
-- Constraints for table `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`),
  ADD CONSTRAINT `fk_activity_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `sys_menus`
--
ALTER TABLE `sys_menus`
  ADD CONSTRAINT `fk_menu_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `sys_menus` (`id`);

--
-- Constraints for table `sys_role_permissions`
--
ALTER TABLE `sys_role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`),
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`);

--
-- Constraints for table `sys_user_roles`
--
ALTER TABLE `sys_user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`),
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`);

--
-- Constraints for table `sys_users`
--
ALTER TABLE `sys_users`
  ADD CONSTRAINT `users_fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `vehicle_consumable_stock`
--
ALTER TABLE `vehicle_consumable_stock`
  ADD CONSTRAINT `fk_stock_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_consumables_categories` (`id`);

--
-- Constraints for table `vehicle_consumable_usage`
--
ALTER TABLE `vehicle_consumable_usage`
  ADD CONSTRAINT `fk_consumable_usage_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables_categories` (`id`),
  ADD CONSTRAINT `fk_consumable_usage_maintenance` FOREIGN KEY (`maintenance_id`) REFERENCES `vehicle_maintenance` (`id`),
  ADD CONSTRAINT `fk_usage_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_consumables_categories` (`id`);

--
-- Constraints for table `vehicle_consumables_categories`
--
ALTER TABLE `vehicle_consumables_categories`
  ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `vehicle_consumables_categories` (`id`);

--
-- Constraints for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `fk_document_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  ADD CONSTRAINT `fk_inspection_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `vehicle_maintenance`
--
ALTER TABLE `vehicle_maintenance`
  ADD CONSTRAINT `fk_work_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `fk_work_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_employee_id`) REFERENCES `hr_employees` (`id`);

--
-- Constraints for table `waste_collection_items`
--
ALTER TABLE `waste_collection_items`
  ADD CONSTRAINT `fk_waste_item_collection_id` FOREIGN KEY (`collection_id`) REFERENCES `waste_collections` (`id`);

--
-- Constraints for table `waste_collections`
--
ALTER TABLE `waste_collections`
  ADD CONSTRAINT `fk_waste_collection_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `fk_waste_collection_created_by` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
