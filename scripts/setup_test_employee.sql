-- 통합 테스트를 위한 테스트 직원 생성 스크립트
-- 시나리오: 2025년 7월 1일 입사자

-- 1. 테스트 직원 정보 삽입 (입사일: 2025-07-01)
INSERT INTO `hr_employees` (`name`, `employee_number`, `department_id`, `position_id`, `hire_date`)
VALUES ('김테스트', 'WS2507001', 1, 5, '2025-07-01');

-- 방금 생성된 직원의 ID를 @test_employee_id 변수에 저장
SET @test_employee_id = LAST_INSERT_ID();

-- 2. 해당 직원을 위한 시스템 사용자 계정 생성 (카카오 정보는 임의로 설정)
INSERT INTO `sys_users` (`employee_id`, `kakao_id`, `email`, `nickname`, `status`)
VALUES (@test_employee_id, 'test_kakao_12345', 'test@example.com', '김테스트', 'active');

-- 방금 생성된 사용자의 ID를 @test_user_id 변수에 저장
SET @test_user_id = LAST_INSERT_ID();

-- 3. 생성된 사용자에게 일반 '직원' 역할 부여 (role_id=2가 '직원' 역할이라고 가정)
INSERT INTO `sys_user_roles` (`user_id`, `role_id`)
VALUES (@test_user_id, 2);

-- 4. (중요) 신규 입사자에게 초기 월차를 부여하는 로직을 수동으로 실행
-- 실제 애플리케이션에서는 직원 생성 후 grantInitialMonthlyLeave() 서비스 메소드를 호출해야 함.
-- 여기서는 테스트를 위해 직접 로그를 삽입.
-- 2025년 7월 입사 -> 8, 9, 10, 11, 12월에 해당하는 5일의 월차 부여
INSERT INTO `hr_leave_logs` (`employee_id`, `leave_type`, `transaction_type`, `amount`, `reason`)
VALUES (@test_employee_id, 'monthly', 'grant_initial', 5.0, '2025년 신규 입사자 월차 부여');

COMMIT;

-- 생성된 ID 확인 (참고용)
SELECT @test_employee_id as employee_id, @test_user_id as user_id;
