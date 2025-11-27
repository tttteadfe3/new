-- ============================================================
-- Policy-Based Access Control (PBAC) 시스템 테이블 생성
-- 생성일: 2025-11-27
-- ============================================================

-- 1. 리소스 타입 테이블
CREATE TABLE `permission_resource_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(50) NOT NULL COMMENT '리소스 타입명 (employee, vehicle, leave, supply 등)',
  `description` varchar(255) DEFAULT NULL COMMENT '리소스 타입 설명',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성화 여부',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resource_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='권한 시스템 리소스 타입 정의';

-- 2. 액션 테이블
CREATE TABLE `permission_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(50) NOT NULL COMMENT '액션명 (view, create, update, delete, approve 등)',
  `description` varchar(255) DEFAULT NULL COMMENT '액션 설명',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성화 여부',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_action_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='권한 시스템 액션 정의';

-- 3. 권한 정책 테이블
CREATE TABLE `permission_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `name` varchar(100) NOT NULL COMMENT '정책명',
  `description` text COMMENT '정책 설명',
  `resource_type_id` int(11) NOT NULL COMMENT '리소스 타입 ID',
  `action_id` int(11) NOT NULL COMMENT '액션 ID',
  `scope_type` enum('own','department','managed_departments','parent_department_tree','global','custom') NOT NULL COMMENT '스코프 타입',
  `scope_config` json DEFAULT NULL COMMENT '스코프 설정 (JSON)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성화 여부',
  `priority` int(11) DEFAULT 0 COMMENT '우선순위 (높을수록 우선)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_resource_action` (`resource_type_id`, `action_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_priority` (`priority`),
  CONSTRAINT `fk_policy_resource_type` 
    FOREIGN KEY (`resource_type_id`) REFERENCES `permission_resource_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_policy_action` 
    FOREIGN KEY (`action_id`) REFERENCES `permission_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='권한 정책 정의';

-- 4. 역할-정책 매핑 테이블
CREATE TABLE `role_policies` (
  `role_id` int(11) NOT NULL COMMENT '역할 ID',
  `policy_id` int(11) NOT NULL COMMENT '정책 ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`role_id`, `policy_id`),
  KEY `idx_policy` (`policy_id`),
  CONSTRAINT `fk_role_policy_role` 
    FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_policy_policy` 
    FOREIGN KEY (`policy_id`) REFERENCES `permission_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='역할별 권한 정책 매핑';

-- 5. 사용자-정책 매핑 테이블 (예외 권한)
CREATE TABLE `user_policies` (
  `user_id` int(11) NOT NULL COMMENT '사용자 ID',
  `policy_id` int(11) NOT NULL COMMENT '정책 ID',
  `granted_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '부여일시',
  `granted_by` int(11) DEFAULT NULL COMMENT '부여자 ID',
  `expires_at` datetime DEFAULT NULL COMMENT '만료일시 (NULL이면 무기한)',
  `reason` varchar(500) DEFAULT NULL COMMENT '부여 사유',
  PRIMARY KEY (`user_id`, `policy_id`),
  KEY `idx_policy` (`policy_id`),
  KEY `idx_granted_by` (`granted_by`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_user_policy_user` 
    FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_policy_policy` 
    FOREIGN KEY (`policy_id`) REFERENCES `permission_policies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_policy_granter` 
    FOREIGN KEY (`granted_by`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='사용자별 예외 권한 정책 매핑';

-- 인덱스 생성
CREATE INDEX `idx_policy_scope_type` ON `permission_policies` (`scope_type`);
CREATE INDEX `idx_user_policy_active` ON `user_policies` (`user_id`, `expires_at`);
