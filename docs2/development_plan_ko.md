# 기능 개발 프로세스 문서

## 1. 개요
본 문서는 `애플리케이션 기능 분석 및 개선 제안 보고서`에서 제안된 기능 중, 우선적으로 개발할 기능들에 대한 구체적인 개발 프로세스를 정의합니다. 각 기능별 목표, 필요한 데이터베이스 스키마 변경, 백엔드 API 개발, 프론트엔드 UI/UX 구현 계획을 포함합니다.

---

## 2. 직원 관리 (Employee Management) 개선

### 2.1. 인사 발령 이력 관리
- **목표:** 직원의 부서 이동, 승진 등 경력 변경 이력을 시스템에서 조회할 수 있도록 합니다. (데이터는 직원 정보 수정 시 자동으로 기록됨)
- **데이터베이스 변경 사항:**
    - **변경 없음.** 기존 `hr_employee_change_logs` 테이블을 활용합니다. 이 테이블은 이미 `EmployeeService`에서 직급, 부서 등이 변경될 때마다 이력을 기록하고 있습니다.
- **백엔드 개발:**
    - `EmployeeService`에 관련 로직 추가 또는 수정:
        - `getCareerHistory(employeeId)`: `EmployeeChangeLogRepository`를 사용하여 특정 직원의 `hr_employee_change_logs` 데이터 중 'department_id', 'position_id' 등 경력과 관련된 변경 이력만 필터링하여 조회하는 메소드를 구현합니다.
    - API 엔드포인트 구현 (`/api/employees/{id}/career`):
        - `GET /`: 해당 직원의 모든 인사 발령 이력 조회. (신규 구현)
        - `POST /`: **필요 없음.** 직원 정보가 업데이트될 때 `EmployeeService`에서 자동으로 이력이 기록되므로 별도의 생성 API는 불필요합니다.
- **프론트엔드 개발:**
    - 직원 상세 정보 페이지에 '인사 발령 이력' 탭 추가.
    - 탭 내부에 발령일(`changed_at`), 변경 내용(`field_name`, `old_value`, `new_value`) 등을 표시하는 타임라인 또는 테이블 형태의 UI 구현.

### 2.2. 퇴사 처리 프로세스
- **목표:** 퇴사한 직원의 계정을 안전하게 비활성화하고, 시스템상에서 '퇴사자'로 명확하게 구분하는 절차를 수립합니다.
- **데이터베이스 변경 사항:**
    - **변경 없음.** 기존 `hr_employees` 테이블의 `termination_date` (퇴사일) 컬럼을 활용합니다. 이 값이 `NULL`이 아니면 퇴사자로 간주합니다.
- **백엔드 개발:**
    - `EmployeeService`에 퇴사 처리 로직 추가:
        - `processTermination(employeeId, terminationDate)`:
            1. 직원의 `termination_date` 컬럼에 파라미터로 받은 퇴사일을 업데이트.
            2. 해당 직원과 연결된 `sys_users` 계정의 `status`를 'blocked' 등으로 변경하여 로그인 비활성화.
    - API 엔드포인트 구현 (`/api/employees/{id}/terminate`):
        - `POST /`: 해당 직원을 퇴사 처리 (body에 `termination_date` 포함).
- **프론트엔드 개발:**
    - 직원 관리 목록 페이지에 '퇴사 처리' 버튼 추가.
    - 버튼 클릭 시, 퇴사일을 입력받는 확인 모달(Modal) 표시.
    - 직원 목록에서 `termination_date` 유무에 따라 재직자/퇴사자를 필터링하여 보거나 숨길 수 있는 옵션 추가.

---

## 3. 휴가 관리 (Leave Management) 개선

### 3.1. 다양한 휴가 유형 관리
- **목표:** 관리자가 직접 연차, 반차 외에 병가, 경조사 휴가 등 새로운 휴가 유형을 생성하고, 유형별 속성(연차 차감 여부 등)을 설정할 수 있도록 합니다.
- **데이터베이스 변경 사항:**
    - `leave_types` 테이블 신규 생성:
        - `id` (PK)
        - `name` (휴가 유형 이름, 예: "병가", "경조사 휴가")
        - `deducts_leave` (연차 차감 여부, boolean)
        - `is_active` (활성 여부)
        - `created_at`, `updated_at`
    - `leave_requests` 테이블의 `leave_type` 컬럼을 `leave_type_id` (FK to `leave_types`)로 변경.
- **백엔드 개발:**
    - `LeaveTypeService` 신규 생성 또는 `LeaveService`에 로직 추가:
        - 휴가 유형 CRUD 로직 구현.
    - `LeaveService`의 `requestLeave` 로직 수정:
        - 신청 시 `leave_type_id`를 받도록 변경.
        - 선택된 휴가 유형의 `deducts_leave` 속성에 따라 잔여 연차를 차감하도록 로직 분기.
    - API 엔드포인트 구현:
        - `GET /api/leave-types`: 모든 휴가 유형 목록 조회.
        - `POST /api/leave-types`: 새 휴가 유형 생성 (관리자).
        - `PUT /api/leave-types/{id}`: 휴가 유형 수정 (관리자).
        - `DELETE /api/leave-types/{id}`: 휴가 유형 삭제 (관리자).
