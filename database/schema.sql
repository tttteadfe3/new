-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- 생성 시간: 25-11-22 00:50
-- 서버 버전: 10.11.2-MariaDB
-- PHP 버전: 8.2.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `erp`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_departments`
--

CREATE TABLE `hr_departments` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '부서명',
  `parent_id` int(11) DEFAULT NULL COMMENT '상위 부서 ID (최상위 부서는 NULL)',
  `path` varchar(255) DEFAULT NULL COMMENT '계층 구조 경로 (예: /1/3/)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_department_managers`
--

CREATE TABLE `hr_department_managers` (
  `department_id` int(11) NOT NULL COMMENT '부서 ID',
  `employee_id` int(11) NOT NULL COMMENT '부서장 직원 ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서별 부서장 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_department_view_permissions`
--

CREATE TABLE `hr_department_view_permissions` (
  `department_id` int(11) NOT NULL COMMENT '정보를 조회 당하는 부서 ID',
  `permitted_department_id` int(11) NOT NULL COMMENT '정보를 조회하는 부서 ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='특정 부서가 다른 부서 정보를 조회할 수 있는 권한';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_employee_change_logs`
--

CREATE TABLE `hr_employee_change_logs` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `employee_id` int(11) NOT NULL COMMENT '변경 대상 직원 ID',
  `changer_employee_id` int(11) DEFAULT NULL COMMENT '변경 수행한 관리자 employee_id (시스템 변경 시 NULL)',
  `field_name` varchar(100) NOT NULL COMMENT '변경된 필드명',
  `old_value` text DEFAULT NULL COMMENT '변경 전 값',
  `new_value` text DEFAULT NULL COMMENT '변경 후 값',
  `changed_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '변경일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보 변경 감사 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_holidays`
--

CREATE TABLE `hr_holidays` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '휴일명 또는 이벤트명',
  `date` date NOT NULL COMMENT '날짜',
  `type` enum('holiday','workday') NOT NULL COMMENT '유형 (holiday: 법정/회사지정 휴일, workday: 대체 근무일)',
  `department_id` int(11) DEFAULT NULL COMMENT '특정 부서에만 적용 시 부서 ID (전체 적용 시 NULL)',
  `deduct_leave` tinyint(1) NOT NULL DEFAULT 0 COMMENT '연차 차감 휴일 여부 (1: 차감, 0: 미차감)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회사 지정 휴일 및 대체 근무일';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_applications`
--

CREATE TABLE `hr_leave_applications` (
  `id` int(11) NOT NULL COMMENT '신청 ID',
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 신청';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_cancellations`
--

CREATE TABLE `hr_leave_cancellations` (
  `id` int(11) NOT NULL COMMENT '취소 신청 ID',
  `application_id` int(11) DEFAULT NULL COMMENT '원본 연차 신청 ID',
  `employee_id` int(11) NOT NULL COMMENT '신청자 ID',
  `reason` varchar(1000) NOT NULL COMMENT '취소 사유',
  `status` enum('대기','승인','반려') DEFAULT '대기' COMMENT '상태',
  `approver_id` int(11) DEFAULT NULL COMMENT '승인자 ID',
  `approved_at` datetime DEFAULT NULL COMMENT '승인일시',
  `approval_reason` varchar(500) DEFAULT NULL COMMENT '승인/반려 사유',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '신청일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 취소 신청';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_logs`
--

CREATE TABLE `hr_leave_logs` (
  `id` int(11) NOT NULL COMMENT '로그 ID',
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
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 변동 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_leave_policies`
--

CREATE TABLE `hr_leave_policies` (
  `id` int(11) NOT NULL,
  `policy_year` year(4) NOT NULL COMMENT '적용 연도',
  `base_annual_days` int(11) DEFAULT 15 COMMENT '기본 연차 일수',
  `max_annual_days` int(11) DEFAULT 25 COMMENT '최대 연차 일수',
  `service_year_increment` int(11) DEFAULT 2 COMMENT '근속 연차 증가 주기(년)',
  `service_year_days` int(11) DEFAULT 1 COMMENT '근속 연차 증가 일수',
  `attendance_rate_threshold` decimal(5,2) DEFAULT 80.00 COMMENT '출근율 기준(%)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성 여부',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연차 정책';

-- --------------------------------------------------------

--
-- 테이블 구조 `hr_positions`
--

CREATE TABLE `hr_positions` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `name` varchar(255) NOT NULL COMMENT '직급명',
  `level` int(11) NOT NULL DEFAULT 99 COMMENT '직급 레벨 (숫자가 낮을수록 높은 직급)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직급 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `illegal_disposal_cases2`
--

CREATE TABLE `illegal_disposal_cases2` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
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
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부적정 배출 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_categories`
--

CREATE TABLE `supply_categories` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '상위 분류 ID (대분류는 NULL)',
  `category_code` varchar(20) NOT NULL COMMENT '분류 코드',
  `category_name` varchar(100) NOT NULL COMMENT '분류명',
  `level` tinyint(4) NOT NULL DEFAULT 1 COMMENT '분류 레벨 (1: 대분류, 2: 소분류)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '사용 여부',
  `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 분류';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_distributions`
--

CREATE TABLE `supply_distributions` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 지급 내역';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_distribution_documents`
--

CREATE TABLE `supply_distribution_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `distribution_date` date NOT NULL DEFAULT curdate(),
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_distribution_document_employees`
--

CREATE TABLE `supply_distribution_document_employees` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_distribution_document_items`
--

CREATE TABLE `supply_distribution_document_items` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_items`
--

CREATE TABLE `supply_items` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `item_code` varchar(30) NOT NULL COMMENT '품목 코드',
  `item_name` varchar(200) NOT NULL COMMENT '품목명',
  `category_id` int(11) NOT NULL COMMENT '분류 ID',
  `unit` varchar(20) DEFAULT '개' COMMENT '단위',
  `description` text DEFAULT NULL COMMENT '품목 설명',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '사용 여부',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 마스터';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_plans`
--

CREATE TABLE `supply_plans` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `year` year(4) NOT NULL COMMENT '계획 연도',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `planned_quantity` int(11) NOT NULL COMMENT '계획 수량',
  `unit_price` decimal(10,2) NOT NULL COMMENT '단가',
  `total_budget` decimal(12,2) GENERATED ALWAYS AS (`planned_quantity` * `unit_price`) VIRTUAL COMMENT '총 예산 (자동 계산)',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) NOT NULL COMMENT '생성자 ID',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연간 지급품 계획';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_purchases`
--

CREATE TABLE `supply_purchases` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 구매';

-- --------------------------------------------------------

--
-- 테이블 구조 `supply_stocks`
--

CREATE TABLE `supply_stocks` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `item_id` int(11) NOT NULL COMMENT '품목 ID',
  `total_purchased` int(11) DEFAULT 0 COMMENT '총 구매량',
  `total_distributed` int(11) DEFAULT 0 COMMENT '총 지급량',
  `current_stock` int(11) GENERATED ALWAYS AS (`total_purchased` - `total_distributed`) VIRTUAL COMMENT '현재 재고 (자동 계산)',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '최종 업데이트 일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지급품 재고 관리';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_activity_logs`
--

CREATE TABLE `sys_activity_logs` (
  `id` bigint(20) NOT NULL COMMENT '고유 ID',
  `user_id` int(11) DEFAULT NULL COMMENT '행위자 user_id (시스템 로그는 NULL)',
  `employee_id` int(11) DEFAULT NULL COMMENT '관련 직원 ID (직원 관련 활동 시)',
  `user_name` varchar(255) DEFAULT NULL COMMENT '행위자 이름 (비로그인 사용자 등)',
  `action` varchar(255) NOT NULL COMMENT '활동 종류 (예: login, employee_update)',
  `details` text DEFAULT NULL COMMENT '활동 상세 내용 (JSON 형식 권장)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '활동일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 감사 로그';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_menus`
--

CREATE TABLE `sys_menus` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '부모 메뉴 ID (최상위 메뉴는 NULL)',
  `name` varchar(100) NOT NULL COMMENT '메뉴명',
  `url` varchar(255) DEFAULT NULL COMMENT '메뉴 URL',
  `icon` varchar(100) DEFAULT NULL COMMENT '아이콘 클래스명',
  `permission_key` varchar(100) DEFAULT NULL COMMENT '메뉴 접근에 필요한 권한 키',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT '표시 순서 (낮을수록 먼저 표시)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='UI 동적 메뉴 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_permissions`
--

CREATE TABLE `sys_permissions` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `key` varchar(100) NOT NULL COMMENT '권한 키 (코드에서 참조, 예: manage_users)',
  `description` varchar(255) DEFAULT NULL COMMENT '권한에 대한 상세 설명'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 권한 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `name` varchar(100) NOT NULL COMMENT '역할명 (예: admin, user)',
  `description` varchar(255) DEFAULT NULL COMMENT '역할에 대한 상세 설명'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 역할 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_role_permissions`
--

CREATE TABLE `sys_role_permissions` (
  `role_id` int(11) NOT NULL COMMENT '역할 ID',
  `permission_id` int(11) NOT NULL COMMENT '권한 ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='역할과 권한 매핑';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_users`
--

CREATE TABLE `sys_users` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `employee_id` int(11) DEFAULT NULL COMMENT '연결된 직원 ID (hr_employees.id)',
  `kakao_id` varchar(255) NOT NULL COMMENT '카카오 고유 ID',
  `email` varchar(255) NOT NULL COMMENT '카카오 계정 이메일',
  `nickname` varchar(255) NOT NULL COMMENT '카카오 닉네임',
  `profile_image_url` varchar(512) DEFAULT NULL COMMENT '카카오 프로필 이미지 URL',
  `status` enum('대기','활성','비활성','삭제','차단') NOT NULL DEFAULT '대기' COMMENT '시스템 사용자 계정 상태',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 사용자 계정 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `sys_user_roles`
--

CREATE TABLE `sys_user_roles` (
  `user_id` int(11) NOT NULL COMMENT '사용자 ID',
  `role_id` int(11) NOT NULL COMMENT '역할 ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자와 역할 매핑';

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_employee_leave_status`
-- (See below for the actual view)
--
CREATE TABLE `v_employee_leave_status` (
`employee_id` int(11)
,`employee_name` varchar(255)
,`hire_date` date
,`department_name` varchar(255)
,`position_name` varchar(255)
,`current_balance` decimal(26,2)
,`granted_days` decimal(26,2)
,`pending_applications` bigint(21)
,`approved_this_year` bigint(21)
,`used_days_this_year` decimal(26,2)
,`remaining_days` decimal(26,2)
);

-- --------------------------------------------------------

--
-- 테이블 구조 `waste_collections`
--

CREATE TABLE `waste_collections` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
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
  `completed_at` datetime DEFAULT NULL COMMENT '완료일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 접수 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `waste_collection_items`
--

CREATE TABLE `waste_collection_items` (
  `id` int(11) NOT NULL COMMENT '고유 ID',
  `collection_id` int(11) NOT NULL COMMENT '수거 접수 ID (waste_collections.id)',
  `item_name` varchar(100) NOT NULL COMMENT '품목명',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT '수량'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='대형폐기물 수거 품목';

-- --------------------------------------------------------

--
-- 뷰 구조 `v_employee_leave_status`
--
DROP TABLE IF EXISTS `v_employee_leave_status`;

CREATE VIEW `v_employee_leave_status` AS
SELECT
    `e`.`id` AS `employee_id`,
    `e`.`name` AS `employee_name`,
    `e`.`hire_date` AS `hire_date`,
    `d`.`name` AS `department_name`,
    `p`.`name` AS `position_name`,
    COALESCE(`leave_balance`.`current_balance`, 0) AS `current_balance`,
    COALESCE(`leave_balance`.`granted_days`, 0) AS `granted_days`,
    COALESCE(`app_stats`.`pending_applications`, 0) AS `pending_applications`,
    COALESCE(`app_stats`.`approved_this_year`, 0) AS `approved_this_year`,
    COALESCE(`leave_balance`.`used_days_this_year`, 0) AS `used_days_this_year`,
    COALESCE(`leave_balance`.`current_balance`, 0) AS `remaining_days`
FROM
    `hr_employees` `e`
    LEFT JOIN `hr_departments` `d` ON `e`.`department_id` = `d`.`id`
    LEFT JOIN `hr_positions` `p` ON `e`.`position_id` = `p`.`id`
    LEFT JOIN (
        SELECT
            `hr_leave_logs`.`employee_id` AS `employee_id`,
            SUM(CASE
                WHEN `hr_leave_logs`.`transaction_type` IN ('초기부여', '연차부여', '근속연차부여', '월차부여', '연차추가', '사용취소') THEN `hr_leave_logs`.`amount`
                WHEN `hr_leave_logs`.`transaction_type` IN ('연차사용', '연차소멸', '연차차감') THEN -ABS(`hr_leave_logs`.`amount`)
                WHEN `hr_leave_logs`.`transaction_type` = '연차조정' THEN `hr_leave_logs`.`amount`
                ELSE 0
            END) AS `current_balance`,
            SUM(CASE
                WHEN `hr_leave_logs`.`transaction_type` IN ('초기부여', '연차부여', '근속연차부여', '월차부여') THEN `hr_leave_logs`.`amount`
                ELSE 0
            END) AS `granted_days`,
            SUM(CASE
                WHEN `hr_leave_logs`.`transaction_type` = '연차사용' AND YEAR(`hr_leave_logs`.`created_at`) = YEAR(CURDATE()) THEN ABS(`hr_leave_logs`.`amount`)
                WHEN `hr_leave_logs`.`transaction_type` = '사용취소' AND YEAR(`hr_leave_logs`.`created_at`) = YEAR(CURDATE()) THEN -ABS(`hr_leave_logs`.`amount`)
                ELSE 0
            END) AS `used_days_this_year`
        FROM
            `hr_leave_logs`
        GROUP BY
            `hr_leave_logs`.`employee_id`
    ) `leave_balance` ON `e`.`id` = `leave_balance`.`employee_id`
    LEFT JOIN (
        SELECT
            `hr_leave_applications`.`employee_id` AS `employee_id`,
            COUNT(CASE WHEN `hr_leave_applications`.`status` = '대기' THEN 1 END) AS `pending_applications`,
            COUNT(CASE WHEN `hr_leave_applications`.`status` = '승인' AND YEAR(`hr_leave_applications`.`start_date`) = YEAR(CURDATE()) THEN 1 END) AS `approved_this_year`
        FROM
            `hr_leave_applications`
        GROUP BY
            `hr_leave_applications`.`employee_id`
    ) `app_stats` ON `e`.`id` = `app_stats`.`employee_id`
WHERE
    `e`.`termination_date` IS NULL
ORDER BY
    `e`.`id` ASC;

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `hr_departments`
--
ALTER TABLE `hr_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `fk_department_parent` (`parent_id`);

--
-- 테이블의 인덱스 `hr_department_managers`
--
ALTER TABLE `hr_department_managers`
  ADD PRIMARY KEY (`department_id`,`employee_id`),
  ADD KEY `fk_manager_employee_id` (`employee_id`);

--
-- 테이블의 인덱스 `hr_department_view_permissions`
--
ALTER TABLE `hr_department_view_permissions`
  ADD PRIMARY KEY (`department_id`,`permitted_department_id`),
  ADD KEY `fk_view_permission_permitted_department_id` (`permitted_department_id`);

--
-- 테이블의 인덱스 `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `fk_employees_department` (`department_id`),
  ADD KEY `fk_employees_position` (`position_id`);

--
-- 테이블의 인덱스 `hr_employee_change_logs`
--
ALTER TABLE `hr_employee_change_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `fk_log_changer_employee_id` (`changer_employee_id`);

--
-- 테이블의 인덱스 `hr_holidays`
--
ALTER TABLE `hr_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_holidays_department` (`department_id`);

--
-- 테이블의 인덱스 `hr_leave_applications`
--
ALTER TABLE `hr_leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_approver_id` (`approver_id`),
  ADD KEY `idx_leave_apps_employee_dates` (`employee_id`,`start_date`,`end_date`),
  ADD KEY `idx_leave_apps_status_created` (`status`,`created_at`);

--
-- 테이블의 인덱스 `hr_leave_cancellations`
--
ALTER TABLE `hr_leave_cancellations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_approver_id` (`approver_id`);

--
-- 테이블의 인덱스 `hr_leave_logs`
--
ALTER TABLE `hr_leave_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `fk_leave_log_creator` (`created_by`),
  ADD KEY `idx_leave_logs_employee_created` (`employee_id`,`created_at`),
  ADD KEY `idx_leave_logs_grant_year` (`grant_year`),
  ADD KEY `idx_leave_logs_employee_grant_year` (`employee_id`,`grant_year`);

--
-- 테이블의 인덱스 `hr_leave_policies`
--
ALTER TABLE `hr_leave_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_policy_year` (`policy_year`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- 테이블의 인덱스 `hr_positions`
--
ALTER TABLE `hr_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 테이블의 인덱스 `illegal_disposal_cases2`
--
ALTER TABLE `illegal_disposal_cases2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coords` (`latitude`,`longitude`),
  ADD KEY `idx_waste_type` (`waste_type`),
  ADD KEY `idx_status` (`status`);

--
-- 테이블의 인덱스 `supply_categories`
--
ALTER TABLE `supply_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- 테이블의 인덱스 `supply_distributions`
--
ALTER TABLE `supply_distributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_employee_date` (`employee_id`,`distribution_date`),
  ADD KEY `idx_department_date` (`department_id`,`distribution_date`),
  ADD KEY `idx_item_date` (`item_id`,`distribution_date`),
  ADD KEY `idx_distributed_by` (`distributed_by`),
  ADD KEY `idx_cancelled` (`is_cancelled`);

--
-- 테이블의 인덱스 `supply_distribution_documents`
--
ALTER TABLE `supply_distribution_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_distribution_date` (`distribution_date`),
  ADD KEY `idx_status` (`status`);

--
-- 테이블의 인덱스 `supply_distribution_document_employees`
--
ALTER TABLE `supply_distribution_document_employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- 테이블의 인덱스 `supply_distribution_document_items`
--
ALTER TABLE `supply_distribution_document_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `item_id` (`item_id`);

--
-- 테이블의 인덱스 `supply_items`
--
ALTER TABLE `supply_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_item_code` (`item_code`);

--
-- 테이블의 인덱스 `supply_plans`
--
ALTER TABLE `supply_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_year_item` (`year`,`item_id`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- 테이블의 인덱스 `supply_purchases`
--
ALTER TABLE `supply_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_date` (`item_id`,`purchase_date`),
  ADD KEY `idx_received` (`is_received`),
  ADD KEY `idx_purchase_date` (`purchase_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- 테이블의 인덱스 `supply_stocks`
--
ALTER TABLE `supply_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_id` (`item_id`),
  ADD KEY `idx_current_stock` (`current_stock`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- 테이블의 인덱스 `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `fk_activity_log_employee_id` (`employee_id`);

--
-- 테이블의 인덱스 `sys_menus`
--
ALTER TABLE `sys_menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_parent_id` (`parent_id`);

--
-- 테이블의 인덱스 `sys_permissions`
--
ALTER TABLE `sys_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- 테이블의 인덱스 `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 테이블의 인덱스 `sys_role_permissions`
--
ALTER TABLE `sys_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- 테이블의 인덱스 `sys_users`
--
ALTER TABLE `sys_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kakao_id` (`kakao_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- 테이블의 인덱스 `sys_user_roles`
--
ALTER TABLE `sys_user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- 테이블의 인덱스 `waste_collections`
--
ALTER TABLE `waste_collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coords` (`latitude`,`longitude`),
  ADD KEY `fk_waste_collection_created_by` (`created_by`),
  ADD KEY `fk_waste_collection_completed_by` (`completed_by`);

--
-- 테이블의 인덱스 `waste_collection_items`
--
ALTER TABLE `waste_collection_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_waste_item_collection_id` (`collection_id`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `hr_departments`
--
ALTER TABLE `hr_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_employees`
--
ALTER TABLE `hr_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_employee_change_logs`
--
ALTER TABLE `hr_employee_change_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_holidays`
--
ALTER TABLE `hr_holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_leave_applications`
--
ALTER TABLE `hr_leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '신청 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_leave_cancellations`
--
ALTER TABLE `hr_leave_cancellations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '취소 신청 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_leave_logs`
--
ALTER TABLE `hr_leave_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '로그 ID';

--
-- 테이블의 AUTO_INCREMENT `hr_leave_policies`
--
ALTER TABLE `hr_leave_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `hr_positions`
--
ALTER TABLE `hr_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `illegal_disposal_cases2`
--
ALTER TABLE `illegal_disposal_cases2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_categories`
--
ALTER TABLE `supply_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_distributions`
--
ALTER TABLE `supply_distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_distribution_documents`
--
ALTER TABLE `supply_distribution_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `supply_distribution_document_employees`
--
ALTER TABLE `supply_distribution_document_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `supply_distribution_document_items`
--
ALTER TABLE `supply_distribution_document_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `supply_items`
--
ALTER TABLE `supply_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_plans`
--
ALTER TABLE `supply_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_purchases`
--
ALTER TABLE `supply_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `supply_stocks`
--
ALTER TABLE `supply_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `sys_menus`
--
ALTER TABLE `sys_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `sys_permissions`
--
ALTER TABLE `sys_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `sys_users`
--
ALTER TABLE `sys_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `waste_collections`
--
ALTER TABLE `waste_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 테이블의 AUTO_INCREMENT `waste_collection_items`
--
ALTER TABLE `waste_collection_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID';

--
-- 덤프된 테이블의 제약사항
--

--
-- 테이블의 제약사항 `hr_departments`
--
ALTER TABLE `hr_departments`
  ADD CONSTRAINT `fk_department_parent` FOREIGN KEY (`parent_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `hr_department_managers`
--
ALTER TABLE `hr_department_managers`
  ADD CONSTRAINT `fk_manager_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_manager_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `hr_department_view_permissions`
--
ALTER TABLE `hr_department_view_permissions`
  ADD CONSTRAINT `fk_view_permission_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_view_permission_permitted_department_id` FOREIGN KEY (`permitted_department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `hr_employee_change_logs`
--
ALTER TABLE `hr_employee_change_logs`
  ADD CONSTRAINT `fk_log_changer_employee_id` FOREIGN KEY (`changer_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `hr_holidays`
--
ALTER TABLE `hr_holidays`
  ADD CONSTRAINT `fk_holidays_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `hr_leave_applications`
--
ALTER TABLE `hr_leave_applications`
  ADD CONSTRAINT `fk_leave_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leave_app_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `hr_leave_cancellations`
--
ALTER TABLE `hr_leave_cancellations`
  ADD CONSTRAINT `fk_leave_cancel_application` FOREIGN KEY (`application_id`) REFERENCES `hr_leave_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_leave_cancel_approver` FOREIGN KEY (`approver_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leave_cancel_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `hr_leave_logs`
--
ALTER TABLE `hr_leave_logs`
  ADD CONSTRAINT `fk_leave_log_creator` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `fk_leave_log_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `supply_categories`
--
ALTER TABLE `supply_categories`
  ADD CONSTRAINT `supply_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `supply_categories` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `supply_distributions`
--
ALTER TABLE `supply_distributions`
  ADD CONSTRAINT `supply_distributions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_4` FOREIGN KEY (`distributed_by`) REFERENCES `hr_employees` (`id`),
  ADD CONSTRAINT `supply_distributions_ibfk_5` FOREIGN KEY (`cancelled_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `supply_distribution_documents`
--
ALTER TABLE `supply_distribution_documents`
  ADD CONSTRAINT `supply_distribution_documents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- 테이블의 제약사항 `supply_distribution_document_employees`
--
ALTER TABLE `supply_distribution_document_employees`
  ADD CONSTRAINT `supply_distribution_document_employees_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_distribution_document_employees_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`);

--
-- 테이블의 제약사항 `supply_distribution_document_items`
--
ALTER TABLE `supply_distribution_document_items`
  ADD CONSTRAINT `supply_distribution_document_items_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `supply_distribution_documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_distribution_document_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`);

--
-- 테이블의 제약사항 `supply_items`
--
ALTER TABLE `supply_items`
  ADD CONSTRAINT `supply_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `supply_categories` (`id`);

--
-- 테이블의 제약사항 `supply_plans`
--
ALTER TABLE `supply_plans`
  ADD CONSTRAINT `supply_plans_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- 테이블의 제약사항 `supply_purchases`
--
ALTER TABLE `supply_purchases`
  ADD CONSTRAINT `supply_purchases_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`),
  ADD CONSTRAINT `supply_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`);

--
-- 테이블의 제약사항 `supply_stocks`
--
ALTER TABLE `supply_stocks`
  ADD CONSTRAINT `supply_stocks_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `supply_items` (`id`);

--
-- 테이블의 제약사항 `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `sys_menus`
--
ALTER TABLE `sys_menus`
  ADD CONSTRAINT `fk_menu_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `sys_menus` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `sys_role_permissions`
--
ALTER TABLE `sys_role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `sys_users`
--
ALTER TABLE `sys_users`
  ADD CONSTRAINT `users_fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `sys_user_roles`
--
ALTER TABLE `sys_user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `waste_collections`
--
ALTER TABLE `waste_collections`
  ADD CONSTRAINT `fk_waste_collection_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_waste_collection_created_by` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `waste_collection_items`
--
ALTER TABLE `waste_collection_items`
  ADD CONSTRAINT `fk_waste_item_collection_id` FOREIGN KEY (`collection_id`) REFERENCES `waste_collections` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
