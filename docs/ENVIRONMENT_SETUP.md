# 개발 환경 설정 가이드 (Environment Setup Guide)

이 문서는 프로젝트의 개발 환경을 구축하는 방법을 안내합니다.

## 🚀 시작하기

### 사전 요구사항
-   PHP 8.2 이상
-   Composer
-   MySQL/MariaDB 8.0 이상
-   웹 서버 (Apache, Nginx 등)
-   Git

### 개발 환경 설정 절차

#### 1. 프로젝트 클론
```bash
git clone [repository-url]
cd [project-directory]
```

#### 2. `.env` 파일 생성 및 설정
프로젝트 루트 디렉토리에서 `.env.example` 파일을 복사하여 `.env` 파일을 생성합니다.
```bash
cp .env.example .env
```
생성된 `.env` 파일을 열고, 데이터베이스 관련 환경변수들을 설정합니다:
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# 카카오 로그인 설정 (선택사항)
KAKAO_CLIENT_ID=your_kakao_client_id
KAKAO_CLIENT_SECRET=your_kakao_client_secret
KAKAO_REDIRECT_URI=http://localhost/auth/kakao/callback
```

#### 3. Composer 의존성 설치
```bash
composer install
```

#### 4. 데이터베이스 초기화
데이터베이스를 생성하고 스키마를 로드합니다:
```bash
# MySQL/MariaDB에 접속하여 데이터베이스 생성
mysql -u root -p
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# 스키마 로드
mysql -u your_username -p your_database_name < database/schema.sql
```

#### 5. 웹 서버 설정
웹 서버의 Document Root를 `public/` 디렉토리로 설정합니다.

**Apache 설정 예시** (`.htaccess` 사용):
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/project/public
    ServerName your-domain.local
    
    <Directory /path/to/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx 설정 예시**:
```nginx
server {
    listen 80;
    server_name your-domain.local;
    root /path/to/project/public;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### 6. 애플리케이션 접속
웹 브라우저에서 설정한 도메인으로 접속하여 애플리케이션을 확인합니다.

---

## ⚙️ 추가 설정

### PHP 확장 모듈 요구사항
이 프로젝트는 다음 PHP 확장 모듈들이 필요합니다:
- `pdo_mysql` - 데이터베이스 연결
- `mbstring` - 멀티바이트 문자열 처리
- `curl` - HTTP 요청 (카카오 로그인 등)
- `json` - JSON 데이터 처리
- `openssl` - 암호화 및 보안

### 권한 설정
웹 서버가 다음 디렉토리에 쓰기 권한을 가져야 합니다:
```bash
chmod -R 755 storage/
chmod -R 755 public/assets/uploads/ # 업로드 디렉토리가 있는 경우
```

### 로그 디렉토리 설정
```bash
mkdir -p storage/logs
chmod 755 storage/logs
```

### 개발 도구 (선택사항)

#### Xdebug 설정 (디버깅용)
```ini
; php.ini에 추가
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003
```

#### 코드 품질 도구
```bash
# PHP CodeSniffer (코딩 스타일 검사)
composer require --dev squizlabs/php_codesniffer

# PHPStan (정적 분석)
composer require --dev phpstan/phpstan
```