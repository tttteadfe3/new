-- SQL migration script to update the database schema from the old version to the new version.

--
-- Step 1: `hr_departments` 테이블 마이그레이션
--
-- `path` 컬럼 추가
ALTER TABLE `hr_departments` ADD COLUMN `path` VARCHAR(255) DEFAULT NULL COMMENT '계층 구조 경로 (예: /1/3/)' AFTER `parent_id`;

-- `can_view_all_employees` 컬럼 삭제
ALTER TABLE `hr_departments` DROP COLUMN `can_view_all_employees`;

-- `hr_department_view_permissions` 테이블 생성
CREATE TABLE `hr_department_view_permissions` (
  `department_id` int(11) NOT NULL COMMENT '정보를 조회 당하는 부서 ID',
  `permitted_department_id` int(11) NOT NULL COMMENT '정보를 조회하는 부서 ID',
  PRIMARY KEY (`department_id`, `permitted_department_id`),
  KEY `fk_view_permission_permitted_department_id` (`permitted_department_id`),
  CONSTRAINT `fk_view_permission_department_id` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_view_permission_permitted_department_id` FOREIGN KEY (`permitted_department_id`) REFERENCES `hr_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='특정 부서가 다른 부서 정보를 조회할 수 있는 권한';

--
-- Step 2: `hr_positions` 테이블 마이그레이션
--
-- `level` 컬럼 추가
ALTER TABLE `hr_positions` ADD COLUMN `level` INT(11) NOT NULL DEFAULT 99 COMMENT '직급 레벨 (숫자가 낮을수록 높은 직급)' AFTER `name`;

-- 직급별 레벨 업데이트
UPDATE `hr_positions` SET `level` = 1 WHERE `name` = '대표';
UPDATE `hr_positions` SET `level` = 10 WHERE `name` = '부장';
UPDATE `hr_positions` SET `level` = 20 WHERE `name` = '과장';
UPDATE `hr_positions` SET `level` = 30 WHERE `name` = '팀장';
UPDATE `hr_positions` SET `level` = 40 WHERE `name` = '조장';
UPDATE `hr_positions` SET `level` = 50 WHERE `name` = '주임';
UPDATE `hr_positions` SET `level` = 90 WHERE `name` = '사원';

--
-- Step 3: Foreign Key 마이그레이션
--

-- `hr_employee_change_logs` 테이블
ALTER TABLE `hr_employee_change_logs`
  ADD COLUMN `changer_employee_id` INT(11) DEFAULT NULL COMMENT '변경 수행한 관리자 employee_id (시스템 변경 시 NULL)' AFTER `employee_id`,
  ADD CONSTRAINT `fk_log_changer_employee_id` FOREIGN KEY (`changer_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

UPDATE `hr_employee_change_logs` l
JOIN `sys_users` u ON l.changer_id = u.id
SET l.changer_employee_id = u.employee_id;

ALTER TABLE `hr_employee_change_logs` DROP FOREIGN KEY `fk_log_changer_id`;
ALTER TABLE `hr_employee_change_logs` DROP COLUMN `changer_id`;

-- `hr_leaves` 테이블
ALTER TABLE `hr_leaves`
  ADD COLUMN `approver_employee_id` INT(11) DEFAULT NULL COMMENT '처리한 관리자 employee_id' AFTER `status`,
  ADD CONSTRAINT `fk_leaves_approver_employee_id` FOREIGN KEY (`approver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

UPDATE `hr_leaves` l
JOIN `sys_users` u ON l.approved_by = u.id
SET l.approver_employee_id = u.employee_id;

ALTER TABLE `hr_leaves` DROP FOREIGN KEY `fk_leaves_approved_by`;
ALTER TABLE `hr_leaves` DROP COLUMN `approved_by`;

-- `hr_leave_adjustments_log` 테이블
ALTER TABLE `hr_leave_adjustments_log`
  ADD COLUMN `admin_employee_id` INT(11) DEFAULT NULL COMMENT '처리한 관리자 employee_id' AFTER `reason`;

UPDATE `hr_leave_adjustments_log` l
JOIN `sys_users` u ON l.admin_id = u.id
SET l.admin_employee_id = u.employee_id;

ALTER TABLE `hr_leave_adjustments_log`
  MODIFY `admin_employee_id` INT(11) NOT NULL,
  ADD CONSTRAINT `fk_leave_adj_admin_employee_id` FOREIGN KEY (`admin_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `hr_leave_adjustments_log` DROP COLUMN `admin_id`;

--
-- Step 4: `illegal_disposal_cases2` 테이블 마이그레이션 (데이터 보존)
--

-- address -> jibun_address 데이터 마이그레이션
UPDATE `illegal_disposal_cases2` SET `jibun_address` = `address` WHERE `address` IS NOT NULL;

ALTER TABLE `illegal_disposal_cases2`
  ADD COLUMN `processed_by` INT(11) DEFAULT NULL COMMENT '개선여부 처리한 직원 ID',
  ADD COLUMN `completed_by` INT(11) DEFAULT NULL COMMENT '완료 처리한 직원 ID',
  ADD COLUMN `completed_at` DATETIME DEFAULT NULL COMMENT '완료일시',
  ADD COLUMN `created_by` INT(11) DEFAULT NULL COMMENT '등록한 직원 ID';
  

-- 컬럼 이름 변경 및 오래된 컬럼 삭제
ALTER TABLE `illegal_disposal_cases2`
  CHANGE COLUMN `updated_at` `processed_at` DATETIME DEFAULT NULL COMMENT '개선여부 처리일시',
  DROP COLUMN `address`, 
  DROP COLUMN `issue_date`, 
  DROP COLUMN `collect_date`, 
  DROP COLUMN `user_id`, 
  DROP COLUMN `employee_id`;
--
-- Step 5: `waste_collections` 테이블 마이그레이션
--
-- `created_by`, `completed_by`, `completed_at` 컬럼 추가
ALTER TABLE `waste_collections`
  ADD COLUMN `created_by` INT(11) DEFAULT NULL COMMENT '등록한 직원 ID' AFTER `admin_memo`,
  ADD COLUMN `completed_by` INT(11) DEFAULT NULL COMMENT '완료 처리한 직원 ID' AFTER `created_by`,
  ADD COLUMN `completed_at` DATETIME DEFAULT NULL COMMENT '완료일시' AFTER `completed_by`;

-- `created_by` 데이터 채우기 (기존 employee_id 사용)
UPDATE `waste_collections` SET `created_by` = `employee_id`;

-- `completed_by`와 `completed_at` 데이터 채우기 ('processed' 상태인 경우)
UPDATE `waste_collections`
SET
  `completed_by` = `employee_id`,
  `completed_at` = `created_at`
WHERE `status` = 'processed';

-- 기존 컬럼 및 제약조건 삭제
ALTER TABLE `waste_collections` DROP FOREIGN KEY `fk_waste_collection_user_id`;
ALTER TABLE `waste_collections` DROP COLUMN `user_id`, DROP COLUMN `employee_id`;

-- 신규 제약조건 추가
ALTER TABLE `waste_collections`
  ADD CONSTRAINT `fk_waste_collection_created_by` FOREIGN KEY (`created_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_waste_collection_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- Step 6: Final Constraints and Index Updates
--

-- `hr_departments` 테이블의 외래 키 제약 조건 추가
ALTER TABLE `hr_departments`
  ADD CONSTRAINT `fk_department_parent` FOREIGN KEY (`parent_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL;

-- `sys_activity_logs` 테이블에 `employee_id` 추가 및 FK 설정
ALTER TABLE `sys_activity_logs`
  ADD COLUMN `employee_id` INT(11) DEFAULT NULL COMMENT '관련 직원 ID (직원 관련 활동 시)' AFTER `user_id`;

-- 사용자 활동 로그에 employee_id 채우기
UPDATE `sys_activity_logs` l
JOIN `sys_users` u ON l.user_id = u.id
SET l.employee_id = u.employee_id
WHERE l.user_id IS NOT NULL;

ALTER TABLE `sys_activity_logs`
  ADD KEY `fk_activity_log_employee_id` (`employee_id`),
  ADD CONSTRAINT `fk_activity_log_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

-- `sys_activity_logs` 테이블의 `employee_id` 업데이트 (프로필 변경 로그)
UPDATE `sys_activity_logs`
SET `employee_id` = SUBSTRING_INDEX(SUBSTRING_INDEX(`details`, '(id:', -1), ')', 1)
WHERE `action` = '프로필 변경 승인' AND `details` LIKE '%(id:%';


-- 오래된 테이블 삭제
DROP TABLE IF EXISTS `vehicle_repairs`;
DROP TABLE IF EXISTS `vehicles`;
