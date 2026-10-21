#!/bin/sh
set -eu

APP_ROOT="${APP_ROOT:-/var/www/syspass}"

mkdir -p \
    "$APP_ROOT/app/config" \
    "$APP_ROOT/app/cache" \
    "$APP_ROOT/app/backup" \
    "$APP_ROOT/app/temp" \
    "$APP_ROOT/app/plugins" \
    /tmp/nginx/client_body \
    /tmp/nginx/proxy \
    /tmp/nginx/fastcgi \
    /tmp/nginx/uwsgi \
    /tmp/nginx/scgi

# Best-effort: named volumes may be created as root.
chown -R "$(id -u):$(id -g)" \
    "$APP_ROOT/app/config" \
    "$APP_ROOT/app/cache" \
    "$APP_ROOT/app/backup" \
    "$APP_ROOT/app/temp" \
    "$APP_ROOT/app/plugins" \
    /tmp/nginx 2>/dev/null || true

php-fpm --daemonize --pid /tmp/php-fpm.pid

exec nginx -g 'daemon off;'
