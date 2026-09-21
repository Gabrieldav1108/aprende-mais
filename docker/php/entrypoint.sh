#!/bin/sh
set -e

cd /var/www/html

set_env() {
    key="$1"
    value="$2"

    if grep -qE "^#? ?${key}=" .env; then
        sed -i -E "s|^#? ?${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

if [ ! -f .env ]; then
    echo "[entrypoint] Creating .env from .env.example"
    cp .env.example .env

    set_env APP_URL "${APP_URL:-http://localhost:8000}"
    set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
    set_env DB_HOST "${DB_HOST:-db}"
    set_env DB_PORT "${DB_PORT:-3306}"
    set_env DB_DATABASE "${DB_DATABASE:-aprendemais}"
    set_env DB_USERNAME "${DB_USERNAME:-aprendemais}"
    set_env DB_PASSWORD "${DB_PASSWORD:-secret}"
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installing composer dependencies"
    composer install --no-interaction --prefer-dist
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    echo "[entrypoint] Generating application key"
    php artisan key:generate --force --no-interaction
fi

echo "[entrypoint] Running migrations"
php artisan migrate --force --no-interaction

echo "[entrypoint] Seeding roles and permissions"
php artisan db:seed --class=RolesAndPermissionsSeeder --force --no-interaction

if ! grep -qE '^APP_ENV=production' .env; then
    echo "[entrypoint] Seeding default users"
    php artisan db:seed --class=DefaultUsersSeeder --force --no-interaction
fi

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

touch /tmp/app-ready

exec "$@"
