# 코드 작성 규칙 (Coding Conventions)

> ℹ️ **참고**
> 이 문서는 프로젝트의 일반적인 코딩 스타일 가이드를 정의합니다.
> 실제 컴포넌트별 구현 패턴과 "Do & Don't" 규칙은 **[신규 개발자 필독 가이드](./DEVELOPER_GUIDE.md)**에 더 상세히 설명되어 있습니다.

## 1. 개요 (Overview)

이 문서는 프로젝트의 코드 스타일, 아키텍처 패턴, 네이밍 규칙 등을 정의하여 코드의 일관성과 가독성을 높이고 유지보수를 용이하게 하는 것을 목적으로 합니다. 모든 개발자는 이 규칙을 숙지하고 준수해야 합니다.

## 2. 네이밍 컨벤션 (Naming Conventions)

일관된 네이밍은 코드의 가독성을 크게 향상시킵니다.

-   **PHP 변수**: `camelCase` (예: `$employeeList`)
-   **PHP 메소드**: `camelCase` (예: `getEmployeeDetails()`)
-   **PHP 클래스**: `PascalCase` (예: `EmployeeService`, `AuthController`)
-   **PHP 네임스페이스**: `PascalCase`, PSR-4 표준을 따릅니다. (예: `App\Services`, `App\Controllers\Api`)
-   **데이터베이스 테이블**: `snake_case`, 복수형 (예: `employees`, `departments`)
-   **데이터베이스 컬럼**: `snake_case` (예: `employee_id`, `hire_date`)
-   **JavaScript 변수/함수**: `camelCase` (예: `employeeList`, `loadInitialData()`)
-   **JavaScript 클래스**: `PascalCase` (예: `class EmployeesPage extends BasePage`)
-   **CSS 클래스**: `kebab-case` (예: `.employee-list-container`)

## 3. 코드 포맷팅 (Code Formatting)

-   **들여쓰기**: 스페이스 4칸을 사용합니다. 탭(Tab) 문자는 사용하지 않습니다.
-   **줄 길이**: 한 줄은 가급적 120자를 넘지 않도록 합니다.
-   **괄호**: 클래스와 메소드의 여는 괄호(`{`)는 선언부와 같은 줄에 위치시키고, 닫는 괄호(`}`)는 새로운 줄에 위치시킵니다.
-   **PSR-12**: PHP 코드는 기본적으로 PSR-12 코딩 스타일 가이드를 따르는 것을 권장합니다.

## 4. 디렉토리 구조 (Directory Structure)

-   `app/Controllers`: HTTP 요청을 처리하는 컨트롤러. `Api`와 `Web`으로 네임스페이스가 구분됩니다.
-   `app/Services`: 비즈니스 로직을 처리하는 서비스 클래스.
-   `app/Repositories`: 데이터베이스 상호작용을 담당하는 리포지토리 클래스.
-   `app/Core`: 라우터, DI 컨테이너, 데이터베이스 연결 등 프레임워크의 핵심 기능.
-   `app/Views`: 사용자에게 보여질 HTML 템플릿.
-   `config`: 애플리케이션 설정 파일.
-   `public`: 웹 서버의 Document Root. `index.php`, CSS, JavaScript, 이미지 등 웹 자산을 포함.
-   `routes`: `web.php`와 `api.php`를 통해 웹 및 API 라우트를 정의.
-   `storage`: 로그, 캐시 등 임시 파일 저장.

## 5. PHP 컴포넌트별 작성 규칙 (PHP Component-specific Rules)

### 5.1. 컨트롤러 (Controllers)

-   **책임**: 컨트롤러는 HTTP 요청을 받고, 적절한 서비스 메소드를 호출하며, 결과를 View 또는 JSON 형태로 반환하는 역할만 수행합니다. **비즈니스 로직을 포함해서는 안 됩니다.**
-   **의존성 주입**: 필요한 서비스나 리포지토리는 반드시 생성자 주입(Constructor Injection)을 통해 전달받습니다.
-   **입력값 처리**: 사용자 입력은 `Request` 객체를 통해 받습니다.

