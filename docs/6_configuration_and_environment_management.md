# 6. 구성 및 환경 관리 분석

## 6.1. 분석

이 애플리케이션의 구성 및 환경 관리는 `.env` 파일과 `config/config.php`라는 두 개의 주요 파일을 통해 이루어집니다. 하지만 두 파일의 역할이 명확히 분리되지 않아 설정이 중복되고 일부는 의도대로 동작하지 않는 문제가 있습니다.

### 6.1.1. 환경 변수 (`.env`)

-   **역할**: 데이터베이스 비밀번호, API 키 등 민감하거나 환경에 따라 달라질 수 있는 값을 코드베이스와 분리하여 관리하는 것이 주 목적입니다.
-   **로딩 방식**: `public/index.php`에서 `vlucas/phpdotenv` 라이브러리를 사용하여 프로젝트 루트의 `.env` 파일에 정의된 변수들을 `$_ENV` 슈퍼글로벌 변수로 로드합니다.
-   **`.env.example`**: 프로젝트에 필요한 환경 변수 목록을 제공하는 템플릿 파일 역할을 합니다.

### 6.1.2. 중앙 설정 파일 (`config/config.php`)

-   **역할**: 애플리케이션의 전반적인 동작을 제어하는 핵심 설정 파일입니다. `public/index.php`에서 `.env` 파일이 로드된 직후에 `require`됩니다.
-   **주요 기능**:
    -   **`ENVIRONMENT` 상수**: `development` 또는 `production` 값을 가지는 상수를 직접 정의하여, 오류 메시지 표시 여부 등 애플리케이션의 동작 모드를 결정합니다.
    -   **상수 정의**: `DB_HOST`, `KAKAO_CLIENT_ID` 등 다양한 상수를 정의합니다.
    -   **초기화 로직**: 전역 예외 처리기 설정, 커스텀 오토로더 등록, 헬퍼 함수 로드, 세션 시작 등 애플리케이션 부트스트래핑(bootstrapping)의 일부를 담당합니다.

### 6.1.3. 심각한 문제점: 설정 값의 충돌 및 무시

-   **문제 현상**: `public/index.php`에서 `.env` 파일의 `DB_USER`, `DB_PASS` 등의 값을 `$_ENV`로 성공적으로 로드하더라도, 바로 다음에 로드되는 `config/config.php` 파일 내에서 `define('DB_USER', 'erp');`, `define('DB_PASS', 'Dnjstlf!23');` 와 같이 **하드코딩된 값으로 상수를 덮어쓰고 있습니다.**
-   **결과**: 이로 인해 `.env` 파일에 아무리 다른 값을 설정하더라도 실제 애플리케이션은 `config/config.php`에 하드코딩된 값을 사용하게 됩니다. 이는 `.env` 시스템을 사용하는 목적을 무의미하게 만들며, 다른 개발자가 프로젝트를 설정할 때 심각한 혼란을 유발하고 배포 과정을 복잡하게 만듭니다.

### 6.1.4. 기타 문제점

-   **중복된 오토로더**: `config/config.php` 내에 `spl_autoload_register`를 사용한 커스텀 오토로더가 존재합니다. 하지만 `public/index.php`에서 이미 `vendor/autoload.php`를 통해 Composer의 PSR-4 오토로더를 로드하고 있으므로, 이 코드는 불필요한 중복입니다.

## 6.2. 개선 방안 및 제안

설정 관리 방식을 명확하고 일관되게 개선하여 예측 가능하고 안전한 애플리케이션 환경을 구축할 것을 강력히 권장합니다.

### 제안 1: 환경 변수를 유일한 설정 소스로 사용 (가장 중요)

-   **개선 방안**: `config/config.php`에서 하드코딩된 값을 모두 제거하고, 대신 `$_ENV`에 로드된 환경 변수를 사용하여 상수를 정의해야 합니다. 환경 변수가 없을 경우를 대비하여 기본값(fallback)을 제공할 수 있습니다.

    ```php
    // 수정 제안: config/config.php

    // 데이터베이스 설정
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'erp');
    define('DB_USER', $_ENV['DB_USER'] ?? 'erp');
    define('DB_PASS', $_ENV['DB_PASS'] ?? ''); // 비밀번호는 기본값을 비워두는 것이 안전합니다.
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

    // 카카오 인증 설정
    define('KAKAO_CLIENT_ID', $_ENV['KAKAO_CLIENT_ID'] ?? '');
    define('KAKAO_REDIRECT_URI', $_ENV['KAKAO_REDIRECT_URI'] ?? '');
    ```
-   **효과**:
    -   `.env` 파일이 설정의 **유일한 진실 공급원(Single Source of Truth)**이 되어 혼란을 없앱니다.
    -   로컬, 테스트, 프로덕션 등 다양한 환경에 맞게 `.env` 파일만 수정하여 쉽게 배포할 수 있습니다.
    -   민감한 정보가 코드 저장소(`config/config.php`)에서 완전히 제거되어 보안이 향상됩니다.

### 제안 2: `ENVIRONMENT` 상수도 환경 변수로 관리

-   **문제점**: 현재 `ENVIRONMENT` 상수는 `config/config.php`에 하드코딩되어 있어, 프로덕션 서버에 배포할 때마다 이 파일을 직접 수정해야 하는 실수를 유발할 수 있습니다.
-   **개선 방안**: `.env` 파일에 `APP_ENV=development`와 같은 변수를 추가하고, `config/config.php`에서 이 값을 읽어 `ENVIRONMENT` 상수를 정의하도록 변경합니다.

    ```php
    // .env 파일에 추가
    // APP_ENV=production

    // config/config.php 에서 수정
    define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'production'); // 기본값은 안전하게 'production'으로 설정
    ```
-   **효과**: 배포 시 코드 수정이 필요 없어져 배포 자동화가 용이해지고 실수를 방지할 수 있습니다.

### 제안 3: 불필요한 커스텀 오토로더 제거

-   **개선 방안**: `config/config.php` 파일 하단에 있는 `spl_autoload_register` 관련 코드를 완전히 삭제합니다. Composer의 오토로더가 모든 `App\` 네임스페이스 클래스를 자동으로 로드해주므로 이 코드는 필요 없습니다.
-   **효과**: 코드베이스가 더 간결해지고, 클래스 로딩 메커니즘이 Composer 표준으로 통일되어 혼란의 여지를 없앱니다.

### 제안 4: 동적 URL 상수 생성

-   **문제점**: `BASE_URL` 상수가 빈 값으로 하드코딩되어 있어, 특정 상황에서 URL 생성에 문제가 발생할 수 있습니다.
-   **개선 방안**: `$_SERVER` 변수를 사용하여 현재 요청의 프로토콜과 호스트 이름을 기반으로 `BASE_URL`을 동적으로 생성하도록 변경합니다.

    ```php
    // config/config.php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host);
    ```
-   **효과**: 어떤 개발 환경이나 서버 도메인에서도 코드를 수정할 필요 없이 애플리케이션이 올바르게 동작합니다.