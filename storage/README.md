# 직원 관리 및 현장 업무 지원 시스템

## 프로젝트 개요

본 프로젝트는 현장 직원의 업무를 효율적으로 관리하고 지원하기 위한 웹 기반 시스템입니다. 주요 기능으로는 직원 정보 관리, 연차 관리, 무단투기 민원 접수 및 처리, 대형폐기물 수거 관리 등이 있습니다. 사용자는 카카오 소셜 로그인을 통해 간편하게 시스템에 접근할 수 있으며, 관리자는 웹 대시보드를 통해 시스템의 모든 데이터를 관리하고 모니터링할 수 있습니다.

## 주요 기능

-   **사용자 및 직원 관리**:
    -   카카오 소셜 로그인을 통한 사용자 인증
    -   역할 기반 접근 제어(RBAC)
    -   직원 정보 및 변경 이력 관리
    -   부서 및 직급 관리

-   **연차 관리**:
    -   근속 연수에 따른 자동 연차 계산 및 부여
    -   사용자 연차 신청 및 관리자 승인/반려 워크플로우
    -   연차 수동 조정 및 내역 관리

-   **무단투기 관리**:
    -   사용자의 무단투기 민원 접수 (사진 및 위치 정보 포함)
    -   관리자의 민원 확인 및 처리 상태 업데이트

-   **대형폐기물 수거 관리**:
    -   현장 직원의 수거 내역 등록 (사진 및 품목 정보 포함)
    -   온라인 접수 내역 일괄 등록 (HTML 파일 파싱)
    -   수거 신청 건에 대한 관리자 메모 및 처리 기능

## 시스템 아키텍처

-   **프론트엔드**: HTML, CSS, JavaScript (jQuery 사용)
-   **백엔드**: PHP (네이티브)
-   **데이터베이스**: MySQL (또는 MariaDB)
-   **서버**: Apache 또는 Nginx

애플리케이션은 MVC(Model-View-Controller)와 유사한 패턴을 따르며, 각 디렉토리의 역할은 다음과 같습니다.

-   `/api`: 백엔드 로직을 처리하는 API 엔드포인트
-   `/app`: 핵심 비즈니스 로직 (Controllers, Repositories, Services)
-   `/assets`: CSS, JavaScript, 이미지 등 정적 파일
-   `/auth`: 인증 관련 로직 (카카오 로그인, 세션 관리)
-   `/config`: 애플리케이션 설정
-   `/database`: 데이터베이스 스키마 및 시드 데이터
-   `/layouts`: 공통 UI 레이아웃 (헤더, 푸터)
-   `/views`: 각 페이지의 UI를 구성하는 뷰 파일

## 설치 및 설정 방법

1.  **저장소 클론**:
    ```bash
    git clone https://repository.url/project.git
    cd project
    ```

2.  **웹 서버 설정**:
    -   Apache 또는 Nginx와 같은 웹 서버를 설치하고, 프로젝트의 루트 디렉토리를 웹 서버의 Document Root로 설정합니다.
    -   URL 리라이팅을 위해 `.htaccess` 또는 서버 설정을 구성해야 할 수 있습니다.

3.  **데이터베이스 설정**:
    -   `database/schema.sql` 파일을 사용하여 데이터베이스와 테이블을 생성합니다.
        ```bash
        mysql -u [username] -p [database_name] < database/schema.sql
        ```
    -   필요한 초기 데이터를 입력하기 위해 `database/seeds/` 디렉토리의 SQL 파일들을 순서대로 실행합니다.

4.  **설정 파일 생성**:
    -   `config/config.php` 파일을 생성하고 데이터베이스 연결 정보 및 기타 설정을 입력합니다. 아래는 예시입니다.
        ```php
        <?php
        // 데이터베이스 설정
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'your_database_name');
        define('DB_USER', 'your_username');
        define('DB_PASS', 'your_password');
        define('DB_CHARSET', 'utf8mb4');

        // 카카오 로그인 설정
        define('KAKAO_CLIENT_ID', 'your_kakao_rest_api_key');
        define('KAKAO_REDIRECT_URI', 'http://your.domain/auth/kakao_callback.php');
        define('KAKAO_MAP_API_KEY', 'your_kakao_map_api_key');


        // 기본 URL 설정
        define('BASE_URL', 'http://your.domain');
        define('BASE_ASSETS_URL', 'http://your.domain');

        // 기타 설정
        define('ROOT_PATH', dirname(__DIR__));
        // ... (필요한 다른 설정 추가)
        ```

5.  **의존성 설치**:
    -   이 프로젝트는 외부 PHP 라이브러리 관리를 위해 Composer를 사용하지 않으므로, 별도의 `vendor` 설치 과정은 필요 없습니다.

6.  **권한 설정**:
    -   `storage/` 디렉토리에 웹 서버가 파일을 쓸 수 있도록 권한을 설정합니다.
        ```bash
        chmod -R 755 storage
        chown -R www-data:www-data storage
        ```

## 사용 방법

-   **최초 관리자 설정**:
    1.  카카오 계정으로 로그인하여 사용자 계정을 생성합니다.
    2.  데이터베이스의 `users` 테이블에서 생성된 사용자의 `status`를 `active`로 변경합니다.
    3.  `roles` 테이블과 `permissions` 테이블을 확인하고, `user_roles` 테이블에 해당 사용자와 관리자 역할을 연결합니다.

-   **일반 사용자**:
    -   카카오 로그인을 통해 접속합니다.
    -   '내 정보'에서 프로필을 수정하거나, '연차 신청', '무단투기 등록' 등의 메뉴를 사용할 수 있습니다.

-   **관리자**:
    -   관리자 계정으로 로그인 후, 대시보드에서 다양한 관리 기능(사용자, 직원, 연차, 메뉴 등)에 접근할 수 있습니다.
