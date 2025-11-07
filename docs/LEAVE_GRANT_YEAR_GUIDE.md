# 연차 부여연도 관리 가이드

## 개요

연차 관리 시스템에서 각 연차는 **부여된 연도(grant_year)**를 기준으로 관리됩니다. 이를 통해 연도별 연차 추적, 소멸 처리, 통계 분석이 가능합니다.

## 데이터베이스 구조

### hr_leave_logs 테이블

```sql
CREATE TABLE `hr_leave_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(20) DEFAULT '연차',
  `grant_year` int(4) DEFAULT NULL COMMENT '연차 부여연도',
  `log_type` enum('부여','사용','조정','소멸','취소') NOT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `amount` decimal(4,2) NOT NULL,
  `balance_after` decimal(4,2) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_leave_logs_grant_year` (`grant_year`),
  KEY `idx_leave_logs_employee_grant_year` (`employee_id`, `grant_year`)
);
```

## grant_year 설정 규칙

### 1. 연차/월차 부여 시 (필수)

모든 부여 거래에는 **반드시 grant_year를 지정**해야 합니다.

```php
// 2025년 연차 부여
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차부여',
    15.0,
    "2025년 기본연차 부여",
    null,
    1,
    2025  // grant_year 필수
);
```

**부여 거래 유형:**
- `초기부여` - 입사 첫 해 월차
- `연차부여` - 기본 연차 또는 비례 연차
- `근속연차부여` - 근속 가산 연차
- `월차부여` - 2년차 월차
- `연차추가` - 포상 등으로 인한 추가

### 2. 연차 사용 시 (선택)

사용 시에는 grant_year가 **NULL**입니다. 시스템이 자동으로 FIFO(선입선출) 방식으로 가장 오래된 연차부터 차감합니다.

```php
// 연차 사용 (grant_year = null)
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차사용',
    1.0,
    "연차 사용 승인",
    $applicationId,
    $approverId,
    null  // grant_year는 null
);
```

### 3. 연차 차감 시 (선택)

징계 등으로 인한 차감 시에도 grant_year는 **NULL**이며, FIFO 방식으로 처리됩니다.

```php
// 연차 차감 (grant_year = null)
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차차감',
    -2.0,
    "징계로 인한 차감",
    null,
    $adminId,
    null  // grant_year는 null
);
```

### 4. 연차 소멸 시 (선택)

소멸 처리 시에도 grant_year는 **NULL**입니다. 특정 연도의 연차를 소멸시키려면 별도 로직이 필요합니다.

```php
// 연차 소멸 (grant_year = null)
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차소멸',
    5.0,
    "2024년 연차 소멸",
    null,
    $adminId,
    null  // grant_year는 null
);
```

## 연도별 연차 조회

### 특정 연도에 부여된 연차 조회

```sql
-- 2025년에 부여된 연차 총량
SELECT 
    employee_id,
    SUM(amount) as granted_amount
FROM hr_leave_logs
WHERE grant_year = 2025
  AND transaction_type IN ('초기부여', '연차부여', '근속연차부여', '월차부여', '연차추가')
GROUP BY employee_id;
```

### 연도별 잔여 연차 계산

```sql
-- 2025년 부여 연차의 현재 잔여량 (FIFO 고려 필요)
SELECT 
    employee_id,
    grant_year,
    SUM(CASE 
        WHEN transaction_type IN ('초기부여', '연차부여', '근속연차부여', '월차부여', '연차추가', '사용취소') 
        THEN amount
        WHEN transaction_type IN ('연차사용', '연차소멸', '연차차감')
        THEN -ABS(amount)
        ELSE 0
    END) as balance
FROM hr_leave_logs
WHERE grant_year = 2025
GROUP BY employee_id, grant_year;
```

## 마이그레이션

### 기존 데이터 마이그레이션

```sql
-- 1. 컬럼 추가
ALTER TABLE `hr_leave_logs` 
ADD COLUMN `grant_year` INT(4) NULL COMMENT '연차 부여연도' AFTER `leave_type`;

-- 2. 기존 부여 로그에 grant_year 설정
UPDATE `hr_leave_logs` 
SET `grant_year` = YEAR(`created_at`)
WHERE `transaction_type` IN ('초기부여', '연차부여', '근속연차부여', '월차부여')
  AND `grant_year` IS NULL;

-- 3. 인덱스 추가
CREATE INDEX `idx_leave_logs_grant_year` ON `hr_leave_logs` (`grant_year`);
CREATE INDEX `idx_leave_logs_employee_grant_year` ON `hr_leave_logs` (`employee_id`, `grant_year`);
```

## 사용 예시

### 1. 연차 부여 (관리자)

```php
// LeaveAdminService::grantAnnualLeave()
$year = 2025;

// 기본 연차 15일 부여
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차부여',
    15.0,
    "{$year}년 기본연차 부여",
    null,
    1,
    $year  // 2025년 연차로 기록
);

// 근속 연차 3일 부여
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '근속연차부여',
    3.0,
    "{$year}년 근속연차 부여 (근속 7년)",
    null,
    1,
    $year  // 2025년 연차로 기록
);
```

### 2. 연차 조정 (포상)

```php
// LeaveAdminService::adjustLeave()
$this->adjustLeave(
    $employeeId,
    2.0,  // 2일 추가
    "우수사원 포상",
    $adminId,
    2025  // 2025년 연차로 추가
);
```

### 3. 연차 사용

```php
// LeaveService::approveApplication()
$this->leaveService->createLog(
    $employeeId,
    '연차',
    '연차사용',
    1.0,
    "연차 사용 승인",
    $applicationId,
    $approverId,
    null  // grant_year는 null (FIFO로 자동 차감)
);
```

## 주의사항

1. **부여 시 grant_year 필수**: 모든 연차/월차 부여 시 반드시 grant_year를 지정해야 합니다.

2. **사용/차감 시 null**: 연차 사용이나 차감 시에는 grant_year를 null로 설정합니다.

3. **FIFO 원칙**: 연차 사용 시 가장 오래된 연차(grant_year가 작은 것)부터 차감됩니다.

4. **연도별 소멸**: 특정 연도의 연차만 소멸시키려면 grant_year를 기준으로 별도 처리가 필요합니다.

5. **통계 조회**: 연도별 부여량, 사용량, 잔여량 통계는 grant_year를 기준으로 집계합니다.

## 향후 개선 사항

1. **FIFO 자동 처리**: 연차 사용 시 자동으로 가장 오래된 연차부터 차감하는 로직 구현
2. **연도별 소멸**: 특정 연도의 연차만 선택적으로 소멸시키는 기능
3. **이월 연차**: 전년도 미사용 연차의 이월 처리 (grant_year 유지)
4. **연도별 대시보드**: grant_year 기준 통계 및 리포트 기능
