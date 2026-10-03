#!/bin/sh

set -eu

data_directory=/data
key_file="${data_directory}/app.key"
database_file="${DB_DATABASE:-${data_directory}/database.sqlite}"
database_parent="$(dirname "$database_file")"

mkdir -p "$data_directory" "$database_parent" /var/www/html/storage /var/www/html/bootstrap/cache
touch "$database_file"
chown -R app:app "$data_directory" /var/www/html/storage /var/www/html/bootstrap/cache
chown app:app "$database_parent" "$database_file"

if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s "$key_file" ]; then
        umask 077
        php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;' > "$key_file"
        chown app:app "$key_file"
    fi

    APP_KEY="$(cat "$key_file")"
    export APP_KEY
fi

if [ -z "${ADMIN_PASSWORD:-}" ]; then
    echo "ADMIN_PASSWORD is required. Set it in .env." >&2
    exit 1
fi

su-exec app php artisan config:clear --no-interaction
su-exec app php artisan migrate --force --no-interaction
su-exec app php artisan db:seed --class=AdminUserSeeder --force --no-interaction
su-exec app php artisan db:seed --class=DemoPropertySeeder --force --no-interaction

exec su-exec app "$@"
