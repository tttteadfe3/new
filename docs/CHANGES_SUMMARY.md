# 변경된 파일 요약

이 문서는 데이터 권한 로직 리팩토링 및 관련 오류 수정 작업으로 인해 변경된 파일의 목록과 주요 변경 사항을 요약합니다.

## 핵심 변경 사항

- **아키텍처 변경**: 데이터 접근 범위(스코프)를 결정하는 로직을 서비스/컨트롤러 계층에서 리포지토리 계층으로 이동시켰습니다.
- **Fail-Safe 구현**: `DataScopeService`를 도입하여 권한이 없는 경우 데이터가 노출되지 않도록(fail-safe) 보장합니다.
- **순환 의존성 해결**: `DataScopeService`와 리포지토리 간의 순환 의존성 문제를 해결하여 DI 컨테이너 오류를 수정했습니다.

---

## 변경된 파일 목록

### 1. Services

-   `app/Services/DataScopeService.php`
    -   **[수정]** `AuthService` 및 리포지토리에 대한 의존성을 제거하고, `SessionManager`와 `Database`에만 의존하도록 변경하여 순환 의존성 문제를 해결했습니다.
    -   **[추가]** 각 테이블(`employee`, `department`, `holiday`, `user`)에 맞는 스코프 적용 메서드(`apply...Scope`)를 추가하여 중앙에서 모든 권한 로직을 관리합니다.
    -   **[추가]** 권한 계산에 필요한 최소한의 DB 조회 로직을 비공개 메서드로 내장했습니다.

-   `app/Services/EmployeeService.php`
-   `app/Services/HolidayService.php`
-   `app/Services/LeaveService.php`
-   `app/Services/OrganizationService.php`
-   `app/Services/UserService.php`
    -   **[수-정]** 위 서비스들에서 `getVisibleDepartmentIdsForCurrentUser()`를 호출하여 수동으로 데이터 스코프를 적용하던 로직을 모두 제거했습니다. 이제 단순히 리포지토리의 메서드를 호출하기만 하면 자동으로 권한이 적용됩니다.

### 2. Repositories

-   `app/Repositories/DepartmentRepository.php`
-   `app/Repositories/EmployeeRepository.php`
-   `app/Repositories/HolidayRepository.php`
-   `app/Repositories/LeaveRepository.php`
-   `app/Repositories/UserRepository.php`
    -   **[수정]** 위 리포지토리들은 생성자를 통해 `DataScopeService`를 주입받도록 수정되었습니다.
    -   **[수정]** `getAll`, `findAll` 등 목록을 조회하는 메서드 내부에서 `DataScopeService`의 적절한 스코프 적용 메서드를 호출하여, 모든 쿼리에 자동으로 `WHERE` 절이 추가되도록 변경되었습니다.

### 3. Controllers

-   `app/Controllers/Api/EmployeeApiController.php`
-   `app/Controllers/Api/OrganizationApiController.php`
-   `app/Controllers/Web/AdminController.php`
    -   **[수정]** 수동으로 부서 목록을 필터링하던 로직을 제거하고, 리팩토링된 리포지토리의 `getAll()` 메서드를 직접 호출하도록 코드를 단순화했습니다.
    -   **[수정]** 컨트롤러에서 `BaseController`를 상속받아 공통 기능을 재사용하도록 구조를 개선했습니다.

### 4. Core & Configuration

-   `public/index.php`
    -   **[수정]** 리팩토링으로 인해 변경된 서비스와 리포지토리의 의존성 관계를 DI 컨테이너 설정에 정확하게 다시 반영했습니다. 순환 의존성이 발생하지 않도록 등록 순서를 조정하여 애플리케이션 시작 오류를 해결했습니다.

### 5. Documentation

-   `docs/PermissionAndDataAuthorization.md`
    -   **[추가/수정]** 이번에 리팩토링된 새로운 "Fail-Safe" 데이터 권한 아키텍처를 반영하여 문서를 전체적으로 업데이트했습니다.
-   `docs/CHANGES_SUMMARY.md`
    -   **[추가]** 본 파일을 추가하여 변경 사항을 쉽게 추적할 수 있도록 했습니다.
