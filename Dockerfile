# syntax=docker/dockerfile:1.7

FROM composer:2.8 AS dependencies

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY . .

RUN composer dump-autoload \
    --classmap-authoritative \
    --no-dev \
    --no-interaction

FROM php:8.4-cli-alpine AS application

RUN apk add --no-cache \
        curl \
        oniguruma \
        sqlite-libs \
        su-exec \
    && apk add --no-cache --virtual .build-dependencies \
        $PHPIZE_DEPS \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        mbstring \
        opcache \
    && apk del .build-dependencies \
    && php -r 'exit(extension_loaded("pdo_sqlite") && extension_loaded("sqlite3") ? 0 : 1);'

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
