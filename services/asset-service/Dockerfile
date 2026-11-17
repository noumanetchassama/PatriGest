
FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    bcmath \
    pcntl \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Répertoire de travail
WORKDIR /var/www

# Copier le code Laravel
COPY . .

# Installer OPcache (compilation locale, aucune dépendance réseau)
RUN docker-php-ext-install opcache \
    && docker-php-ext-enable opcache

# Installer les dépendances PHP
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# The dashboard fans out several API requests at once.
RUN sed -i 's/^pm.max_children = 5$/pm.max_children = 8/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^pm.min_spare_servers = 1$/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^pm.max_spare_servers = 3$/pm.max_spare_servers = 6/' /usr/local/etc/php-fpm.d/www.conf \
    && grep -q '^pm.max_children = 8$' /usr/local/etc/php-fpm.d/www.conf \
    && grep -q '^pm.min_spare_servers = 2$' /usr/local/etc/php-fpm.d/www.conf \
    && grep -q '^pm.max_spare_servers = 6$' /usr/local/etc/php-fpm.d/www.conf

# Permissions Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
