# 통합 변경 이력 (Changelog)

이 문서는 프로젝트의 주요 변경 사항, 특히 기존 코드베이스에 영향을 줄 수 있는 중요한 수정 내역을 기록합니다. 모든 개발 에이전트는 코드 변경 시 이 문서를 참조하고, 자신의 변경 사항을 아래 형식에 맞게 기록해야 합니다.

---
## [1.2.0 - 2025-10-27]

### ♻️ 리팩토링 (Refactoring)
- **데이터 조회 권한 로직 중앙화**:
  - **변경 이유**: 여러 서비스(`OrganizationService`, `EmployeeService`, `LeaveService` 등)에 분산되어 있던 부서 데이터 조회 권한 로직을 하나로 통합하여 유지보수성을 높이고 중복을 제거하기 위함.
  - **변경 내용**:
    - `DataScopeService`를 신설하여, 현재 사용자가 조회할 수 있는 부서 ID 목록을 계산하는 모든 권한 관련 로직을 중앙에서 관리하도록 함.
    - 기존에 각 서비스가 자체적으로 수행하던 권한 확인 로직을 모두 제거하고, `DataScopeService`를 호출하는 방식으로 통일함.
    - `AuthService`에서 데이터 조회 범위와 관련된 책임을 제거하여, 인증 및 기능 권한 관리에만 집중하도록 역할을 명확히 함.
  - **영향 범위**: `app/Services/OrganizationService.php`, `app/Services/EmployeeService.php`, `app/Services/LeaveService.php`, `app/Services/HolidayService.php`, `app/Services/UserService.php`, `app/Services/AuthService.php`, `app/Controllers/Api/OrganizationApiController.php`, `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**: `public/index.php` (DI 컨테이너 설정), `app/Repositories/DepartmentRepository.php` (`findByIds` 메소드 추가)
- **직원 데이터 접근 권한 로직 중앙화**:
  - **변경 이유**: 부서 데이터 접근 권한 중앙화의 후속 조치로, 특정 직원을 관리할 수 있는지 확인하는 로직(`canManageEmployee`)을 `AuthService`에서 `DataScopeService`로 이전하여 데이터 접근 범위 관련 책임을 일원화함.
  - **변경 내용**:
    - `DataScopeService`에 `canManageEmployee` 메소드를 구현하고, `getVisibleDepartmentIdsForCurrentUser`를 활용하여 효율적으로 권한을 확인하도록 개선.
    - `EmployeeApiController`가 `AuthService` 대신 `DataScopeService`의 `canManageEmployee`를 호출하도록 수정.
  - **영향 범위**: `app/Services/DataScopeService.php`, `app/Controllers/Api/EmployeeApiController.php`

### 🐛 버그 수정 (Bug Fixes)
- **리팩토링 과정에서 발생한 DI 컨테이너 및 메소드 호출 오류 수정**:
  - **문제**: 데이터 조회 권한 로직 중앙화 리팩토링 중 `OrganizationService`에 잘못된 의존성이 주입되고, `DataScopeService`에서 존재하지 않는 메소드를 호출하여 Fatal Error가 발생하는 문제.
  - **수정**:
    - `public/index.php`에서 `OrganizationService`의 생성자에 `DataScopeService`가 올바르게 주입되도록 수정.
    - `DataScopeService` 내에서 `authService->getCurrentUser()`로 잘못 호출된 부분을 `authService->user()`로 수정.
  - **영향 범위**: `public/index.php`, `app/Services/DataScopeService.php`
- **관리자 데이터 조회 권한 오류 수정**:
  - **문제**: 리팩토링 이후, 최상위 부서 관리자를 포함한 모든 사용자가 하위 부서의 데이터를 볼 수 없는 문제 발생.
  - **원인**: `DataScopeService`의 `getVisibleDepartmentIdsForCurrentUser` 메소드가 명시적으로 권한이 부여된 부서만 반환하고, 사용자의 소속 부서 및 그 하위 부서를 기본적으로 포함하지 않았음.
  - **수정**: `DataScopeService`의 로직을 수정하여, 모든 사용자가 자신의 소속 부서와 모든 하위 부서를 기본적으로 조회할 수 있도록 변경.
  - **영향 범위**: `app/Services/DataScopeService.php`

## [1.1.0 - 2025-10-27]

### ✨ 새로운 기능 (Features)
- **직원 관리 부서 필터에 권한 로직 적용**:
  - **설명**: `/employees` 페이지의 부서 선택 드롭다운에 현재 로그인한 사용자의 조회 권한(`hr_department_managers` 기반)이 적용되도록 개선했습니다.
  - **변경 내용**: `EmployeeApiController::getInitialData()`가 권한이 적용된 부서 목록을 반환하는 `OrganizationService::getManagableDepartments()`를 호출하도록 수정하여, 권한이 있는 부서만 필터에 표시되도록 변경했습니다.
  - **영향 범위**: `app/Controllers/Api/EmployeeApiController.php`

### 🐛 버그 수정 (Bug Fixes)
- **데이터 조회 권한 로직 수정**:
  - **문제**: 데이터 조회 권한이 없는 사용자에게도 자신의 소속 부서가 기본적으로 보여지는 문제.
  - **원인**: `OrganizationService::_getPermittedDepartmentIds()` 메서드가 `hr_department_managers` 확인 로직과 별개로 현재 사용자의 소속 부서를 항상 포함시키고 있었음.
  - **수정**: `OrganizationService`에서 자신의 소속 부서를 자동으로 포함하는 로직을 제거하여, `hr_department_managers` 테이블에 명시적으로 부여된 권한만 적용되도록 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`
