# 신규 개발자 필독 가이드 (Developer Guide)

이 문서는 우리 프로젝트의 신규 입사자가 빠르게 적응하고, 팀의 개발 규칙을 일관되게 따를 수 있도록 돕는 실용적인 가이드입니다. **모든 개발자는 기능 개발 전 반드시 이 문서를 숙지해야 합니다.**

---

## 1. [가장 먼저 필독] 우리 팀의 핵심 개발 원칙과 정책

이 섹션에서는 우리 프로젝트의 안정성과 보안을 유지하는 가장 중요한 정책과 원칙을 설명합니다. 이 규칙들을 이해하고 지키는 것은 선택이 아닌 **필수**입니다.

### 1.1. 권한 및 데이터 접근 정책 (매우 중요)

#### 정책 1: 롤/퍼미션(Role/Permission) 기반 접근 제어

우리 시스템의 모든 기능 접근은 **사전에 정의된 권한(Permission)**을 기반으로 제어됩니다.

**구현 방법:** 모든 라우트(Route)에는 반드시 `PermissionMiddleware`를 통해 필요한 권한을 명시해야 합니다. `AuthService::check($permission)`를 통해 권한을 검사하며, 권한이 없으면 403 Forbidden 페이지로 리다이렉트됩니다.

**적용 예시:**

새로운 관리자 페이지를 추가할 경우, `routes/web.php`에 다음과 같이 메소드 체이닝(Method Chaining) 방식으로 미들웨어를 설정해야 합니다.

```php
// routes/web.php

// 'user.view' 권한이 있는 사용자만 접근할 수 있도록 미들웨어를 추가합니다.
$router->get('/admin/users', [AdminController::class, 'users'])
       ->name('admin.users')
       ->middleware('auth')
       ->middleware('permission', 'user.view'); // <--- 여기!
```
**⚠️ 위험성:** 권한 체크를 누락하면 일반 사용자가 URL 직접 입력을 통해 관리자 페이지에 접근하는 심각한 보안 사고로 이어질 수 있습니다.

#### 정책 2: 부서 데이터 접근 범위(Data Scope) 규칙

우리 시스템은 **"사용자는 자신의 부서 및 허가된 하위 부서의 데이터만 조회/수정할 수 있다"**는 엄격한 데이터 격리 정책을 따릅니다. 이 규칙은 `DataScopeService`를 통해 중앙에서 관리됩니다.

**구현 방법:** 데이터베이스에서 데이터를 조회하는 모든 리포지토리 메소드는 `DataScopeService::getVisibleDepartmentIdsForCurrentUser()`가 제공하는 '조회 가능한 부서 ID 목록'을 `WHERE` 조건에 반드시 포함해야 합니다.

**올바른 코드 예시:**
```php
// EmployeeRepository.php
public function getAll(array $filters = [], ?array $visibleDeptIds = null)
{
    // ...
    if ($visibleDeptIds !== null) {
        // ...
        $whereClauses[] = "e.department_id IN ($inClause)";
        $params = array_merge($params, $visibleDeptIds);
    }
    // ...
}
```
**⚠️ 위험성:** 데이터 스코프를 무시하면 일반 사용자가 다른 부서의 민감한 개인정보를 조회할 수 있는 심각한 데이터 유출 사고가 발생할 수 있습니다.

#### 퍼미션(Permission) 이름 규칙
새로운 권한을 추가할 때는 **`{리소스}.{행위}`** 형식을 반드시 따릅니다.
- **리소스(Resource)**: `employee`, `user`, `leave` 등 기능의 대상 (단수형)
- **행위(Action)**: `view`, `create`, `update`, `delete`, `manage` 등 동사
- **좋은 예시**: `employee.view`, `user.create`, `organization.manage`
- **나쁜 예시**: `view-employee`, `createUser`, `manage_organization`

### 1.2. 일반 개발 원칙
-   **단일 책임 원칙 (SRP)**: 클래스/메소드는 하나의 책임만 가집니다 (Controller: 요청/응답, Service: 비즈니스 로직, Repository: DB 통신).
-   **코드 재사용성**: 중복 코드는 `helper` 함수나 `Service` 메소드로 분리합니다.

---

## 2. 주요 컴포넌트별 "해야 할 것(Do)"과 "하지 말아야 할 것(Don't)"

### 2.1. 컨트롤러 (Controller)
컨트롤러는 **교통 경찰**입니다. 요청을 받아 서비스에 전달하고, 그 결과를 사용자에게 보여주는 역할만 합니다.
-   **Do ✅**: `Request` 객체로 입력을 받고, `Service`를 호출하고, `View`나 `JSON`으로 응답합니다.
-   **Don't ❌**: 직접 SQL 쿼리를 작성하거나 복잡한 비즈니스 로직을 구현하지 않습니다.

