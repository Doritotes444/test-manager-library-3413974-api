FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    mysql-client \
    wget

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs || true

COPY . .
RUN composer dump-autoload --optimize || true

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/covers \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD ["sh", "./entrypoint.sh"]