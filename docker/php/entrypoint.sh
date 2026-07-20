#!/bin/sh
set -e

cd /var/www/html

# Pastikan .env ada (compose meng-inject via env_file/environment; ini fallback).
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

# Generate APP_KEY bila belum ada.
if [ -z "$(php artisan tinker --execute='echo config("app.key");' 2>/dev/null)" ]; then
    php artisan key:generate --force || true
fi

# Pastikan folder writable (jaga-jaga bila volume storage baru).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# public/temp: tempat aplikasi menulis docx sementara saat generate dokumen.
mkdir -p public/temp/templates public/temp/documentation-photo
chown -R www-data:www-data storage bootstrap/cache public/temp || true

# Symlink storage publik (idempoten).
php artisan storage:link || true

# Tunggu database siap lalu migrasi.
# RUN_MIGRATIONS=false untuk melewati (mis. saat scaling worker terpisah).
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Menunggu database siap..."
    tries=0
    until php artisan migrate --force 2>/dev/null; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "Database tidak siap setelah 30 percobaan, lanjut tanpa migrasi."
            break
        fi
        echo "DB belum siap, retry ($tries)..."
        sleep 3
    done
fi

# Cache config/route/view untuk performa (production-like).
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
