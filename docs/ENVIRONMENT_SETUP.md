# 개발 환경 설정 가이드 (Environment Setup Guide)

이 문서는 Docker를 사용하여 프로젝트의 표준 개발 환경을 구축하는 방법을 안내합니다.

## 🚀 시작하기

### 사전 요구사항
-   [Docker](https://www.docker.com/get-started) 및 `docker-compose`가 설치되어 있어야 합니다.
-   Git 저장소를 로컬 컴퓨터에 클론해야 합니다.

### 개발 환경 설정 절차

#### 1. `.env` 파일 생성 및 설정
프로젝트 루트 디렉토리에서 `.env.example` 파일을 복사하여 `.env` 파일을 생성합니다.
```bash
cp .env.example .env
```
생성된 `.env` 파일을 열고, `docker-compose.yml`에서 사용할 데이터베이스 관련 환경변수들을 설정합니다. 최소한 아래 값들이 필요합니다.
- `DB_ROOT_PASSWORD`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

#### 2. Docker 컨테이너 빌드 및 실행
프로젝트 루트 디렉토리에서 아래 명령어를 실행하여 Docker 컨테이너를 빌드하고 백그라운드에서 실행합니다.
```bash
docker-compose up --build -d
```

#### 3. Composer 의존성 설치
실행 중인 `php` 컨테이너에 접속하여 `composer install` 명령을 실행합니다.
```bash
docker-compose exec php composer install
```

#### 4. 데이터베이스 초기화
데이터베이스 스키마와 초기 데이터를 로드해야 합니다. `phpmyadmin`에 접속하거나 DB 클라이언트를 사용하여 `database/schema.sql` 파일의 내용을 실행하고, 필요에 따라 `database/seeds/` 디렉토리의 SQL 파일들을 실행합니다.

#### 5. 애플리케이션 접속
모든 설정이 완료되면 웹 브라우저에서 아래 주소로 접속하여 애플리케이션을 확인할 수 있습니다.
-   **애플리케이션**: `http://localhost:8080`
-   **phpMyAdmin**: Nginx 설정을 통해 `http://localhost:8080/phpMyAdmin/` 경로로 프록시됩니다.

---

## ⚙️ Docker 설정 상세

### `docker-compose.yml`
이 프로젝트는 Nginx, PHP, MariaDB, phpMyAdmin 컨테이너를 함께 실행합니다.
```yaml
version: '3.8'
services:
  nginx:
    image: nginx:latest
    container_name: intra_nginx
    environment:
      - TZ=Asia/Seoul
    ports:
      - "8080:80"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
      - ./www:/var/www/
      - ./nginx/logs:/var/log/nginx
    depends_on:
      - php
      - phpmyadmin
    networks:
      - intra_network
    restart: unless-stopped
  php:
    build: .
    container_name: intra_php
    environment:
      - TZ=Asia/Seoul
    volumes:
      - ./www:/var/www
      - ./php/php.ini:/usr/local/etc/php/php.ini
    networks:
      - intra_network
    restart: unless-stopped
  mariadb:
    image: mariadb:latest
    container_name: intra_mariadb
    environment:
      TZ: Asia/Seoul
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - ./mariadb/data:/var/lib/mysql
      - ./mariadb/conf.d:/etc/mysql/conf.d
    networks:
      - intra_network
    restart: unless-stopped
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: intra_phpmyadmin
    environment:
      PMA_HOST: mariadb
      PMA_PORT: 3306
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      UPLOAD_LIMIT: 2048M
      MEMORY_LIMIT: 2048M
    depends_on:
      - mariadb
    networks:
      - intra_network
    restart: unless-stopped
networks:
  intra_network:
    driver: bridge
```

### `Dockerfile`
PHP 8.2-FPM을 기반으로 하며, 필요한 PHP 확장 프로그램과 Composer를 설치합니다.
```dockerfile
# PHP 8.2 FPM 기반 이미지
FROM php:8.2-fpm

# 작업 디렉토리
WORKDIR /var/www/public

# 필수 패키지 및 PHP 확장 설치
RUN apt-get update && \
    apt-get install -y \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        zip \
        unzip \
        tzdata \
    && ln -sf /usr/share/zoneinfo/Asia/Seoul /etc/localtime \
    && echo "Asia/Seoul" > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        curl \
        gd \
        mbstring

# Composer 복사
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 기본 명령
CMD ["php-fpm"]
```

### Nginx 설정 (`nginx/conf.d/default.conf`)
PHP 요청을 `php` 컨테이너로 전달하고, phpMyAdmin으로의 프록시를 설정합니다.
```nginx
server {
    listen 80;
    server_name _;
    root /var/www/public;
    index index.php index.html;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # phpMyAdmin 프록시
    location ^~ /phpMyAdmin/ {
        proxy_pass http://phpmyadmin:80/;
        proxy_set_header Host $host;
        # (이하 생략)
    }

    location ~ /\. {
        deny all;
    }
}
```

### PHP 설정 (`php/php.ini`)
애플리케이션에 최적화된 PHP 설정을 정의합니다. (에러 로깅, 리소스 제한, 보안 설정 등)
```ini
; PHP 환경 기본 설정
engine = On
short_open_tag = Off
memory_limit = 128M
post_max_size = 8M
upload_max_filesize = 8M

; 에러 핸들링 및 로깅
display_errors = Off
log_errors = On

; 시간/문자셋 설정
date.timezone = "Asia/Seoul"
mbstring.internal_encoding = UTF-8

; OPCache (성능 최적화)
opcache.enable = 1
opcache.memory_consumption = 256
```