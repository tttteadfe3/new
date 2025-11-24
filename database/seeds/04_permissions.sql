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
(15, 'employee.terminate', '직원 퇴사 처리'),
(16, 'employee.assign', '직원 인사 발령(부서/직급 변경)'),

-- Leave Management (3)
(20, 'leave.view', '자신의 연차 내역 조회'),
(21, 'leave.approve', '팀원의 연차 신청 승인/반려 및 현황 조회'),
(22, 'leave.manage', '전사 연차 부여, 소멸, 조정 등 제도 관리'),

-- Littering Management (8)
(30, 'littering.view', '부적정배출 현황 조회'),
(31, 'littering.create', '부적정배출 신규 등록'),
(32, 'littering.process', '부적정배출 처리(개선)'),
(33, 'littering.confirm', '관리자의 신고 내용 확인/승인'),
(34, 'littering.approve', '처리완료 건 최종 승인'),
(35, 'littering.delete', '부적정배출 정보 삭제'),
(36, 'littering.restore', '삭제된 부적정배출 정보 복원'),
(37, 'littering.force_delete', '부적정배출 정보 영구 삭제'),

-- Waste Collection Management (3)
(40, 'waste.view', '대형폐기물 수거 현황 조회'),
(41, 'waste.process', '대형폐기물 수거 처리'),
(42, 'waste.manage', '대형폐기물 인터넷배출 관리'),

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
(101, 'log.delete', '활동 로그 삭제'),

-- Supply Management (12)
(103, 'supply.category.manage', '지급품 분류 관리'),
(104, 'supply.item.view', '지급품 품목 조회'),
(105, 'supply.item.manage', '지급품 품목 관리'),
(110, 'supply.plan.view', '지급품 계획 조회'),
(111, 'supply.plan.manage', '지급품 계획 관리'),
(120, 'supply.purchase.view', '지급품 구매 조회'),
(121, 'supply.purchase.manage', '지급품 구매 관리'),
(130, 'supply.distribution.view', '지급품 지급 조회'),
(131, 'supply.distribution.manage', '지급품 지급 관리'),
(140, 'supply.report.view', '지급품 보고서 조회'),

-- Vehicle Management (7)
(150, 'vehicle.view', '차량 목록 조회'),
(151, 'vehicle.manage', '차량 정보 관리'),
(152, 'vehicle.work.view', '차량 작업 조회'),
(153, 'vehicle.work.report', '차량 작업 신고 (고장/정비)'),
(154, 'vehicle.work.manage', '차량 작업 처리 및 승인'),
(155, 'vehicle.inspection.view', '차량 검사 조회'),
(156, 'vehicle.inspection.manage', '차량 검사 관리');
