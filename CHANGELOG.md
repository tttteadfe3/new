# 통합 변경 이력 (Changelog)

이 문서는 프로젝트의 주요 변경 사항, 특히 기존 코드베이스에 영향을 줄 수 있는 중요한 수정 내역을 기록합니다. 모든 개발 에이전트는 코드 변경 시 이 문서를 참조하고, 자신의 변경 사항을 아래 형식에 맞게 기록해야 합니다.

---

## [1.0.6 - 2025-10-26]

### ♻️ 리팩토링 (Refactoring)
- **데이터베이스 및 애플리케이션 전반의 상태 값 한글화 (수정 반영)**:
  - **변경 이유**: 코드 가독성 향상, 데이터 일관성 확보, 프론트엔드 변환 로직 제거를 위해 시스템 전반의 영어 상태 값을 한글로 통일. (기존 작업의 오류 수정)
  - **변경 내용**:
    - **[수정]** 마이그레이션 스크립트(`database/20251026_translate_status_enums.sql`)를 보완하여 누락된 상태 값(`illegal_disposal_cases2`의 'processed')을 추가하고, 불일치하던 상태 값(`'확인'`과 `'처리완료'` 등)을 코드와 통일. `sys_users`에 '차단' 상태를 추가.
    - **[수정]** PHP 백엔드 코드(`app/` 전체)와 JavaScript 프론트엔드 코드(`public/assets/js/` 전체)를 **수정된 스크립트와 완벽히 일치**하도록 재수정. 특히 `Littering` 관련 기능의 상태 값 불일치 오류를 해결.
  - **영향 범위**: `database/schema.sql`, `app/` 디렉토리 전체, `public/assets/js/` 디렉토리 전체
  - **함께 수정된 파일**: `database/20251026_translate_status_enums.sql` (신규), `app/Views/pages/leaves/approval.php`

## [1.0.5 - 2025-10-26]

### ♻️ 리팩토링 (Refactoring)
- **`waste.manage_admin` 권한 분리 및 이름 변경**:
  - **변경 이유**: 단일 책임 원칙에 따라, 하나의 권한이 너무 많은 책임을 갖는 것을 방지하고 역할 분리를 명확히 하기 위함.
  - **변경 내용**: 기존의 `waste.manage_admin` 권한을 다음과 같이 두 개의 구체적인 권한으로 분리하고 최종적으로 이름을 확정:
    - `waste.process`: 현장 등록 및 수거 처리 관련 기능(개별 처리)을 제어.
    - `waste.manage`: 인터넷 배출 신고 관리 기능(조회, 수정, 삭제, 일괄 등록)을 제어.
  - **영향 범위**: `database/seeds/04_permissions.sql`, `database/seeds/09_menus.sql`, `routes/web.php`, `routes/api.php`
  - **함께 수정된 파일**: 상기 영향 범위와 동일.

## [1.0.4 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **API 컨트롤러의 Fatal Error 수정**:
  - **문제**: `/api/employees/unlinked` 엔드포인트 호출 시 `Call to undefined method App\Core\Request::get()` Fatal Error 발생.
  - **원인**: `EmployeeApiController`에서 `Request` 객체의 존재하지 않는 `get()` 메서드를 호출함.
  - **수정**: `get()` 메서드 호출을 올바른 `input()` 메서드로 변경하여 API가 정상적으로 작동하도록 수정.
  - **영향 범위**: `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**: 없음

## [1.0.3 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **사용자 관리의 직원 연결 필터 기능 수정 (Fullstack)**:
  - **문제**: `/admin/users` 페이지에서 직원을 연결할 때 부서 필터가 작동하지 않아 다른 부서의 직원들이 목록에 포함되는 문제.
  - **원인**: 1) 프론트엔드 `users.js`에서 API 요청 시 `department_id`를 보내지 않았고, 2) 백엔드 API에서 해당 파라미터를 처리하는 로직이 누락되었음.
  - **수정**:
    - **Frontend**: `users.js`의 `departmentFilter` 이벤트 리스너가 선택된 부서 ID를 `loadUnlinkedEmployees()` 함수로 전달하도록 수정.
    - **Backend**: `EmployeeApiController`, `EmployeeService`, `EmployeeRepository`를 모두 수정하여 `department_id` 파라미터를 받아 SQL 쿼리에서 필터링하도록 로직 추가.
  - **영향 범위**: `public/assets/js/pages/users.js`, `app/Controllers/Api/EmployeeApiController.php`, `app/Services/EmployeeService.php`, `app/Repositories/EmployeeRepository.php`
  - **함께 수정된 파일**: 상기 영향 범위와 동일

## [1.0.2 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **부서 관리자 권한 확인 로직 수정**:
  - **문제**: 부서 관리자로 지정된 사용자가 부서 데이터를 관리할 수 없는 심각한 버그.
  - **원인**: `AuthService::canManageEmployee()` 메서드가 `hr_departments` 테이블의 존재하지 않는 `manager_id` 컬럼을 확인하고 있었음. 올바른 로직은 `hr_department_managers` 테이블을 통해 권한을 확인해야 함.
  - **수정**: `departmentRepository->findDepartmentIdsWithEmployeeViewPermission()`를 호출하여 사용자가 관리하는 부서 목록을 가져온 후, 대상 직원의 부서가 해당 목록 또는 그 상위 부서에 속하는지 재귀적으로 확인하도록 로직을 변경.
  - **영향 범위**: `app/Services/AuthService.php`
  - **함께 수정된 파일**: 없음

## [1.0.1 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **조직도 데이터 접근 권한 수정**:
  - **문제**: `hr_department_managers`에 등록된 사용자임에도 불구하고 조직도 조회 시 권한이 없다는 오류가 발생하는 문제.
  - **원인**: `OrganizationService::getOrganizationChartData()` 메서드가 모든 부서 정보를 필터링 없이 반환하여, 프론트엔드에서 권한 없는 데이터에 접근 시도.
  - **수정**: `getVisibleDepartmentIdsForCurrentUser()`를 사용하여 현재 사용자가 볼 수 있는 부서만 필터링하도록 로직 추가.
  - **영향 범위**: `app/Services/OrganizationService.php`
  - **함께 수정된 파일**: 없음

## [버전 - YYYY-MM-DD]

### ✨ 새로운 기능 (Features)
- **(설명)**: (작업 내용에 대한 상세 설명)
- **(설명)**: (작업 내용에 대한 상세 설명)

### 🐛 버그 수정 (Bug Fixes)
- **(설명)**: (작업 내용에 대한 상세 설명)

### ♻️ 리팩토링 (Refactoring)
- **클래스명::메서드명() 변경**:
  - **변경 이유**: (예: 성능 개선, 로직 명확화 등)
  - **영향 범위**: (해당 메서드를 호출하던 모든 파일 목록)
  - **함께 수정된 파일**: (변경에 따라 함께 수정한 파일 목록)

### 💥 주요 변경 사항 (Breaking Changes)
- **(설명)**: (하위 호환성을 깨뜨리는 변경 사항에 대한 상세 설명)

---

### 예시:

## [1.0.0 - 2025-10-25]

### ♻️ 리팩토링 (Refactoring)
- **`EmployeeService::getEmployee()` 메서드 시그니처 변경**:
  - **변경 이유**: 직원 ID 외에 사번(employee\_number)으로도 조회할 수 있도록 파라미터 추가.
  - **영향 범위**:
    - `app/Controllers/Web/EmployeeController.php`
    - `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**:
    - `app/Controllers/Web/EmployeeController.php`
    - `app/Controllers/Api/EmployeeApiController.php`
