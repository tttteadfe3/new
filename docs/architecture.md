# 애플리케이션 아키텍처 가이드

## 1. 개요

이 문서는 애플리케이션의 아키텍처, 코딩 표준, 데이터베이스 설계 및 개발 프로세스 전반에 대한 지침을 제공합니다. 모든 개발자는 이 가이드를 숙지하고 준수하여 코드의 일관성과 품질을 유지해야 합니다.

-   **프로젝트 목적**: HR 및 조직 데이터 관리에 중점을 둔 ERP 스타일의 웹 애플리케이션
-   **기술 스택**: PHP, MariaDB, Nginx, Bootstrap CSS

## 2. 아키텍처 (Architecture)

### 2.1. MVC-S (Model-View-Controller-Service) 패턴

애플리케이션은 서비스 계층(Service Layer)이 추가된 MVC 패턴을 따릅니다.

-   **요청 흐름**: `Route` → `Controller` → `Service` → `Repository` → `Database`
-   **Thin Controller 원칙**: 컨트롤러는 HTTP 요청/응답 처리 및 데이터 유효성 검증에 집중하며, 모든 비즈니스 로직은 서비스 계층에 위임합니다. 컨트롤러 내에서 직접 권한을 확인하거나 복잡한 데이터 처리 로직을 포함하지 않습니다.

### 2.2. 디렉토리 구조

-   **컨트롤러**:
    -   페이지 렌더링: `app/Controllers/Web/`
    -   데이터 API: `app/Controllers/Api/`
-   **뷰**:
    -   페이지: `app/Views/pages/` (예: `pages.admin.roles` → `app/Views/pages/admin/roles.php`)
    -   오류 페이지: `app/Views/errors/`
    -   레이아웃 및 공용 컴포넌트: `app/Views/layouts/`
-   **프론트엔드 에셋**:
    -   JavaScript: `public/assets/js/` (하위에 `pages`, `components`, `services` 등으로 구조화)
    -   CSS: `public/assets/css/` (하위에 `pages` 등으로 구조화)
-   **라우팅**:
    -   웹: `routes/web.php`
    -   API: `routes/api.php`

### 2.3. 의존성 주입 (Dependency Injection)

-   DI 컨테이너는 `public/index.php`에서 설정 및 관리됩니다.
-   **주의**: 클래스 생성자 시그니처가 변경될 경우, DI 컨테이너에 등록된 정보도 반드시 함께 수정해야 `too few arguments`와 같은 치명적인 오류를 방지할 수 있습니다.

### 2.4. 인증 및 권한 (Authentication & Authorization)

**역할 분담: 인증(Middleware) vs. 권한(Service Layer)**

이 시스템은 인증과 권한 처리를 명확하게 분리하여 관리합니다.

-   **인증 (Authentication)**: **미들웨어 계층**에서 처리합니다.
    -   `AuthMiddleware`는 사용자가 **로그인했는지 여부**를 확인하는 책임만 가집니다. 라우트(Route) 수준에서 접근을 제어하며, 인증되지 않은 사용자는 로그인 페이지로 리디렉션합니다.

-   **권한 (Authorization)**: **서비스 계층**에서 처리합니다.
    -   로그인한 사용자가 **특정 작업을 수행할 자격이 있는지 여부**(예: '직원 삭제')는 `AuthService`를 통해 각 비즈니스 로직 내에서 직접 확인합니다. 이 방식은 특정 데이터의 맥락(예: '자신이 속한 팀의 직원 정보만 수정 가능')을 고려해야 하는 복잡한 권한 규칙을 유연하게 적용하는 데 유리합니다.

**세부 구현:**

-   **인증**:
    -   `AuthMiddleware`를 사용하여 인증되지 않은 사용자의 라우트 접근을 `/login`으로 리디렉션합니다.
    -   `AuthService->checkAccess()`: 모든 페이지 요청 시 사용자의 현재 상태(활성, 보류, 차단)를 실시간으로 DB에서 확인합니다.
    -   OAuth: Kakao OAuth 로그인은 `AuthController`의 `kakaoCallback` 메소드에서 처리하며, CSRF 보호를 위해 `state` 파라미터를 사용합니다.
-   **권한**:
    -   **2단계 프로세스**: 기능 권한(Permission) 확인 후 데이터 범위(Data Scope)를 적용합니다.
    -   **`AuthService`**: `check(string $permission_key)` 메소드를 통해 사용자의 기능 권한(`resource.action` 형식, 예: `employee.create`)을 중앙에서 관리합니다. 권한은 세션에 캐시되어 성능을 최적화합니다.
    -   **`DataScopeFilter`**: 사용자의 역할과 부서에 따라 접근 가능한 데이터 범위를 결정하고, 이를 SQL `WHERE` 조건으로 생성합니다.
    -   **예외 규칙**:
        -   `employee.manage` 권한을 가진 사용자는 모든 데이터 스코프 필터링을 우회하여 모든 직원 정보를 조회할 수 있습니다.
        -   `무단투기(Littering)`, `대형폐기물(Waste Collection)` 모듈은 현재의 권한 시스템에서 제외되며, 기존의 레거시 권한 로직을 유지합니다.

## 3. 문서 (Documentation)

-   **`README.md`**: 프로젝트의 개요와 주요 정보를 담고 있으며, `backend-guide.md`, `frontend-guide.md` 등 더 상세한 기술 문서로 연결되는 중앙 허브 역할을 합니다.
-   **`AGENTS.md`**: 코딩 규칙 등 개발 에이전트를 위한 추가적인 지침을 포함할 수 있습니다.