### 2.2. 서비스 (Service)
서비스는 프로젝트의 **두뇌**입니다. 여러 기능을 조합하여 하나의 완전한 비즈니스 로직을 완성합니다.
-   **Do ✅**: 여러 리포지토리를 호출하여 데이터를 가공하고, 트랜잭션을 관리하며, 비즈니스 규칙을 검증합니다.
-   **Don't ❌**: 다른 서비스를 직접 호출하는 것을 가급적 지양하고, `$_POST`와 같은 전역 변수를 직접 참조하지 않습니다.

### 2.3. 리포지토리 (Repository)
리포지토리는 **데이터베이스 창고 관리자**입니다. DB와의 모든 상호작용은 리포지토리를 통해서만 이루어져야 합니다.
-   **Do ✅**: 특정 테이블에 대한 CRUD SQL 쿼리만을 담당하고, 복잡한 `JOIN`을 캡슐화합니다.
-   **Don't ❌**: 비즈니스 로직을 포함하지 않고, 데이터의 주체(Main Entity)가 되는 리포지토리에서만 `JOIN`을 처리합니다.

---

## 3. [사례 중심] 기능 개발 워크플로우 (Workflow by Example)

### 3.1. "신규 직원 등록" 기능 A to Z
1.  **라우트 정의 (`routes/api.php`)**: `POST /api/employees` 엔드포인트를 `EmployeeApiController::store`에 연결하고 `employee.create` 권한 미들웨어를 설정합니다.
2.  **컨트롤러 구현 (`EmployeeApiController`)**: 요청(`Request`)에서 데이터를 받아 `EmployeeService`로 전달하고, 결과를 `JSON`으로 응답합니다.
3.  **서비스 구현 (`EmployeeService`)**: 데이터 유효성을 검사하고 `EmployeeRepository`를 호출하여 DB에 저장합니다.
4.  **리포지토리 구현 (`EmployeeRepository`)**: `INSERT` 쿼리를 실행하여 데이터를 저장합니다.
5.  **프론트엔드 구현 (`employees.js`)**: 폼 제출 시 `apiCall` 메소드를 사용해 API를 호출하고, 성공 시 UI를 갱신합니다.

---

## 4. 프론트엔드 개발 규칙 (Frontend Rules)

### 4.1. 모든 페이지는 `BasePage`를 상속해야 합니다
모든 페이지 JavaScript는 `BasePage` 클래스를 상속(extend)하여 페이지 생명주기를 표준화하고 공통 기능을 재사용해야 합니다.
```javascript
class MyNewPage extends BasePage {
    constructor() { super(); }
    initializeApp() { /* 초기화 로직 */ }
}
new MyNewPage();
```

### 4.2. API 호출은 `this.apiCall`을 사용해야 합니다
`BasePage`의 `apiCall` 메소드는 에러 핸들링 등을 자동으로 수행하므로, 모든 서버 API 요청은 반드시 이 메소드를 통해 이루어져야 합니다.
```javascript
const response = await this.apiCall('/some-data');
```

### 4.3. 공통 UI 유틸리티를 사용해야 합니다
`Toast` 알림, `Confirm` 확인창 등은 `public/assets/js/utils/ui-helpers.js`에 미리 만들어진 함수를 사용해야 합니다.
- **알림**: `Toast.success('성공했습니다.')`, `Toast.error('실패했습니다.')`
- **확인창**: `const result = await Confirm.fire({ title: '정말 삭제하시겠습니까?' });`

---

## 5. [사례 중심] 연차 관리 시스템 개발 가이드 (Leave Management System)

연차 관리 시스템은 복잡한 비즈니스 규칙과 중요한 데이터를 다루므로, 아래 가이드를 반드시 따릅니다.

### 5.1. 아키텍처: "로그 + 잔여일수" 하이브리드 모델

-   **`hr_leave_logs`**: **Source of Truth**. 연차 부여, 사용, 소멸 등 모든 변경 이력은 이 테이블에만 기록됩니다. 데이터는 절대 수정되거나 삭제되지 않습니다.
-   **`hr_leave_balances`**: **읽기용 집계 데이터 (Aggregated Data for Read)**. 사용자의 현재 연차 상태(총 연차, 사용 연차, 잔여 연차)를 빠르게 조회하기 위한 집계 테이블입니다. `logs` 테이블에 변경이 생길 때마다 트랜잭션 안에서 함께 업데이트되어야 합니다.
-   **`hr_leave_requests`**: **신청/승인 워크플로우 관리**. 연차 신청서의 상태(대기, 승인, 반려)를 관리합니다.

