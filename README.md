# 시스템 개발 기초 문서

## 1. 프로젝트 개요
- **목적**: 클린박스/부적정 배출 관리 및 직원 관리 시스템  
- **개발자**: 1인  
- **환경**:
  - Web Server: Nginx
  - Backend: PHP 8.2+
  - Database: MariaDB 10.6+
  - 구조: MVC
  - URL: 짧은 주소 지원
  - 인증: 카카오 로그인 단일 인증
  - 권한: Role + Rule 기반 퍼미션
  - 로깅: 사용자 행위 기록

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

## 3. 디렉토리 구조
\`\`\`
project_root/
├─ public/            # index.php, assets (Web Server Document Root)
├─ app/
│   ├─ Controllers/
│   ├─ Repositories/  # 데이터베이스 로직
│   ├─ Views/
│   ├─ Core/          # Router, DB, SessionManager 등 핵심 클래스
│   ├─ Services/      # 비즈니스 로직 (인증, 권한, 메뉴 관리 등)
│   └─ Middleware/
├─ config/            # config.php, routes.php
├─ database/          # DB 스키마 및 시드
├─ storage/           # 로그, 캐시
├─ vendor/
└─ .env
\`\`\`

---

## 수동 설치 가이드

이 가이드는 터미널과 데이터베이스 클라이언트를 사용하여 수동으로 애플리케이션을 설치하는 방법을 안내합니다.

### 1. 요구 사양
설치를 시작하기 전, 서버 환경이 다음 요구 사양을 만족하는지 확인해주세요.

- **PHP**: 8.0.0 이상
- **PHP 확장 프로그램**: `pdo_mysql`, `curl`, `mbstring`
- **데이터베이스**: MySQL 5.7+ 또는 MariaDB 10.2+
- **웹 서버**: Nginx 또는 Apache
- **버전 관리**: Git
- **PHP 의존성 관리자**: Composer

### 2. 설치 절차

#### 1단계: 소스 코드 다운로드
터미널을 열고, 웹 서버의 루트 디렉토리에서 아래 명령어를 실행하여 프로젝트를 클론합니다.
```bash
git clone [저장소_URL] .
```

#### 2단계: PHP 의존성 설치
프로젝트 루트 디렉토리에서 Composer를 사용하여 필요한 라이브러리를 설치합니다.
```bash
composer install
```

#### 3단계: 환경 설정 파일(.env) 생성
`.env.example` 파일을 복사하여 `.env` 파일을 생성하고, 텍스트 편집기로 열어 내용을 수정합니다.
```bash
cp .env.example .env
nano .env
```
아래 항목들을 자신의 환경에 맞게 수정해주세요.
- `DB_HOST`: 데이터베이스 서버 주소 (예: 127.0.0.1)
- `DB_DATABASE`: 사용할 데이터베이스 이름
- `DB_USERNAME`: 데이터베이스 사용자 이름
- `DB_PASSWORD`: 데이터베이스 사용자 비밀번호
- `KAKAO_REST_API_KEY`: 카카오 주소 검색 API를 위한 REST API 키

#### 4단계: 데이터베이스 설정
MySQL 또는 MariaDB 클라이언트에 접속하여, `.env` 파일에 설정한 데이터베이스를 생성하고 데이터를 임포트합니다.

1.  **데이터베이스 생성:**
    ```sql
    CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
2.  **데이터 임포트 (터미널 사용 예시):**
    `your_database_name`, `your_user`, `your_password`를 자신의 환경에 맞게 변경하여 아래 명령어를 실행합니다. **파일 순서가 매우 중요합니다.**

    ```bash
    # 1. 스키마 임포트
    mysql -u your_user -p your_database_name < database/schema.sql

    # 2. 시드 데이터 임포트 (파일 이름 순서대로)
    mysql -u your_user -p your_database_name < database/seeds/01_departments.sql
    mysql -u your_user -p your_database_name < database/seeds/02_positions.sql
    mysql -u your_user -p your_database_name < database/seeds/03_roles.sql
    mysql -u your_user -p your_database_name < database/seeds/04_permissions.sql
    mysql -u your_user -p your_database_name < database/seeds/05_role_permissions.sql
    mysql -u your_user -p your_database_name < database/seeds/06_employees.sql
    mysql -u your_user -p your_database_name < database/seeds/07_users.sql
    mysql -u your_user -p your_database_name < database/seeds/08_user_roles.sql
    mysql -u your_user -p your_database_name < database/seeds/09_menus.sql
    ```

#### 5단계: 디렉토리 권한 설정
웹 서버가 로그 파일과 업로드된 파일을 저장할 수 있도록, 특정 디렉토리에 쓰기 권한을 부여해야 합니다. 프로젝트 루트에서 아래 명령어를 실행합니다.
```bash
# `www-data`는 웹 서버 사용자(예: Nginx, Apache)에 따라 다를 수 있습니다.
sudo chown -R $USER:www-data storage public/uploads
sudo chmod -R 775 storage public/uploads
```
`public/uploads` 디렉토리가 없다면 생성해주세요.
```bash
mkdir -p public/uploads
```

#### 6단계: 웹 서버 설정
웹 서버의 Document Root를 프로젝트의 `public` 디렉토리로 설정합니다. 이는 `index.php` 파일이 모든 요청을 처리하도록 하는 중요한 설정입니다.

**Nginx 설정 예시 (`/etc/nginx/sites-available/your-site.conf`):**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # PHP 버전에 맞게 수정
    }
}
```
설정 변경 후 Nginx를 재시작하는 것을 잊지 마세요.
```bash
sudo systemctl restart nginx
```

이제 웹 브라우저에서 설정한 도메인으로 접속하면 애플리케이션이 실행됩니다.
