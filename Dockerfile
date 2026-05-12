FROM php:8.4-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gettext-base \
        git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd mbstring pcntl pdo_pgsql pgsql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY inmobiliaria-saas/composer.json inmobiliaria-saas/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

COPY inmobiliaria-saas/package.json inmobiliaria-saas/package-lock.json ./
RUN npm ci

COPY inmobiliaria-saas ./
RUN npm run build \
    && composer dump-autoload --optimize \
    && chmod +x railway/init-app.sh railway/run-worker.sh railway/run-cron.sh docker/start-container.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY inmobiliaria-saas/docker/apache.conf /etc/apache2/sites-available/000-default.conf

CMD ["docker/start-container.sh"]