이 구조는 데이터의 정합성을 보장하면서도, 복잡한 계산 없이 빠른 조회가 가능하도록 합니다.

### 5.2. 핵심 연차 계산 규칙 (반드시 숙지)

#### 신규 입사자 연차 부여 (매우 복잡)
신규 입사자의 최초 2년간의 연차는 다음 규칙에 따라 시스템에서 자동으로 계산 및 부여됩니다.

1.  **월차 (입사 첫 해)**:
    -   **부여 시점**: 입사 직후
    -   **계산**: `입사일로부터 첫 1년이 되기까지 남은 월 수` 만큼을 미리 부여합니다.
    -   **예시**: 7월 1일 입사자는 1년이 되는 내년 6월 30일까지 11개월이 남았으므로, 입사 시점에 **11일**의 '월차'가 부여됩니다.

2.  **2년차 비례 연차 (Prorated Annual Leave)**:
    -   **부여 시점**: 입사 다음 해 1월 1일
    -   **계산**: `(입사 해 근무일수 / 입사 해 전체일수) * 15일` (소수점 둘째 자리에서 반올림)
    -   **예시**: 2024년 7월 1일 입사자는 2025년 1월 1일에 `(184 / 366) * 15 = 7.5`일의 '비례 연차'를 부여받습니다.

#### 기존 직원 연차 부여
-   **기본 연차**: 매년 1월 1일, 15일 부여
-   **근속 연차**:
    - **시작**: 만 3년 근속이 되는 해 1월 1일에 첫 근속 연차(+1일) 부여
    - **주기**: 이후 2년마다 1일씩 추가
    - **한도**: 기본 연차와 합쳐 최대 25일까지

### 5.3. 주요 기능 구현 패턴

#### 배치 작업: "계산 → 확인 → 실행" 워크플로우
연차 일괄 부여/소멸과 같이 다수 직원에게 영향을 미치는 기능은 반드시 다음 3단계 워크플로우를 따라야 합니다.
1.  **계산 (Calculate)**: 사용자가 조건을 선택하고 '계산하기'를 누르면, 서버는 실제 DB 변경 없이 예상 결과만 계산하여 반환합니다. (`LeaveAdminApiController::previewGrantAnnualLeave`)
2.  **확인 (Confirm)**: 프론트엔드는 계산 결과를 모달(Modal) 창에 표시하여 사용자가 변경 내용을 명확히 인지하고 확인할 수 있게 합니다.
3.  **실행 (Execute)**: 사용자가 모달 창에서 '최종 실행' 버튼을 눌렀을 때만 실제 DB에 변경을 가하는 API를 호출합니다. (`LeaveAdminApiController::grantAnnualLeave`)

이 패턴은 운영상 실수를 방지하는 핵심적인 안전장치입니다.

#### API 엔드포인트 설계
-   **Admin API**: 관리자 기능은 `/api/admin/` 접두사를 사용하고 `leave.manage_entitlement` 와 같이 명확한 권한으로 보호해야 합니다.
-   **User API**: 일반 사용자 기능은 `/api/` 접두사를 사용합니다.

### 5.4. 자주 발생하는 실수 및 해결 방법 (Common Pitfalls)

-   **DB 조회 메소드 오류**: `Database` 클래스 사용 시, 여러 행 조회는 `fetch()`, 단일 값/행 조회는 `fetchOne()`을 명확히 구분하여 사용하세요. 혼용 시 Fatal Error가 발생합니다.
-   **`DataScopeService` 오남용**: `DataScopeService`는 현재 로그인한 유저의 권한에 따른 **가시성 범위**만 제공하는 역할입니다. 특정 페이지의 필터링 로직을 위해 이 서비스를 수정해서는 안 됩니다. 각 컨트롤러나 서비스에서 필요한 데이터를 필터링하세요.
-   **프론트엔드 `apiCall` 사용법**:
    -   `GET` 요청 시 파라미터는 URL에 직접 쿼리 문자열로 만들어야 합니다. (`/api/users?department_id=1`)
    -   `POST`, `PUT` 요청 시 데이터는 두 번째 인자로 객체 형태로 전달해야 합니다. (`this.apiCall('/api/users', { name: 'John' }, 'POST')`)
    -   `BasePage.apiCall`을 직접 수정하지 마세요.
