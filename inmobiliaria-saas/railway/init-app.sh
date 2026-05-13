#!/usr/bin/env sh
set -e

if [ -z "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
    echo "Railway init error: configure DATABASE_URL or DB_URL with the Postgres service URL before deploying."
    echo "Example: DATABASE_URL=\${{Postgres.DATABASE_URL}} and DB_CONNECTION=pgsql"
    exit 1
fi

export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_URL="${DB_URL:-${DATABASE_URL:-}}"

echo "Railway init: clearing cached Laravel files"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Railway init: running database migrations"
php artisan migrate --force

echo "Railway init: caching views"
php artisan view:cache

if [ "${RAILWAY_CACHE_CONFIG:-false}" = "true" ]; then
    echo "Railway init: caching config"
    php artisan config:cache
fi

if [ "${RAILWAY_CACHE_ROUTES:-false}" = "true" ]; then
    echo "Railway init: caching routes"
    php artisan route:cache
fi

echo "Railway init: done"
