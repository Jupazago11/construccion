#!/usr/bin/env sh
set -e

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
