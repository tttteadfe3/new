# 통합 관리 시스템 개발 문서

## 1. 프로젝트 개요

- **목적**: 클린박스/부적정 배출 관리 및 직원 관리 시스템
- **핵심 아키텍처**:
  - **Backend**: PHP 8.2+ 기반의 MVC (Model-View-Controller) 패턴
  - **Frontend**: JavaScript (ES6+ Classes)
  - **Database**: MariaDB 10.6+
- **주요 특징**:
  - **의존성 주입 (DI)**: `App\Core\Container`를 통한 객체 관리
  - **라우팅**: `App\Core\Router`를 통한 명시적인 URL-컨트롤러 매핑
  - **인증**: 카카오 OAuth2 단일 인증
  - **권한**: 역할 기반 접근 제어 (RBAC)
  - **API**: RESTful API 지원 (`/api/...`)

## 2. 기술 스택

| 구분 | 기술 | 설명 |
|------|------|------|
| 서버 | Nginx | PHP-FPM 연동 |
| 언어 | PHP 8.2+ | MVC 기반 |
| DB | MariaDB 10.6+ | PDO 사용, Singleton 패턴으로 관리 |
| 인증 | 카카오 OAuth2 | 단일 로그인 |
| 권한 | Role + Rule | 세부 행위 단위 권한 관리 |
| API | RESTful | `/api/v1/...` |
| 환경 | dotenv | 환경 변수 관리 |
| 로깅 | DB 기록 | sys_activity_logs 테이블 |

## 3. 아키텍처

### 3.1. 백엔드 (PHP)

#### 요청 처리 흐름

모든 웹 요청은 `public/index.php`를 통해 시작되며, 다음 순서로 처리됩니다.

1.  **`public/index.php`**:
    - Composer `autoload.php` 로드.
    - DI 컨테이너(`App\Core\Container`) 초기화 및 서비스, 리포지토리, 컨트롤러 등록.
    - 라우터(`App\Core\Router`) 초기화.
    - 미들웨어(`AuthMiddleware`, `PermissionMiddleware`) 등록.
    - `routes/web.php`, `routes/api.php` 파일 로드.
2.  **Router**:
    - 현재 요청 URL과 일치하는 라우트를 찾아 해당 컨트롤러의 메서드를 호출합니다.
3.  **Middleware**:
    - 컨트롤러 실행 전, 인증 및 권한 검사를 수행합니다.
4.  **Controller (`app/Controllers`)**:
    - HTTP 요청을 받아 입력 데이터 유효성 검사.
    - 비즈니스 로직 처리를 서비스(Service)에 위임.
    - 서비스로부터 받은 결과를 `View` 또는 `JsonResponse`를 통해 응답.
5.  **Service (`app/Services`)**:
    - 애플리케이션의 핵심 비즈니스 로직을 담당.
    - 여러 리포지토리(Repository)를 조합하여 데이터를 처리.
6.  **Repository (`app/Repositories`)**:
    - 데이터베이스와의 상호작용을 담당.
    - SQL 쿼리를 실행하고 결과를 서비스에 반환.

#### 디렉토리 구조

- **`app/`**: 애플리케이션의 핵심 코드.
  - `Controllers/`: 요청 처리 및 응답 반환.
  - `Services/`: 비즈니스 로직.
  - `Repositories/`: 데이터베이스 상호작용.
  - `Views/`: 프레젠테이션 로직 (HTML).
  - `Core/`: DI 컨테이너, 라우터 등 핵심 클래스.
  - `Middleware/`: 요청 중간 처리 (인증, 권한).
- **`routes/`**: URL 라우팅 정의.
  - `web.php`: 웹 페이지 라우트.
  - `api.php`: API 라우트.
- **`config/`**: 애플리케이션 설정.
- **`public/`**: 웹 서버의 Document Root.

### 3.2. 프론트엔드 (JavaScript)

#### 아키텍처 가이드

- **`public/assets/js/`**: 모든 JavaScript 모듈의 루트 디렉토리.
  - **`core/`**: `base-page.js` 등 핵심 기반 클래스.
  - **`pages/`**: 페이지별 고유 로직을 담는 진입점 클래스.
  - **`components/`**: 재사용 가능한 UI 컴포넌트.
  - **`services/`**: API 통신 등 UI 없는 공유 기능.
  - **`utils/`**: 순수 유틸리티 함수.

#### 페이지 스크립트 작성 절차

1.  **파일 생성**: `public/assets/js/pages/feature-name.js` 형식으로 파일을 생성합니다.
2.  **클래스 작성**: `BasePage`를 상속받아 페이지별 로직을 구현합니다.
3.  **컨트롤러에 등록**: 해당 페이지를 렌더링하는 PHP 컨트롤러에서 `View::addJs()`를 사용해 스크립트를 추가합니다.

### 3.3. 데이터베이스

- **`database/schema.sql`**: 전체 데이터베이스 구조를 정의하는 스키마 파일.
- **`database/seeds/`**: 초기 데이터를 담고 있는 시드 파일. **파일명 순서대로** 임포트해야 합니다.

## 4. 코딩 규칙

### 4.1. 명명 규칙

- **PHP 클래스**: `PascalCase` (예: `LitteringController`)
- **PHP 메서드**: `camelCase` (예: `getLitteringById`)
- **JavaScript 파일**: `kebab-case.js` (예: `littering-manage.js`)
- **JavaScript 클래스**: `PascalCase` (클래스명 끝에 `Page` 추가, 예: `LitteringManagePage`)
- **CSS 파일**: `kebab-case.css` (예: `littering-manage.css`)
- **View 파일**: `kebab-case.php` (예: `littering-manage.php`)

### 4.2. 스타일 가이드

- **CSS**: View 파일 내 `<style>` 태그나 인라인 스타일 사용을 금지합니다. 모든 스타일은 `public/assets/css/pages` 내의 CSS 파일로 분리해야 합니다.

---

## 5. 수동 설치 가이드

(기존 설치 가이드 내용은 여기에 동일하게 포함됩니다.)

### 1. 요구 사양
- **PHP**: 8.0.0 이상 (`pdo_mysql`, `curl`, `mbstring` 확장 프로그램 필요)
- **데이터베이스**: MySQL 5.7+ 또는 MariaDB 10.2+
- **웹 서버**: Nginx 또는 Apache
- **의존성 관리자**: Composer

### 2. 설치 절차

#### 1단계: 소스 코드 다운로드
```bash
git clone [저장소_URL] .
```

#### 2단계: PHP 의존성 설치
```bash
composer install
```

#### 3단계: 환경 설정 파일(.env) 생성
```bash
cp .env.example .env
nano .env
```
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `KAKAO_REST_API_KEY` 등을 환경에 맞게 수정합니다.

#### 4단계: 데이터베이스 설정
1.  **데이터베이스 생성:**
    ```sql
    CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
2.  **데이터 임포트 (파일 순서 중요):**
    ```bash
    mysql -u your_user -p your_database_name < database/schema.sql
    mysql -u your_user -p your_database_name < database/seeds/01_departments.sql
    # ... 나머지 시드 파일 순서대로 임포트
    ```

#### 5단계: 디렉토리 권한 설정
```bash
mkdir -p public/uploads
sudo chown -R $USER:www-data storage public/uploads
sudo chmod -R 775 storage public/uploads
```

#### 6단계: 웹 서버 설정
웹 서버의 Document Root를 프로젝트의 `public` 디렉토리로 설정합니다.
