
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
COMMIT;
