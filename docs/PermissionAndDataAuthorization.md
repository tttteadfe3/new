# 시스템 권한 및 데이터 접근 제어 상세 명세서

## 1. 개요

### 1.1. 문서의 목적

이 문서는 신규 입사자를 포함한 모든 개발자가 시스템의 권한 관리 체계를 명확히 이해하고, 관련 기능을 개발하거나 유지보수할 때 발생할 수 있는 문제를 예방하는 것을 목표로 합니다. 시스템의 권한은 크게 두 가지로 나뉩니다.

### 1.2. 권한 체계의 종류

1.  **퍼미션 권한 (Permission Authorization)**
    *   **무엇을**: 사용자가 시스템의 특정 기능(메뉴, 버튼, API 등)을 사용할 수 있는지 여부를 결정합니다.
    *   **어떻게**: 사용자의 역할(Role)에 부여된 권한(Permission)을 기반으로 접근을 제어합니다.
    *   **예시**: "인사팀장" 역할은 '직원 관리' 메뉴에 접근할 수 있지만, "일반 직원" 역할은 접근할 수 없습니다.

2.  **데이터 권한 (Data Authorization)**
    *   **무엇을**: 특정 기능에 접근한 사용자가 조회하거나 수정할 수 있는 데이터의 범위를 제한합니다.
    *   **어떻게**: 사용자의 소속 부서, 직책 등을 기준으로 접근 가능한 데이터의 범위를 동적으로 결정합니다.
    *   **예시**: '직원 목록 조회' 기능은 모든 직원이 사용할 수 있지만, A팀 팀장은 A팀 소속 직원의 정보만 조회할 수 있습니다.

## 2. 퍼미션 권한 (메뉴/기능 권한)

퍼미션 권한은 사용자가 특정 기능에 접근할 수 있는지 여부를 결정합니다. 이 시스템에서는 역할 기반 접근 제어(RBAC, Role-Based Access Control) 모델을 사용합니다.

### 2.1. 처리 흐름

API 요청에 대한 퍼미션 권한 확인은 다음 순서로 이루어집니다.

1.  **라우트 정의 (`routes/api.php`)**: 클라이언트의 요청이 들어오면, `api.php`에 정의된 라우트 중 하나와 매칭됩니다. 각 라우트에는 `auth` 미들웨어와 `permission` 미들웨어가 적용되어 있습니다.
    ```php
    // 예시: /api/employees GET 요청
    $router->get('/employees', [EmployeeApiController::class, 'index'])
           ->middleware('auth')
           ->middleware('permission', 'employee.view');
    ```
    위 예시에서 `/api/employees` API를 호출하기 위해서는 `employee.view` 라는 퍼미션이 필요합니다.

2.  **인증 확인 (`AuthMiddleware`)**: `auth` 미들웨어는 `AuthService`를 통해 사용자가 로그인 상태인지 먼저 확인합니다. 로그인되어 있지 않으면 요청은 거부됩니다.

3.  **권한 확인 (`PermissionMiddleware`)**: `permission` 미들웨어는 라우트에 지정된 퍼미션 키(예: 'employee.view')를 `AuthService`의 `check()` 메서드로 전달하여 현재 로그인한 사용자가 해당 권한을 가지고 있는지 검사합니다.

4.  **`AuthService`의 권한 검사 로직**:
    *   `AuthService`는 로그인 시 사용자의 역할(Role)과 그 역할에 연결된 모든 퍼미션(Permission)을 데이터베이스에서 조회합니다.
    *   조회된 퍼미션 키 목록(예: `['employee.view', 'employee.create', 'leave.request']`)을 세션에 저장합니다.
    *   `check()` 메서드는 `PermissionMiddleware`로부터 전달받은 퍼미션 키가 세션에 저장된 목록에 있는지 단순 확인하여 `true` 또는 `false`를 반환합니다.

5.  **결과 처리**: `PermissionMiddleware`는 `AuthService`로부터 `true`를 반환받으면 요청을 컨트롤러로 전달하고, `false`를 반환받으면 403 Forbidden 오류를 응답합니다.

### 2.2. 관련 데이터베이스 테이블

퍼미션 권한 시스템은 아래 테이블들을 통해 관리됩니다.

*   `sys_users`: 사용자 계정 정보.
*   `sys_roles`: 역할 정보 (예: 관리자, 팀장, 일반직원).
*   `sys_permissions`: 개별 권한 정보 (예: `employee.view` - 직원 조회, `employee.create` - 직원 생성).
*   `sys_user_roles` (Pivot): 사용자와 역할을 연결하는 테이블 (Many-to-Many).
*   `sys_role_permissions` (Pivot): 역할과 권한을 연결하는 테이블 (Many-to-Many).

## 3. 데이터 권한 (Data Authorization)

데이터 권한은 사용자가 특정 기능 내에서 접근할 수 있는 데이터의 범위를 제한합니다. 예를 들어, 같은 직원 목록 조회 API를 호출하더라도 사용자에 따라 다른 결과(자신이 속한 부서의 직원 목록만)를 받게 됩니다.

### 3.1. 핵심 원칙: Fail-Safe 설계

*   **중앙 관리**: 데이터 접근 범위(Data Scope)의 결정은 `DataScopeService`에서 중앙 집중적으로 관리됩니다. 이를 통해 권한 로직의 일관성을 유지하고 중복을 제거합니다.
*   **리포지토리 레벨 적용**: 데이터 스코프는 **리포지토리(Repository) 계층에서** SQL 쿼리에 직접 적용됩니다. 서비스나 컨트롤러 계층에서는 데이터 권한을 신경쓰지 않습니다.
*   **안전 우선(Fail-Safe)**: `DataScopeService`는 개발자의 실수가 발생하더라도 권한 없는 데이터가 노출되는 'fail-open' 상황을 방지하도록 설계되었습니다. 기본적으로 아무것도 보이지 않는 것이 원칙입니다.

