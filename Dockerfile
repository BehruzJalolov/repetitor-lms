# ==============================================================================
# 1-bosqich: Frontend (Tailwind CSS v4 + Vite) dizayn fayllarini yig'ish
# ==============================================================================
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

# ==============================================================================
# 2-bosqich: PHP 8.3 + Apache Production Server (Render.com uchun moslangan)
# ==============================================================================
FROM php:8.3-apache

# Tizim kutubxonalari va PHP kengaytmalarini o'rnatish (MySQL, PostgreSQL, SQLite, GD, Zip, Intl)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        gd \
        zip \
        intl \
        mbstring \
        bcmath \
        pcntl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache rewrite va headers modullarini yoqish + DocumentRoot ni /public ga qaratish
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Video darslar (MP4/MOV/WEBM) va PDF fayllar yuklanishi uchun PHP limitlarini oshirish
RUN { \
    echo "upload_max_filesize = 512M"; \
    echo "post_max_size = 512M"; \
    echo "memory_limit = 512M"; \
    echo "max_execution_time = 300"; \
    echo "max_input_time = 300"; \
    echo "opcache.enable = 1"; \
    echo "opcache.validate_timestamps = 0"; \
} > /usr/local/etc/php/conf.d/repetitor-lms.ini

# Composer o'rnatish
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer paketlarini o'rnatish (keshdan unumli foydalanish uchun avval composer.json ko'chiriladi)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Loyiha kodini va 1-bosqichda yig'ilgan frontend (public/build) fayllarini ko'chirish
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Autoload fayllarini optimallashtirish
RUN composer dump-autoload --optimize --no-dev --no-interaction

# Entrypoint skriptini tayyorlash (Windows CRLF belgilarini tozalash bilan)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
