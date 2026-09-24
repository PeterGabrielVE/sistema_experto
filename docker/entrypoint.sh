#!/bin/sh
set -e

cd /var/www/html

# One-off commands (e.g. "php artisan key:generate --show") skip the web boot steps.
if [ "$1" != "apache2-foreground" ]; then
    exec docker-php-entrypoint "$@"
fi

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with:" >&2
    echo "  docker compose run --rm --no-deps app php artisan key:generate --show" >&2
    echo "and put it in your .env file." >&2
    exit 1
fi

# Named volumes are created root-owned the first time they are mounted.
chown -R www-data:www-data storage bootstrap/cache public/patient

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
else
    php artisan optimize:clear > /dev/null
fi

exec docker-php-entrypoint "$@"
