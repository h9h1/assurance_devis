# ── Stage 1 : Builder ────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS builder

# Extensions PHP nécessaires
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev icu-dev oniguruma-dev \
    git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo pdo_mysql \
    gd zip intl mbstring opcache \
    exif pcntl bcmath

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier les fichiers de dépendances en premier (cache Docker layer)
COPY composer.json composer.lock ./

# Installer les dépendances sans les devDependencies
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copier le reste du code
COPY . .

# Générer l'autoloader optimisé
RUN composer dump-autoload --optimize --no-dev

# ── Stage 2 : Production ──────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS app

# Extensions PHP
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo pdo_mysql \
    gd zip intl mbstring opcache \
    exif pcntl bcmath

# Config PHP optimisée pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/php.ini     $PHP_INI_DIR/conf.d/app.ini
COPY docker/php/www.conf    /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# Copier depuis le builder
COPY --from=builder /var/www/html /var/www/html

# Permissions
RUN chown -R www-data:www-data var/ public/ \
    && chmod -R 775 var/

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php-fpm -t || exit 1

EXPOSE 9000

CMD ["php-fpm"]
