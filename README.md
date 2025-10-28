# 통합 관리 시스템

이 문서는 통합 관리 시스템 프로젝트의 개요, 설치 방법, 기술 스택 및 개발에 필요한 주요 정보를 안내합니다.

## 1. 프로젝트 개요

- **목적**: 클린박스/부적정 배출 관리 및 직원 관리를 위한 웹 기반 통합 시스템입니다.
- **핵심 아키텍처**: 서비스 계층(Service Layer)이 추가된 MVC-S (Model-View-Controller-Service) 패턴을 따릅니다.

> **Note**
> 이 README는 프로젝트의 핵심 정보를 요약한 문서입니다. 더 상세한 기술 지침은 `docs/` 디렉토리의 가이드 문서를 참고하십시오.

## 2. 시작하기 (Getting Started)

### 요구 사양
- **PHP**: 8.2 이상 (`pdo_mysql`, `curl`, `mbstring` 확장 프로그램 필요)
- **Database**: MariaDB 10.6+
- **Web Server**: Nginx 또는 Apache
- **Dependency Manager**: Composer

### 설치 절차

1.  **소스 코드 다운로드**
    ```bash
    git clone [저장소_URL] .
    ```

2.  **PHP 의존성 설치**
    ```bash
    composer install
    ```

3.  **환경 설정 파일(.env) 생성**
    ```bash
    cp .env.example .env
    nano .env
    ```
    - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `KAKAO_REST_API_KEY` 등 주요 환경 변수를 실제 환경에 맞게 수정합니다.

4.  **데이터베이스 설정**
    데이터베이스 클라이언트에 접속하여 `.env` 파일에 설정한 이름으로 데이터베이스를 생성한 후, 아래 스크립트를 실행하여 스키마와 초기 데이터를 임포트합니다.
    ```sql
    -- 예: 데이터베이스 생성
    CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
    ```bash
    # 스키마 및 시드 데이터 임포트
    php scripts/seed.php
    ```
    - **참고**: 데이터베이스 스키마는 `database/schema.sql` 파일에서 확인할 수 있습니다.

5.  **디렉토리 권한 설정**
    ```bash
    mkdir -p public/uploads
    sudo chown -R $USER:www-data storage public/uploads
    sudo chmod -R 775 storage public/uploads
    ```

6.  **웹 서버 설정**
    웹 서버의 Document Root를 프로젝트의 `public` 디렉토리로 설정합니다.

## 3. 기술 스택 및 아키텍처

| 구분 | 기술 | 설명 |
|------|------|------|
| Backend | PHP 8.2+ | MVC-S 패턴 기반 |
| Frontend | Vanilla JavaScript (ES6+) | `BasePage` 클래스 기반 페이지별 모듈화 |
| Database | MariaDB 10.6+ | PDO 사용 |
| Authentication | Kakao OAuth2 | 단일 로그인 |
| Authorization | Role-Based Access Control (RBAC) | 서비스 계층에서 권한 처리 |
| Dependencies | Composer | PHP 의존성 관리 |

프로젝트의 상세한 아키텍처, 요청 처리 흐름, 의존성 주입 정책, 인증/권한 처리 방식에 대한 정보는 아래 문서를 참고하십시오.
- **[상세 아키텍처 가이드](./docs/architecture.md)**

## 4. 프로젝트 구조

- **`app/`**: 애플리케이션의 핵심 코드가 위치합니다.
  - `Controllers/`: HTTP 요청 처리 및 응답 반환.
  - `Services/`: 핵심 비즈니스 로직.
  - `Repositories/`: 데이터베이스 상호작용.
  - `Views/`: 프레젠테이션 로직 (HTML 템플릿).
  - `Core/`: DI 컨테이너, 라우터 등 프레임워크 핵심 클래스.
- **`public/`**: 웹 서버의 Document Root이자 프론트엔드 에셋의 위치입니다.
  - `assets/`: CSS, JavaScript, 이미지 등.
- **`routes/`**: URL 라우팅 정의 파일.
  - `web.php`: 웹 페이지 라우트.
  - `api.php`: API 라우트.
- **`docs/`**: 프로젝트 관련 모든 가이드 문서.
- **`database/`**: 데이터베이스 스키마(`schema.sql`) 및 시드(`seeds/`) 파일.
- **`CHANGELOG.md`**: 프로젝트의 모든 주요 변경 이력.

## 5. 개발 가이드

본격적인 개발에 앞서, 아래의 가이드 문서들을 반드시 숙지하십시오.

- **[백엔드 개발 가이드](./docs/backend-guide.md)**: PHP 코드 작성 규칙, 서비스 및 리포지토리 패턴 사용법, 새로운 기능 추가 절차 등을 포함합니다.
- **[프론트엔드 개발 가이드](./docs/frontend-guide.md)**: JavaScript 코드 구조, `BasePage` 클래스의 생명주기, API 호출 및 상태 관리 패턴 등을 포함합니다.
- **[데이터베이스 가이드](./docs/database-guide.md)**: 테이블 명명 규칙과 스키마 설계 원칙을 안내합니다.
- **[API 가이드](./docs/api-guide.md)**: 모든 API가 준수해야 할 JSON 응답 형식 표준을 정의합니다.

## 6. 코딩 규칙

- **PHP 클래스**: `PascalCase` (예: `LitteringController`)
- **PHP 메서드**: `camelCase` (예: `getLitteringById`)
- **JavaScript 파일**: `kebab-case.js` (예: `littering-manage.js`)
- **JavaScript 클래스**: `PascalCase` (페이지 클래스는 `Page` 접미사 사용, 예: `LitteringManagePage`)
- **View 파일**: `kebab-case.php` (예: `littering-manage.php`)
- **CSS**: View 파일 내 `<style>` 태그나 인라인 스타일 사용을 금지합니다. 모든 스타일은 `public/assets/css/pages/` 디렉토리의 CSS 파일로 분리해야 합니다.
