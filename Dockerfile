# syntax=docker/dockerfile:1.7
FROM php:8.2-apache-bookworm

ARG APP_DIR=/var/www/html

ENV APP_DIR=${APP_DIR} \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR ${APP_DIR}

RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        sqlite3 \
        xml \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite headers expires \
    && sed -ri 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:8080>/g' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' \
        '<Directory "/var/www/html">' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        '<DirectoryMatch "^/var/www/html/(app|bootstrap|config|database|resources|routes|tests|vendor)(/|$)">' \
        '    Require all denied' \
        '</DirectoryMatch>' \
        '<Directory "/var/www/html/storage/framework">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/storage/logs">' \
        '    Require all denied' \
        '</Directory>' \
      > /etc/apache2/conf-available/laravel-security.conf \
    && a2enconf laravel-security \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
    && composer clear-cache

COPY . .

RUN rm -f .env \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

RUN printf '%s\n' \
    'memory_limit=512M' \
    'upload_max_filesize=64M' \
    'post_max_size=64M' \
    'max_execution_time=120' \
    'opcache.enable=1' \
    'opcache.enable_cli=0' \
    'opcache.validate_timestamps=0' \
    'opcache.memory_consumption=192' \
    'opcache.max_accelerated_files=20000' \
    'opcache.interned_strings_buffer=16' \
    > /usr/local/etc/php/conf.d/99-production.ini

EXPOSE 8080

CMD ["apache2-foreground"]
