-- MySQL Database Schema Export
-- Generated: 2025-11-07 14:31:47
-- Database: erp

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

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
COMMIT;
