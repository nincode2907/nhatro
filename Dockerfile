# syntax=docker/dockerfile:1.7

FROM php:8.4-cli-alpine AS php-base

RUN apk add --no-cache \
        curl \
        freetype \
        libjpeg-turbo \
        libpng \
        libxml2 \
        libzip \
        oniguruma \
        sqlite-libs \
        su-exec \
    && apk add --no-cache --virtual .build-dependencies \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        dom \
        gd \
        mbstring \
        opcache \
        simplexml \
        xml \
        xmlreader \
        xmlwriter \
        zip \
    && apk del .build-dependencies \
    && php -r 'exit(extension_loaded("pdo_sqlite") && extension_loaded("sqlite3") && extension_loaded("gd") && extension_loaded("zip") ? 0 : 1);'

FROM node:22-alpine AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM php-base AS dependencies

WORKDIR /app

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY . .

COPY --from=frontend-build /app/public/build ./public/build

RUN composer dump-autoload \
    --classmap-authoritative \
    --no-dev \
    --no-interaction

FROM php-base AS application

WORKDIR /var/www/html

COPY --from=dependencies /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/nhatro-entrypoint

RUN addgroup -g 1000 app \
    && adduser -D -G app -u 1000 app \
    && sed -i 's/\r$//' /usr/local/bin/nhatro-entrypoint \
    && chmod +x /usr/local/bin/nhatro-entrypoint \
    && chown -R app:app /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=15s --timeout=3s --start-period=30s --retries=3 \
    CMD curl --fail --silent http://127.0.0.1:8000/up > /dev/null || exit 1

ENTRYPOINT ["nhatro-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
