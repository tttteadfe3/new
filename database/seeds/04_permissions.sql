-- Renewed permissions based on resource.action convention with fixed IDs
INSERT INTO `sys_permissions` (`id`, `key`, `description`) VALUES
-- Dashboard (1)
(1, 'dashboard.view', '대시보드 조회'),

-- Employee Management (5)
(10, 'employee.view', '직원 목록 조회'),
(11, 'employee.create', '신규 직원 등록'),
(12, 'employee.update', '직원 정보 수정'),
(13, 'employee.delete', '직원 정보 삭제'),
(14, 'employee.approve', '직원 정보 변경 요청 승인/거부'),

-- Leave Management (5)
(20, 'leave.request', '휴가 신청 및 취소'),
(21, 'leave.view_own', '자신의 휴가 내역 조회'),
(22, 'leave.view_all', '전체 직원의 휴가 내역 조회'),
(23, 'leave.approve', '휴가 신청 승인/거부'),
(24, 'leave.manage_entitlement', '연차 부여 및 수동 조정'),

-- Littering Management (6)
-- Littering Management (7)
(30, 'littering.view', '부적정배출 현황 조회'),
(31, 'littering.create', '부적정배출 신규 등록'),
(32, 'littering.process', '부적정배출 처리(개선)'),
(33, 'littering.confirm', '관리자의 신고 내용 확인/승인'),
(34, 'littering.delete', '부적정배출 정보 삭제'),
(35, 'littering.restore', '삭제된 부적정배출 정보 복원'),
(36, 'littering.force_delete', '부적정배출 정보 영구 삭제'),

-- Waste Collection Management (2)
(40, 'waste.view', '대형폐기물 수거 현황 조회'),
(41, 'waste.manage_admin', '대형폐기물 관리(처리, 수정, 일괄등록 등)'),

-- User Management (3)
(50, 'user.view', '사용자 계정 목록 조회'),
(51, 'user.update', '사용자 상태 및 역할 변경'),
(52, 'user.link', '사용자-직원 정보 연결/해제'),

-- Role & Permission Management (5)
(60, 'role.view', '역할 목록 조회'),
(61, 'role.create', '신규 역할 생성'),
(62, 'role.update', '역할 정보 수정'),
(63, 'role.delete', '역할 삭제'),
(64, 'role.assign_permissions', '역할에 권한 매핑'),

-- Organization Management (1)
-- Organization Management (3)
(70, 'organization.manage', '부서 및 직급 관리'),
(71, 'organization.view', '조직도 조회'),
(72, 'department.manage_manager', '부서장 임명'),

-- Holiday Management (1)
(80, 'holiday.manage', '휴일 및 특정 근무일 관리'),

-- Menu Management (1)
(90, 'menu.manage', '시스템 메뉴 관리'),

-- Log Management (2)
(100, 'log.view', '활동 로그 조회'),
(101, 'log.delete', '활동 로그 삭제');
