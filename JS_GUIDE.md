# JavaScript 아키텍처 가이드

이 문서는 프로젝트의 JavaScript 코드 구조와 규칙을 정의하여 일관성을 유지하고 협업을 용이하게 하는 것을 목표로 합니다.

## 1. 디렉토리 구조

모든 JavaScript 모듈은 `public/assets/js/` 디렉토리 아래에 위치하며, 각 디렉토리는 다음과 같은 명확한 역할을 가집니다.

- **`core/`**: 애플리케이션의 핵심 기반 클래스를 포함합니다.
  - `base-page.js`: 모든 페이지 클래스가 상속받는 최상위 부모 클래스입니다. 공통 상태 관리, API 서비스 접근, 기본 생명주기 메서드 등을 제공합니다.

- **`pages/`**: 각 페이지의 고유한 로직과 진입점(entry point) 역할을 하는 스크립트를 포함합니다.
  - 파일은 특정 페이지의 기능을 모두 담고 있는 클래스를 정의하고, 마지막에 인스턴스화(`new ...Page()`)하여 실행합니다.
  - 예: `menu-admin.js`, `profile.js`

- **`components/`**: 재사용 가능한 UI 컴포넌트를 포함합니다.
  - 특정 페이지에 종속되지 않고, 여러 곳에서 사용될 수 있는 시각적 요소를 관리합니다.
  - 예: `interactive-map.js`, `custom-chart.js`

- **`services/`**: UI가 없는 공유 기능을 담당하는 서비스 모듈을 포함합니다.
  - API 통신, 상태 관리, 지도 로직 등 백그라운드에서 동작하는 기능들을 클래스로 정의합니다.
  - 예: `api-service.js`, `map-service.js`

- **`utils/`**: 상태에 의존하지 않는 순수 유틸리티 함수들을 포함합니다.
  - DOM 조작, 날짜 포맷팅 등 간단하고 재사용 가능한 함수들의 모음입니다.
  - 예: `dom-helpers.js`

## 2. 명명 규칙

코드의 가독성과 예측 가능성을 높이기 위해 다음 명명 규칙을 엄격히 준수합니다.

- **파일 이름**: `kebab-case.js`
  - 단어는 모두 소문자로 작성하고, 하이픈(`-`)으로 연결합니다.
  - 예: `menu-admin.js`, `api-service.js`, `base-page.js`

- **클래스 이름**: `PascalCase`
  - 각 단어의 첫 글자를 대문자로 시작하며, 단어를 모두 붙여 씁니다.
  - **`pages/`** 디렉토리의 클래스는 이름 끝에 `Page`를 붙여 역할을 명확히 합니다.
  - 예: `MenuAdminPage`, `ApiService`, `BasePage`, `InteractiveMap`

## 3. 새 페이지 스크립트 작성 예시

새로운 '직원 관리' 페이지를 위한 스크립트를 추가하는 과정은 다음과 같습니다.

1.  **파일 생성**: `public/assets/js/pages/employee-management.js` 파일을 생성합니다.

2.  **클래스 작성**: `BasePage`를 상속받는 `EmployeeManagementPage` 클래스를 작성합니다.

    ```javascript
    // public/assets/js/pages/employee-management.js

    class EmployeeManagementPage extends BasePage {
        constructor() {
            super({
                // 이 페이지에서 사용할 API 엔드포인트 등 설정
                API_URL: '/employees'
            });

            // 이 페이지에서만 사용할 상태 변수 초기화
            this.state.employees = [];
        }

        // BasePage의 initializeApp에서 자동으로 호출됨
        setupEventListeners() {
            // 이 페이지의 버튼, 입력 필드 등에 이벤트 리스너를 바인딩
            document.getElementById('add-employee-btn').addEventListener('click', () => {
                // ...
            });
        }

        // BasePage의 initializeApp에서 자동으로 호출됨
        loadInitialData() {
            // 페이지 로드 시 필요한 초기 데이터를 불러옴
            this.fetchEmployees();
        }

        async fetchEmployees() {
            try {
                const result = await this.apiCall(this.config.API_URL);
                this.state.employees = result.data;
                this.render();
            } catch (error) {
                console.error('Failed to fetch employees:', error);
            }
        }

        render() {
            // 데이터를 화면에 렌더링
        }
    }

    // 페이지 스크립트 실행
    new EmployeeManagementPage();
    ```

3.  **컨트롤러에 스크립트 추가**: 해당 페이지를 렌더링하는 PHP 컨트롤러에서 `View::addJs()`를 사용하여 스크립트를 추가합니다.

    ```php
    // app/Controllers/Web/EmployeeController.php

    class EmployeeController extends BaseController
    {
        public function management()
        {
            // ...
            View::addJs(BASE_ASSETS_URL . '/assets/js/core/base-page.js');
            View::addJs(BASE_ASSETS_URL . '/assets/js/pages/employee-management.js');
            // ...
            $this->render('employees/management');
        }
    }
    ```