### 3.2. 처리 흐름 (리팩토링 후)

1.  **서비스 로직**: 데이터 조회가 필요한 서비스는 필터 조건만을 리포지토리로 전달할 뿐, 권한에 대해서는 관여하지 않습니다.
    ```php
    // 예시: EmployeeService.php 내부
    public function getAllEmployees(array $filters = []): array
    {
        // 권한 관련 코드가 완전히 사라짐
        return $this->employeeRepository->getAll($filters);
    }
    ```

2.  **리포지토리의 스코프 적용**: 데이터를 조회하는 리포지토리 메서드는 `DataScopeService`의 스코프 적용 메서드를 호출하여 쿼리를 안전하게 수정합니다.
    ```php
    // 예시: EmployeeRepository.php 내부
    public function getAll(array $filters = []): array {
        $queryParts = [
            'sql' => "SELECT ... FROM hr_employees e ...",
            'params' => [],
            'where' => []
        ];

        // DataScopeService를 통해 현재 사용자의 권한에 맞는 WHERE 절이 자동으로 추가됨
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        // ... 나머지 필터 조건 추가 ...

        // 최종 쿼리 실행
        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
    ```

3.  **`DataScopeService`의 권한 계산 및 쿼리 수정**: `applyEmployeeScope`와 같은 스코프 메서드는 내부적으로 `getVisibleDepartmentIdsForCurrentUser`를 호출하여 권한을 계산하고, 그 결과를 바탕으로 쿼리 구성 배열(`$queryParts`)을 직접 수정하여 반환합니다.
    *   **전체 조회 권한이 있는 경우**: 쿼리를 변경하지 않고 그대로 반환합니다.
    *   **조회 가능한 부서가 있는 경우**: `WHERE e.department_id IN (...)` 조건을 `where` 배열에 추가합니다.
    *   **조회 가능한 부서가 없는 경우**: `WHERE 1=0` 조건을 추가하여 어떤 결과도 반환되지 않도록 합니다. **이것이 바로 Fail-Safe의 핵심입니다.**

### 3.3. 관련 데이터베이스 테이블

데이터 권한 시스템은 주로 아래 테이블들을 통해 관리됩니다.

*   `hr_employees`: 직원 정보 (특히 `department_id` 컬럼이 중요).
*   `hr_departments`: 조직의 부서 정보 (부모-자식 관계 포함).
*   `hr_department_managers`: 특정 직원이 특정 부서의 데이터를 조회/관리할 수 있는 권한을 직접 부여하는 테이블.
*   `hr_department_view_permissions`: 특정 부서가 다른 부서의 데이터를 조회할 수 있는 권한을 부여하는 테이블.

## 4. 종합 예시: 직원 목록 조회 (`GET /api/employees`)

A팀 팀장 '김팀장'이 직원 목록을 조회하는 상황을 통해 전체 권한 시스템의 동작을 순서대로 살펴보겠습니다.

**사용자 시나리오:**
*   **사용자**: 김팀장 (A팀 소속)
*   **역할**: 팀장
*   **퍼미션**: `employee.view` (팀장 역할에 부여됨)
*   **데이터 권한**: 소속된 A팀 및 A팀의 하위 부서인 A-1팀의 데이터만 조회 가능

---

**처리 흐름 다이어그램 (텍스트)**

```
[클라이언트] -> [라우터] -> [미들웨어] -> [EmployeeApiController]
                                             |
                                             V
                                     [EmployeeService]
                                     - getAllEmployees() 호출
                                             |
                                             V
                                     [EmployeeRepository]
                                     - getAll() 내부에서 DataScopeService 호출
                                             |
                                             V
                                     [DataScopeService]
                                     - applyEmployeeScope() 실행
                                     - 권한 계산 후 WHERE 절 추가
                                             |
                                             V
[클라이언트] <- [JSON 응답] <- [데이터베이스] <- [EmployeeRepository]
                                              - 최종 SQL 실행
```

**단계별 설명:**

1.  **요청 및 퍼미션 확인**: 이전과 동일하게 라우터와 미들웨어를 거쳐 `employee.view` 퍼미션이 있는지 확인됩니다.

2.  **서비스 호출**: `EmployeeApiController`는 `EmployeeService`의 `getAllEmployees`를 호출합니다. 이 때 서비스는 권한에 대해 알지 못합니다.

3.  **리포지토리의 데이터 스코핑**:
    *   `EmployeeService`는 `EmployeeRepository`의 `getAll`을 호출합니다.
    *   `EmployeeRepository`는 가장 먼저 `DataScopeService`의 `applyEmployeeScope`를 호출합니다.
    *   `DataScopeService`는 '김팀장'이 조회 가능한 부서가 A팀과 A-1팀임을 계산하고, `WHERE e.department_id IN (A팀_ID, A-1팀_ID)` 라는 SQL 조건을 생성하여 반환합니다.

4.  **데이터베이스 조회 및 응답**:
    *   `EmployeeRepository`는 스코프가 적용된 쿼리를 최종적으로 실행하여 A팀과 A-1팀 소속 직원 정보만 데이터베이스에서 가져옵니다.
    *   이 데이터가 서비스와 컨트롤러를 거쳐 클라이언트에게 JSON 형태로 응답됩니다.
