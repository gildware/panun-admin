#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  storage/app/public \
  storage/oauth-keys \
  bootstrap/cache

# Passport keys are not in the image; keep them on a named volume across rebuilds.
if [ -f storage/oauth-keys/oauth-private.key ] && [ -f storage/oauth-keys/oauth-public.key ]; then
  cp -f storage/oauth-keys/oauth-private.key storage/oauth-private.key
  cp -f storage/oauth-keys/oauth-public.key storage/oauth-public.key
elif [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
  php artisan passport:keys --force || true
  if [ -f storage/oauth-private.key ] && [ -f storage/oauth-public.key ]; then
    cp -f storage/oauth-private.key storage/oauth-keys/oauth-private.key
    cp -f storage/oauth-public.key storage/oauth-keys/oauth-public.key
  fi
else
  cp -f storage/oauth-private.key storage/oauth-keys/oauth-private.key
  cp -f storage/oauth-public.key storage/oauth-keys/oauth-public.key
fi

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true
chmod 600 storage/oauth-private.key storage/oauth-public.key 2>/dev/null || true
chmod 600 storage/oauth-keys/oauth-private.key storage/oauth-keys/oauth-public.key 2>/dev/null || true

if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Only cache config when the app key exists (set via Dokploy env).
if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec "$@"
