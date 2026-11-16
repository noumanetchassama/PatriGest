# ==========================================
# 1. Dépendances PHP des deux applications
# ==========================================
FROM php:8.3-fpm-bookworm AS php-build

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql bcmath pcntl zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build/auth
COPY services/auth-service/ ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

WORKDIR /build/asset
COPY services/asset-service/ ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ==========================================
# 2. Compilation frontend avec Vite
# ==========================================
FROM node:22-bookworm-slim AS assets-build

WORKDIR /build/auth
COPY services/auth-service/package*.json ./
RUN npm ci
COPY services/auth-service/ ./
RUN npm run build

WORKDIR /build/asset
COPY services/asset-service/package*.json ./
RUN npm ci
COPY services/asset-service/ ./
RUN npm run build

# ==========================================
# 3. Image finale : Nginx + PHP-FPM
# ==========================================
FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y \
    nginx supervisor libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql bcmath pcntl zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=php-build /build/auth /var/www/auth
COPY --from=php-build /build/asset /var/www/asset

COPY --from=assets-build /build/auth/public/build /var/www/auth/public/build
COPY --from=assets-build /build/asset/public/build /var/www/asset/public/build

COPY infrastructure/nginx/render.conf /etc/nginx/conf.d/default.conf
COPY infrastructure/php-fpm/auth.conf /usr/local/etc/php-fpm-auth.conf
COPY infrastructure/php-fpm/asset.conf /usr/local/etc/php-fpm-asset.conf
COPY infrastructure/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /run/nginx /var/log/supervisor \
    /var/www/auth/storage/framework/cache \
    /var/www/auth/storage/framework/sessions \
    /var/www/auth/storage/framework/views \
    /var/www/asset/storage/framework/cache \
    /var/www/asset/storage/framework/sessions \
    /var/www/asset/storage/framework/views \
    && chown -R www-data:www-data \
       /var/www/auth/storage /var/www/auth/bootstrap/cache \
       /var/www/asset/storage /var/www/asset/bootstrap/cache \
    && chmod -R ug+rwX \
       /var/www/auth/storage /var/www/auth/bootstrap/cache \
       /var/www/asset/storage /var/www/asset/bootstrap/cache
       CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]