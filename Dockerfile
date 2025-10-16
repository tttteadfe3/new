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