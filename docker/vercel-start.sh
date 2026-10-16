#!/bin/sh
set -eu

export DATABASE_URL="${DB_DATABASE_URL_UNPOOLED:-${DATABASE_URL_UNPOOLED:-${POSTGRES_URL_NON_POOLING:-${DB_DATABASE_URL:-${DATABASE_URL:-${POSTGRES_URL:-}}}}}}"

if [ -n "$DATABASE_URL" ]; then
    php artisan migrate --force --no-interaction
    php artisan db:seed --class=DedicatedTenantSeeder --force --no-interaction
fi

exec frankenphp run --config /etc/frankenphp/Caddyfile