### 5.2. 서비스 (Services)

-   **책임**: 핵심 비즈니스 로직을 담당합니다. 여러 리포지토리를 조합하여 하나의 작업 단위를 처리하고, 필요한 경우 트랜잭션을 관리합니다.
-   **상태 비저장**: 서비스는 상태를 가지지 않아야 합니다(Stateless).

### 5.3. 리포지토리 (Repositories)

-   **책임**: 특정 데이터베이스 테이블에 대한 CRUD(Create, Read, Update, Delete) 작업을 담당합니다. 복잡한 SQL 쿼리는 리포지토리 메소드 내에 캡슐화합니다.
-   **데이터베이스 의존성**: `Database` 객체는 생성자를 통해 주입받습니다.

## 6. JavaScript 작성 규칙 (JavaScript Coding Rules)

-   **ES6+ 사용**: `var` 대신 `const`와 `let`을 사용하고, 클래스, 화살표 함수, `async/await` 등 모던 JavaScript 문법을 적극적으로 사용합니다.
-   **모듈화**: 기능별로 JavaScript 코드를 클래스 또는 모듈로 분리합니다. 이 프로젝트에서는 페이지별로 `class EmployeesPage extends BasePage`와 같이 클래스를 정의하는 패턴을 사용합니다.
-   **DOM 조작**: 가급적 순수 JavaScript(`document.getElementById`, `addEventListener`)를 사용합니다. jQuery는 레거시 코드 유지보수 외에는 사용을 지양합니다.
-   **API 통신**: `async/await`와 함께 `fetch` API 또는 이 프로젝트의 `apiCall`과 같은 중앙화된 HTTP 통신 함수를 사용합니다.
-   **보안**: 사용자 입력을 HTML에 렌더링할 때는 반드시 `sanitizeHTML`과 같은 함수를 거쳐 XSS(Cross-Site Scripting) 공격을 방지해야 합니다.

## 7. API 엔드포인트 설계 (API Endpoint Design)

-   **RESTful 원칙**: API는 가급적 RESTful 원칙을 따릅니다.
    -   자원(Resource)은 URI로 표현합니다. (예: `/employees`, `/employees/123`)
    -   자원에 대한 행위(Verb)는 HTTP 메소드(GET, POST, PUT, DELETE)로 표현합니다.
-   **URI**: 소문자를 사용하고, 단어는 하이픈(`-`)으로 연결합니다. (예: `/employee-history`)
-   **응답 형식**: JSON을 기본 응답 형식으로 사용하며, 일관된 응답 구조를 가집니다.

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": { ... }
}
```

## 8. 에러 처리 (Error Handling)

-   PHP에서는 예외(Exception)를 사용하여 에러 상황을 전파합니다. `try-catch` 블록을 사용하여 예외를 처리합니다.
-   치명적인 오류는 `config/config.php`에 설정된 전역 예외 처리기(set_exception_handler)에 의해 처리되며, 개발 환경에서는 상세 오류를, 운영 환경에서는 표준 에러 페이지를 보여줍니다.
-   API 요청 실패 시에는 명확한 에러 메시지와 적절한 HTTP 상태 코드(400, 401, 403, 404, 500 등)를 반환해야 합니다.

## 9. 주석 및 테스트 코드 작성법 (Commenting & Test Code Style)

-   **주석**: 복잡한 로직이나 다른 개발자가 이해하기 어려운 코드에는 반드시 주석을 작성합니다.
-   **PHPDoc**: 모든 클래스와 공개(public) 메소드에는 PHPDoc 형식의 주석 블록을 작성하여 파라미터, 반환값, 역할을 명확히 설명합니다.
-   **테스트 코드**: (현재 프로젝트에는 없지만) 향후 테스트 코드 작성 시, 메소드명은 `test_`로 시작하고 테스트하는 동작을 명확하게 서술합니다.