- **프론트엔드 개발:**
    - (관리자 페이지) '휴가 유형 관리' 메뉴 추가:
        - 휴가 유형을 추가/수정/삭제할 수 있는 CRUD UI 구현.
    - (사용자) 휴가 신청 페이지:
        - 휴가 종류 선택 드롭다운을 DB에서 조회한 `leave_types` 목록으로 동적 생성.

### 3.2. 팀 휴가 캘린더
- **목표:** 관리자 또는 팀장이 팀원들의 휴가 신청 및 확정 현황을 월별 캘린더 형태로 한눈에 파악할 수 있도록 합니다.
- **데이터베이스 변경 사항:**
    - 변경 없음. 기존 `leave_requests`와 `employees`, `departments` 테이블의 정보를 조합하여 조회.
- **백엔드 개발:**
    - `LeaveService`에 캘린더 데이터 조회 로직 추가:
        - `getLeaveCalendar(year, month, departmentId)`: 특정 부서의 월별 휴가 데이터를 조회.
        - 반환 데이터 형식: `[{ date: 'YYYY-MM-DD', employee_name: '홍길동', status: 'approved', leave_type: 'annual' }, ...]`
    - API 엔드포인트 구현 (`/api/leaves/calendar`):
        - `GET /?year=...&month=...&department_id=...`: 특정 부서의 월별 휴가 데이터를 조회.
- **프론트엔드 개발:**
    - '팀 휴가 현황' 메뉴 추가.
    - FullCalendar.js 또는 유사한 캘린더 라이브러리를 사용하여 UI 구현.
    - 페이지 진입 시 API를 호출하여 현재 월의 팀원 휴가 데이터를 캘린더에 렌더링.
    - 월 이동, 부서 선택 필터 기능 구현.
    - 캘린더의 각 이벤트를 클릭하면 휴가 신청 상세 정보를 볼 수 있는 모달(Modal) 표시.

---

## 4. 휴일 관리 (Holiday Management) 개선

### 4.1. 반복 휴일 설정
- **목표:** 매년 반복되는 공휴일(예: 신정, 삼일절)을 한 번만 등록하면, 시스템이 매년 해당 날짜를 휴일로 자동 인식하도록 합니다.
- **데이터베이스 변경 사항:**
    - `holidays` 테이블에 `is_recurring` (매년 반복 여부, boolean) 컬럼 추가.
    - `holidays` 테이블에 `month` (int), `day` (int) 컬럼을 추가하고, 반복되지 않는 휴일에만 기존 `date` 컬럼 사용.
- **백엔드 개발:**
    - `HolidayService`의 휴일 조회 로직(`findForDateRange` 등) 수정:
        - 조회 시, `is_recurring`이 `true`인 휴일들을 현재 연도와 조합하여 결과에 포함시키도록 로직 변경.
        - 예: 3월 1일을 조회할 때, `month=3`, `day=1`이고 `is_recurring=true`인 '삼일절' 데이터를 찾아서 결과에 추가.
    - 휴일 생성/수정 로직 변경:
        - '매년 반복' 옵션을 처리할 수 있도록 수정.
- **프론트엔드 개발:**
    - 휴일 등록/수정 폼에 '매년 반복' 체크박스 추가.
    - 체크박스 선택 시, 날짜 입력 필드가 '연도'는 무시하고 '월-일'만 입력받도록 UI 변경.

### 4.2. 법정 공휴일 데이터 가져오기 (Import)
- **목표:** 관리자가 공공 API 등을 통해 특정 연도의 대한민국 법정 공휴일 데이터를 일괄적으로 시스템에 등록할 수 있도록 하여, 수동 입력의 번거로움을 줄입니다.
- **데이터베이스 변경 사항:**
    - 변경 없음.
- **백엔드 개발:**
    - `HolidayService`에 공휴일 가져오기 로직 추가:
        - `importLegalHolidays(year)`:
            1. 대한민국 공공데이터포털의 '특일 정보' API (또는 다른 신뢰할 수 있는 API) 호출.
            2. API 응답(JSON 또는 XML)을 파싱하여 해당 연도의 공휴일 목록 추출.
            3. 각 공휴일에 대해 `HolidayRepository`를 사용하여 DB에 저장. (이미 존재하는 경우 중복 저장 방지 로직 필요).
    - API 엔드포인트 구현 (`/api/holidays/import`):
        - `POST /?year=...`: 특정 연도의 공휴일을 가져와 DB에 저장 (관리자).
- **프론트엔드 개발:**
    - 휴일 관리 페이지에 '공휴일 가져오기' 버튼 추가.
    - 버튼 클릭 시, 연도를 입력받는 폼을 포함한 모달(Modal) 표시.
    - '가져오기' 실행 시 백엔드 API를 호출하고, 진행 상태(로딩 스피너 등)와 완료/실패 메시지를 사용자에게 피드백.