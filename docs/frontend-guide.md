# JavaScript 개발 가이드

이 문서는 프로젝트의 JavaScript 코드 구조와 규칙을 정의하여 일관성을 유지하고 협업을 용이하게 하는 것을 목표로 합니다.

## 1. 아키텍처 및 디렉토리 구조

모든 JavaScript 모듈은 `public/assets/js/` 디렉토리 아래에 위치하며, 각 디렉토리는 다음과 같은 명확한 역할을 가집니다.

-   **`core/`**: 애플리케이션의 핵심 기반 클래스를 포함합니다.
    -   `base-page.js`: 모든 페이지 클래스가 상속받는 최상위 부모 클래스. 공통 상태 관리, API 서비스 접근, 생명주기 메서드 등을 제공합니다.
-   **`pages/`**: 각 페이지의 고유한 로직과 진입점(entry point) 역할을 하는 스크립트를 포함합니다.
-   **`services/`**: UI가 없는 공유 기능을 담당하는 서비스 모듈입니다.
    -   `api-service.js`: 모든 서버 API 통신을 담당하는 중앙 집중형 서비스입니다.
-   **`components/`**: 여러 페이지에서 재사용 가능한 UI 컴포넌트입니다. (예: `custom-chart.js`)
-   **`utils/`**: 상태에 의존하지 않는 순수 유틸리티 함수 모음입니다. (예: `dom-helpers.js`)

## 2. `BasePage` 클래스 심층 분석

`BasePage`는 모든 페이지 클래스의 기반이며, 공통적인 로직을 추상화하여 코드 중복을 최소화합니다.

### 2.1. 생명주기 (Lifecycle)

페이지 스크립트가 로드되면 `BasePage`의 생성자(`constructor`)는 다음 순서대로 핵심 메서드를 호출합니다.

1.  **`constructor(config)`**:
    -   `this.state = {}`와 `this.config = {}`를 초기화합니다.
    -   자식 클래스에서 전달된 `config` 객체를 `this.config`에 병합합니다.
    -   `this.initializeApp()`을 호출하여 초기화 프로세스를 시작합니다.

2.  **`initializeApp()`**:
    -   **`setupEventListeners()`**: 자식 클래스에서 오버라이드하여 페이지의 모든 이벤트 리스너를 바인딩합니다.
    -   **`loadInitialData()`**: 자식 클래스에서 오버라이드하여 페이지 로드 시 필요한 초기 데이터를 불러옵니다.

> **중요**: 이벤트 리스너 설정이나 초기 데이터 로딩 로직은 `constructor`에 직접 작성하지 말고, 반드시 `setupEventListeners`와 `loadInitialData` 메서드에 작성해야 합니다.

### 2.2. 상태 관리 (State Management)

-   **`this.state`**: 페이지 내에서 동적으로 변경되는 모든 데이터는 `this.state` 객체에 저장해야 합니다.
-   **데이터 변경**: 데이터를 변경할 때는 `this.state`의 속성을 직접 수정합니다. (예: `this.state.employees = [...]`)
-   **UI 업데이트**: 데이터 변경 후에는 반드시 `this.render()`와 같은 UI 업데이트 메서드를 호출하여 화면에 변경 사항을 반영해야 합니다.

### 2.3. API 통신 (`apiCall`)

`BasePage`는 `ApiService`의 `apiCall` 메서드를 래핑하여 제공합니다. 이를 통해 일관된 방식으로 API를 호출하고 에러를 처리할 수 있습니다.

-   **사용법**: `this.apiCall(endpoint, method, body)`
-   **`endpoint`**: API 엔드포인트 URL (예: `/api/v1/employees`)
-   **`method`**: HTTP 메서드 (`'GET'`, `'POST'`, `'PUT'`, `'DELETE'`)
-   **`body`**: 요청 본문 (주로 `POST` 또는 `PUT` 요청 시 사용)

## 3. 새 페이지 스크립트 작성 절차

새로운 페이지 스크립트를 추가하는 전체 과정은 다음과 같습니다.

1.  **파일 생성**: `public/assets/js/pages/feature-name.js` 형식으로 파일을 생성합니다.

2.  **클래스 작성**: `BasePage`를 상속받아 페이지별 로직을 구현합니다. 아래는 '직원 관리' 페이지의 예시입니다.

    ```javascript
    // public/assets/js/pages/employee-management.js

    class EmployeeManagementPage extends BasePage {
        constructor() {
            super({ /* 페이지별 설정 */ });
            this.state = { employees: [], isLoading: true };
        }

        setupEventListeners() {
            // 이벤트 리스너 설정
        }

        loadInitialData() {
            this.fetchEmployees();
        }

        async fetchEmployees() {
            // API 호출 및 상태 업데이트
        }

        render() {
            // this.state를 기반으로 UI 렌더링
        }
    }

    // 페이지 스크립트 실행
    new EmployeeManagementPage();
    ```

3.  **컨트롤러에 스크립트 등록**: 해당 페이지를 렌더링하는 PHP 컨트롤러에서 `View::addJs()`를 사용해 스크립트를 추가합니다.

    ```php
    // app/Controllers/Web/EmployeeController.php
    View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/core/base-page.js');
    View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employee-management.js');
    ```

## 4. CSS & View 가이드

-   **네이밍**: 파일명은 `kebab-case.css`, `kebab-case.php`를 따릅니다.
-   **스타일링**: 인라인 CSS나 `<style>` 태그 사용을 금지합니다. 모든 페이지별 스타일은 `public/assets/css/pages/` 디렉토리의 개별 CSS 파일로 분리해야 합니다.