- **모델 객체 처리 관련 치명적 오류 수정 (Fullstack)**:
  - **문제**: `OrganizationService`와 `EmployeeService`에서 `Department` 및 `Position` 모델 객체를 배열처럼 접근하여 "Cannot use object of type ... as array" Fatal Error가 발생하는 문제.
  - **원인**: `DepartmentRepository` 등이 반환하는 객체 배열을 처리하는 과정에서 잘못된 배열 구문(`$model['property']`)을 사용함.
  - **수정**: `OrganizationService::getManagableDepartments`, `EmployeeService::logChanges` 및 관련 헬퍼 메서드 내에서 객체 속성에 접근할 때 올바른 객체 구문(`$model->property`)을 사용하도록 일괄 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`, `app/Services/EmployeeService.php`
- **부서 생성 시 'name' 필드 누락 오류 수정**:
  - **문제**: 부서 생성 API 호출 시 `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'name' cannot be null` 오류 발생.
  - **원인**: `OrganizationService::createDepartment` 메서드에서 `DepartmentRepository::create`로 전달하는 데이터 배열에 `name` 필드가 누락됨.
  - **수정**: `createDepartment` 메서드 내에서 `create` 메서드로 전달할 데이터 배열을 명시적으로 생성하여 `name` 필드가 항상 포함되도록 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`

## [1.0.9 - 2025-10-26]

### ✨ 새로운 기능 (Features)
- **무단투기 승인 시 개선여부 상태 함께 처리**:
  - **설명**: `/littering/manage` 페이지의 상세 보기에서 '개선여부' 상태를 선택한 후, '승인' 버튼을 누를 때 해당 상태가 최종적으로 함께 서버에 저장되도록 기능을 개선했습니다.
  - **변경 내용**:
    - **Backend**: `approve` API가 요청 본문에 `corrected` 상태 값을 포함하여 처리하도록 컨트롤러, 서비스, 리포지토리 로직을 수정했습니다.
    - **Frontend**: '승인' 버튼 클릭 시, 상세 보기 내의 '개선여부' 드롭다운 값을 읽어 API 요청에 포함하도록 `littering-manage.js`의 `approveReport` 메서드를 수정했습니다.
  - **영향 범위**: `app/Controllers/Api/LitteringAdminApiController.php`, `app/Services/LitteringService.php`, `app/Repositories/LitteringRepository.php`, `app/Views/pages/littering/manage.php`, `public/assets/js/pages/littering-manage.js`

## [1.0.8 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **직원 API 목록 부서 필터 기능 수정**:
  - **문제**: `/api/employees` 엔드포인트에 `department_id` 쿼리 파라미터를 전달해도 부서별로 직원 목록이 필터링되지 않는 문제.
  - **원인**: `EmployeeRepository::getAll` 메서드에서 `department_id` 필터를 처리하는 SQL `WHERE` 조건절 생성이 누락되었음.
  - **수정**: `EmployeeRepository::getAll` 메서드에 `department_id` 파라미터가 존재할 경우, `WHERE` 절에 부서 필터링 조건을 추가하는 로직을 구현.
  - **영향 범위**: `app/Repositories/EmployeeRepository.php`
  - **함께 수정된 파일**: 없음

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
