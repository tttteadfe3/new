# 5. 프론트엔드 자산 관리 분석

## 5.1. 분석

이 애플리케이션의 프론트엔드 자산(CSS, JavaScript, 이미지 등)은 `public/assets` 디렉토리 내에 체계적으로 관리되고 있으며, 정적 및 동적 두 가지 방식으로 페이지에 포함됩니다.

### 5.1.1. 자산의 물리적 구조

-   **루트 디렉토리**: 모든 프론트엔드 자산은 `public/assets/` 디렉토리 아래에 위치합니다.
-   **하위 디렉토리 구조**:
    -   `css/`: 애플리케이션 전반에 사용되는 CSS 파일 (`bootstrap.min.css`, `app.min.css` 등).
    -   `js/`: 애플리케이션의 커스텀 JavaScript 파일. 페이지별 로직을 담은 `pages/`와 공통 플러그인을 담은 `plugins/`로 세분화되어 있습니다.
    -   `libs/`: `Bootstrap`, `jQuery` 등 외부 서드파티 라이브러리.
    -   `images/`, `fonts/`: 이미지와 폰트 파일.
-   **결론**: 자산이 종류와 역할에 따라 명확하게 분리되어 있어 구조를 파악하기 쉽습니다.

### 5.1.2. 자산 포함 방식

#### 1. 정적 자산 포함 (Static Asset Inclusion)

-   **방식**: 모든 페이지에 공통적으로 필요한 핵심 CSS와 JavaScript 파일들은 메인 레이아웃 파일(예: `app/Views/layouts/app.php`, `basic.php`)의 `<head>` 태그와 `<body>` 태그가 닫히기 직전에 직접 하드코딩되어 있습니다.
-   **경로 관리**: `config/config.php`에 정의된 `BASE_ASSETS_URL` 상수를 사용하여 `href`나 `src` 경로를 생성합니다. 이는 애플리케이션이 어떤 도메인이나 서브디렉토리에서 호스팅되더라도 자산 경로가 깨지지 않도록 보장하는 좋은 방법입니다.
    ```html
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" ...>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    ```

#### 2. 동적 자산 포함 (Dynamic Asset Inclusion)

-   **핵심 컴포넌트**: `app/Core/View.php`
-   **방식**: 특정 페이지에서만 필요한 JavaScript나 CSS 파일은 컨트롤러에서 `View` 클래스의 정적 메서드를 호출하여 동적으로 추가할 수 있습니다.
    -   `View::addJs('path/to/script.js')`
    -   `View::addCss('path/to/style.css')`
-   **작동 원리**:
    1.  컨트롤러에서 `addJs()`나 `addCss()`가 호출되면, `View` 클래스는 해당 `<script>` 또는 `<link>` 태그를 생성하여 내부적으로 `js` 또는 `css`라는 이름의 섹션(section) 배열에 누적합니다.
    2.  뷰 파일(`*.php`)이 렌더링된 후, 최종적으로 레이아웃 파일이 렌더링됩니다.
    3.  레이아웃 파일의 적절한 위치(보통 `<head>` 끝 또는 `<body>` 끝)에 `<?= View::yieldSection('js') ?>`와 같은 코드를 배치하여, 해당 요청 동안 `addJs()`로 추가된 모든 스크립트 태그들을 한 번에 출력합니다.

-   **결론**: 이 방식은 페이지별로 필요한 자산만 선택적으로 로드할 수 있게 하여, 불필요한 파일 로드를 줄이고 초기 페이지 로딩 성능을 최적화하는 효과적인 방법을 제공합니다.

## 5.2. 개선 방안 및 제안

현재 자산 관리 방식은 PHP 기반의 전통적인 웹 애플리케이션에서 매우 효과적입니다. 하지만 더 나은 개발 경험과 성능 최적화를 위해 현대적인 프론트엔드 개발 워크플로우 도입을 제안합니다.

### 제안 1: 프론트엔드 빌드 도구 도입 (Vite 또는 Webpack)

-   **문제점**:
    -   **수동 의존성 관리**: JavaScript 파일 간의 의존성(예: `menu-admin-app.js`가 `api-service.js`를 필요로 함)이 코드 상으로 명시되지 않고, 개발자가 레이아웃 파일이나 컨트롤러에서 로드 순서를 직접 관리해야 합니다. 이는 실수를 유발할 수 있으며 프로젝트가 커질수록 관리가 복잡해집니다.
    -   **성능 최적화 한계**: CSS와 JavaScript 파일이 압축(minification)되지 않은 원본 상태로 서비스되거나, 여러 파일이 개별적으로 로드되어 네트워크 요청 횟수를 증가시킵니다.
    -   **최신 JavaScript 기능 사용 제약**: ES6+의 모듈 시스템(`import`/`export`), `async`/`await`와 같은 최신 JavaScript 문법을 사용하면 일부 구형 브라우저에서 호환성 문제가 발생할 수 있습니다.
-   **개선 방안**: **Vite**나 **Webpack**과 같은 현대적인 프론트엔드 빌드 도구를 도입하는 것을 강력히 권장합니다.
    -   **의존성 자동 관리**: JavaScript 파일 상단에 `import ApiService from './api-service.js'`와 같이 의존성을 명시적으로 선언할 수 있습니다. 빌드 도구는 이 관계를 분석하여 필요한 모든 파일을 하나의 파일로 자동으로 묶어줍니다(Bundling).
    -   **자동 최적화**: 빌드 과정에서 CSS와 JavaScript 코드를 자동으로 압축(Minification)하고, 여러 파일을 하나로 병합(Bundling)하여 네트워크 요청 수를 최소화합니다. 또한, 사용되지 않는 코드를 제거(Tree-shaking)하여 파일 크기를 더욱 줄일 수 있습니다.
    -   **최신 문법 지원**: Babel과 같은 트랜스파일러를 통해 최신 JavaScript 코드를 구형 브라우저에서도 동작하는 코드로 자동 변환해줍니다.
    -   **개발 경험 향상**: 소스 코드를 수정할 때마다 변경 사항이 즉시 브라우저에 반영되는 HMR(Hot Module Replacement) 기능을 통해 개발 생산성을 크게 높일 수 있습니다.

### 제안 2: CSS 전처리기(Sass) 도입

-   **문제점**: 순수 CSS는 변수, 중첩 규칙, 믹스인(mixin)과 같은 기능이 부족하여 코드의 재사용성과 유지보수성이 떨어질 수 있습니다.
-   **개선 방안**: `app.min.css`와 `custom.min.css`를 관리하기 위해 **Sass(SCSS)**와 같은 CSS 전처리기를 도입하는 것을 제안합니다.
    -   `_variables.scss` 파일에 주요 색상, 폰트 크기 등을 변수로 정의하여 전체 디자인의 일관성을 쉽게 유지할 수 있습니다.
    -   중첩(nesting) 문법을 통해 CSS 선택자 구조를 HTML 구조와 유사하게 작성하여 가독성을 높일 수 있습니다.
    -   `@mixin`과 `@include`를 사용하여 반복되는 스타일 그룹을 재사용 가능한 단위로 만들 수 있습니다.
    -   빌드 도구(Vite/Webpack)와 함께 사용하면 `.scss` 파일이 수정될 때마다 자동으로 `.css` 파일로 컴파일됩니다.