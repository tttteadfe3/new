-- MySQL Database Schema Export
-- Generated: 2025-11-07 14:31:47
-- Database: erp

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- 테이블 구조 `hr_department_managers`
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
-- 테이블 구조 `hr_department_view_permissions`
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
-- 테이블 구조 `hr_departments`
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_employee_change_logs`
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보 변경 감사 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_employees`
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
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_holidays`
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
-- 테이블 구조 `hr_leave_policies`
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
-- 테이블 구조 `hr_positions`
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
-- 테이블 구조 `illegal_disposal_cases2`
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
-- 테이블 구조 `sys_activity_logs`
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
) ENGINE=InnoDB AUTO_INCREMENT=8456 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 감사 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_menus`
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
) ENGINE=InnoDB AUTO_INCREMENT=947 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='UI 동적 메뉴 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_permissions`
--

CREATE TABLE `sys_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `key` varchar(100) NOT NULL COMMENT '권한 키 (코드에서 참조, 예: manage_users)',
  `description` varchar(255) DEFAULT NULL COMMENT '권한에 대한 상세 설명',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 권한 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_role_permissions`
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
-- 테이블 구조 `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(100) NOT NULL COMMENT '역할명 (예: admin, user)',
  `description` varchar(255) DEFAULT NULL COMMENT '역할에 대한 상세 설명',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 역할 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_user_roles`
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
-- 테이블 구조 `sys_users`
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
-- 테이블 구조 `waste_collections`
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

COMMIT;
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
