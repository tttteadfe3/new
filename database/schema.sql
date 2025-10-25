SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1단계: 부모 테이블 생성 (FK 참조 없음)
-- ========================================

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
  KEY `fk_department_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서 정보';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직급 정보';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 역할 정보';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 권한 정보';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='UI 동적 메뉴 정보';

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

-- ========================================
-- 2단계: hr_employees 생성 (부서, 직급 참조) 및 hr_departments 제약조건 추가
-- ========================================

--
-- 테이블 구조 `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',

  -- 기본 정보
  `name` varchar(255) NOT NULL COMMENT '직원명',
  `employee_number` varchar(50) DEFAULT NULL COMMENT '사번',
  `department_id` int(11) DEFAULT NULL COMMENT '부서 ID',
  `position_id` int(11) DEFAULT NULL COMMENT '직급 ID',
  `hire_date` date DEFAULT NULL COMMENT '입사일',
  `termination_date` date DEFAULT NULL COMMENT '퇴사일 (재직 중일 경우 NULL)',

  -- 개인 추가 정보
  `phone_number` varchar(255) DEFAULT NULL COMMENT '전화번호',
  `address` text DEFAULT NULL COMMENT '주소',
  `clothing_top_size` varchar(50) DEFAULT NULL COMMENT '상의 사이즈',
  `clothing_bottom_size` varchar(50) DEFAULT NULL COMMENT '하의 사이즈',
  `shoe_size` varchar(50) DEFAULT NULL COMMENT '신발 사이즈',
  `emergency_contact_name` varchar(255) DEFAULT NULL COMMENT '비상연락처 이름',
  `emergency_contact_relation` varchar(50) DEFAULT NULL COMMENT '비상연락처 관계',

  -- 프로필 수정 관련
  `profile_update_status` enum('none','pending','rejected') NOT NULL DEFAULT 'none' COMMENT '프로필 수정 요청 상태',
  `pending_profile_data` text DEFAULT NULL COMMENT '수정 요청 중인 데이터 (JSON)',
  `profile_update_rejection_reason` text DEFAULT NULL COMMENT '프로필 수정 요청 반려 사유',

  -- 타임스탬프
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',

  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `idx_name` (`name`),
  KEY `fk_employees_department` (`department_id`),
  KEY `fk_employees_position` (`position_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보';

-- ========================================
-- 3단계: sys_users 생성 (hr_employees 참조)
-- ========================================

--
-- 테이블 구조 `sys_users`
--

CREATE TABLE `sys_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) DEFAULT NULL COMMENT '연결된 직원 ID (hr_employees.id)',

  -- 카카오 인증 정보
  `kakao_id` varchar(255) NOT NULL COMMENT '카카오 고유 ID',
  `email` varchar(255) NOT NULL COMMENT '카카오 계정 이메일',
  `nickname` varchar(255) NOT NULL COMMENT '카카오 닉네임',
  `profile_image_url` varchar(512) DEFAULT NULL COMMENT '카카오 프로필 이미지 URL',

  -- 시스템 상태 및 타임스탬프
  `status` enum('pending','active','inactive','deleted') NOT NULL DEFAULT 'pending' COMMENT '계정 상태',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',

  PRIMARY KEY (`id`),
  UNIQUE KEY `kakao_id` (`kakao_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  CONSTRAINT `users_fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 사용자 계정 정보';

-- ========================================
-- 4단계: sys_user_roles 생성 (sys_users, sys_roles 참조)
-- ========================================

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

-- ========================================
-- 5단계: 활동 로그 테이블 생성 (sys_users 참조)
-- ========================================

--
-- 테이블 구조 `sys_activity_logs`
--

CREATE TABLE `sys_activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `user_id` int(11) DEFAULT NULL COMMENT '행위자 user_id (시스템 로그는 NULL)',
  `user_name` varchar(255) DEFAULT NULL COMMENT '행위자 이름 (비로그인 사용자 등)',
  `action` varchar(255) NOT NULL COMMENT '활동 종류 (예: login, employee_update)',
  `details` text DEFAULT NULL COMMENT '활동 상세 내용 (JSON 형식 권장)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '활동일시',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 감사 로그';

-- ========================================
-- 6단계: 연차 관련 테이블 생성
-- ========================================

--
-- 테이블 구조 `hr_leave_entitlements`
--

CREATE TABLE `hr_leave_entitlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '직원 ID',
  `year` int(4) NOT NULL COMMENT '연차 해당 연도',
  `total_days` decimal(4,1) NOT NULL DEFAULT 0.0 COMMENT '부여된 총 연차',
  `used_days` decimal(4,1) NOT NULL DEFAULT 0.0 COMMENT '사용한 연차',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employee_year` (`employee_id`,`year`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `fk_entitlements_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원별 연차 부여 내역';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_adjustments_log`
--

CREATE TABLE `hr_leave_adjustments_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '대상 직원 ID',
  `year` int(4) NOT NULL COMMENT '조정 대상 연도',
  `adjusted_days` decimal(4,1) NOT NULL COMMENT '조정된 연차 (예: +1.0, -0.5)',
  `reason` varchar(255) NOT NULL COMMENT '조정 사유',
  `admin_id` int(11) NOT NULL COMMENT '처리한 관리자 user_id',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '기록 생성일시',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='(관리자용) 연차 수동 조정 기록';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leaves`
--

CREATE TABLE `hr_leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '신청 직원 ID',

  -- 휴가 정보
  `leave_type` enum('annual','sick','special','other','half_day') NOT NULL DEFAULT 'annual' COMMENT '휴가 종류',
  `start_date` date NOT NULL COMMENT '휴가 시작일',
  `end_date` date NOT NULL COMMENT '휴가 종료일',
  `days_count` decimal(4,1) NOT NULL COMMENT '휴가 일수 (0.5는 반차)',
  `reason` text DEFAULT NULL COMMENT '휴가 신청 사유',

  -- 처리 정보
  `status` enum('pending','approved','rejected','cancelled','cancellation_requested') NOT NULL DEFAULT 'pending' COMMENT '신청 상태',
  `approved_by` int(11) DEFAULT NULL COMMENT '처리한 관리자 user_id',
  `rejection_reason` text DEFAULT NULL COMMENT '반려 사유',
  `cancellation_reason` text DEFAULT NULL COMMENT '취소 사유 (직원이 취소 요청 시)',

  -- 타임스탬프
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '신청일시',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `fk_leaves_approved_by` (`approved_by`),
  CONSTRAINT `fk_leaves_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leaves_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 휴가 신청 내역';

-- ========================================
-- 7단계: 휴일 관련 테이블 생성
-- ========================================

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

-- ========================================
-- 8단계: 직원 변경 이력 테이블 생성
-- ========================================

--
-- 테이블 구조 `hr_employee_change_logs`
--

CREATE TABLE `hr_employee_change_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '변경 대상 직원 ID',
  `changer_id` int(11) DEFAULT NULL COMMENT '변경 수행한 관리자 user_id (시스템에 의한 변경 시 NULL)',
  `field_name` varchar(100) NOT NULL COMMENT '변경된 필드명',
  `old_value` text DEFAULT NULL COMMENT '변경 전 값',
  `new_value` text DEFAULT NULL COMMENT '변경 후 값',
  `changed_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '변경일시',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `fk_log_changer_id` (`changer_id`),
  CONSTRAINT `fk_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_changer_id` FOREIGN KEY (`changer_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보 변경 감사 로그';

-- ========================================
-- 9단계: 폐기물 관련 테이블 생성
-- ========================================

--
-- 테이블 구조 `illegal_disposal_cases2`
--

CREATE TABLE `illegal_disposal_cases2` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',

  -- 위치 정보
  `latitude` decimal(15,10) NOT NULL COMMENT '위도',
  `longitude` decimal(15,10) NOT NULL COMMENT '경도',
  `jibun_address` varchar(255) DEFAULT NULL COMMENT '지번 주소',
  `road_address` varchar(255) DEFAULT NULL COMMENT '도로명 주소',

  -- 폐기물 정보
  `waste_type` varchar(50) NOT NULL COMMENT '폐기물 주성상',
  `waste_type2` varchar(50) DEFAULT NULL COMMENT '폐기물 부성상 (혼합배출 시)',

  -- 사진 정보
  `reg_photo_path` varchar(255) NOT NULL COMMENT '등록 사진 (처리 전)',
  `reg_photo_path2` varchar(255) DEFAULT NULL COMMENT '등록 사진 (처리 후)',
  `proc_photo_path` varchar(255) DEFAULT NULL COMMENT '담당자 처리 사진',

  -- 상태 및 처리 정보
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '처리 상태 (pending, confirmed, completed, rejected, deleted)',
  `corrected` enum('o','x','=') DEFAULT NULL COMMENT '개선 여부 (o: 개선, x: 미개선, =: 사라짐)',
  `note` text DEFAULT NULL COMMENT '관리자 메모',

  -- 담당자 정보
  `created_by` int(11) DEFAULT NULL COMMENT '최초 등록한 직원 ID',
  `confirmed_by` int(11) DEFAULT NULL COMMENT '내용 확인한 직원 ID',
  `processed_by` int(11) DEFAULT NULL COMMENT '개선여부 처리한 직원 ID',
  `completed_by` int(11) DEFAULT NULL COMMENT '완료 처리한 직원 ID',
  `deleted_by` int(11) DEFAULT NULL COMMENT '삭제 처리한 직원 ID',

  -- 타임스탬프
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `confirmed_at` datetime DEFAULT NULL COMMENT '확인일시',
  `processed_at` datetime DEFAULT NULL COMMENT '개선여부 처리일시',
  `completed_at` datetime DEFAULT NULL COMMENT '완료일시',
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제일시',

  PRIMARY KEY (`id`),
  KEY `idx_coords` (`latitude`,`longitude`),
  KEY `idx_waste_type` (`waste_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부적정 배출 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `waste_collections`
--

CREATE TABLE `waste_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `type` enum('field','online') NOT NULL DEFAULT 'field' COMMENT '등록 유형 (field: 현장, online: 인터넷)',

  -- 위치 및 주소
  `latitude` decimal(15,10) NOT NULL COMMENT '위도',
  `longitude` decimal(15,10) NOT NULL COMMENT '경도',
  `address` varchar(255) NOT NULL COMMENT '수거지 주소',
  `geocoding_status` enum('success','failure') NOT NULL DEFAULT 'success' COMMENT '주소-좌표 변환 성공 여부',

  -- 배출 정보
  `issue_date` datetime NOT NULL COMMENT '배출일시',
  `photo_path` varchar(255) DEFAULT NULL COMMENT '배출 사진 경로',
  `discharge_number` varchar(100) DEFAULT NULL COMMENT '배출번호 (온라인 접수 시)',
  `submitter_name` varchar(100) DEFAULT NULL COMMENT '배출자 성명 (온라인 접수 시)',
  `submitter_phone` varchar(100) DEFAULT NULL COMMENT '배출자 연락처 (온라인 접수 시)',

  -- 처리 정보
  `fee` int(11) NOT NULL DEFAULT 0 COMMENT '수수료',
  `status` varchar(20) NOT NULL DEFAULT 'unprocessed' COMMENT '수거 상태 (unprocessed, processed)',
  `admin_memo` text DEFAULT NULL COMMENT '관리자 메모',

  -- 담당자 및 타임스탬프
  `created_by` int(11) DEFAULT NULL COMMENT '등록한 직원 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정한 직원 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '등록일시',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT '수정일시',

  PRIMARY KEY (`id`),
  KEY `idx_coords` (`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 접수 정보';

-- ========================================
-- 10단계: 폐기물 수거 품목 테이블 생성
-- ========================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 품목';

-- ========================================
-- 최종 단계: 제약 조건 추가
-- ========================================

-- `hr_departments` 테이블의 외래 키 제약 조건 추가
-- (참조하는 테이블들이 모두 생성된 후에 추가)
ALTER TABLE `hr_departments`
  ADD CONSTRAINT `fk_department_parent` FOREIGN KEY (`parent_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL;

-- ========================================
-- (신규) 부서장 매핑 테이블 생성
-- (신규) 부서 조회 권한 직원 매핑 테이블
-- ========================================
CREATE TABLE `hr_department_managers` (
  `department_id` int(11) NOT NULL COMMENT '부서 ID',
  `employee_id` int(11) NOT NULL COMMENT '부서장 직원 ID',
  PRIMARY KEY (`department_id`, `employee_id`),
  KEY `fk_manager_employee_id` (`employee_id`),
  CONSTRAINT `fk_manager_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_manager_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서별 부서장 정보';

-- ========================================
-- (신규) 부서 조회 권한 부서 매핑 테이블
-- ========================================
CREATE TABLE `hr_department_view_permissions` (
  `department_id` int(11) NOT NULL COMMENT '정보를 조회 당하는 부서 ID',
  `permitted_department_id` int(11) NOT NULL COMMENT '정보를 조회하는 부서 ID',
  PRIMARY KEY (`department_id`, `permitted_department_id`),
  KEY `fk_view_permission_permitted_department_id` (`permitted_department_id`),
  CONSTRAINT `fk_view_permission_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_view_permission_permitted_department_id` FOREIGN KEY (`permitted_department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='특정 부서가 다른 부서 정보를 조회할 수 있는 권한';

-- ========================================
COMMIT;