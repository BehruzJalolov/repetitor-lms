#!/usr/bin/env sh
set -e

cd /var/www/html

# 1. Render dinamik portini (default: 10000) Apache uchun sozlash
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# 2. .env fayli bo'lmasa .env.example dan nusxa olish
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 3. Persistent storage (doimiy disk) ichidagi barcha kerakli papkalarni yaratish
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/database \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database

# 4. Production muhitda APP_DEBUG=false bo'lishini qat'iy ta'minlash
if [ "${APP_ENV:-production}" = "production" ]; then
    export APP_DEBUG="false"
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|g" .env
fi

# 5. Ma'lumotlar bazasi ulanishini tekshirish:
# Agar DATABASE_URL yoki DB_URL berilgan bo'lsa (masalan Render PostgreSQL), avtomatik pgsql ishlatiladi.
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
    export DB_URL="${DATABASE_URL}"
fi

if [ -n "${DB_URL:-}" ] && [ "${DB_CONNECTION:-}" = "" ]; then
    export DB_CONNECTION="pgsql"
fi

# Agar SQLite ishlatilsa, uni persistent storage (/var/www/html/storage/database/database.sqlite) ichida saqlaymiz
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    export DB_DATABASE="${DB_DATABASE:-/var/www/html/storage/database/database.sqlite}"
    if [ ! -f "${DB_DATABASE}" ]; then
        touch "${DB_DATABASE}"
    fi
fi

# 6. Doimiy APP_KEY tekshiruvi (persistent storage ichida kalitni saqlab qolish bilan)
PERSISTENT_KEY_FILE="/var/www/html/storage/app/private/.app_key"
if [ -z "${APP_KEY:-}" ]; then
    if [ -f "${PERSISTENT_KEY_FILE}" ]; then
        export APP_KEY="$(cat "${PERSISTENT_KEY_FILE}")"
    else
        php artisan key:generate --force --no-interaction
        GENERATED_KEY="$(grep '^APP_KEY=' .env | cut -d '=' -f2-)"
        printf '%s' "${GENERATED_KEY}" > "${PERSISTENT_KEY_FILE}"
        chmod 600 "${PERSISTENT_KEY_FILE}"
        export APP_KEY="${GENERATED_KEY}"
    fi
elif ! echo "${APP_KEY}" | grep -q "^base64:"; then
    VALID_KEY="base64:$(printf '%s' "${APP_KEY}" | sha256sum | cut -c1-32 | base64)"
    export APP_KEY="${VALID_KEY}"
fi
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|g" .env

# 7. Keshni tozalash va storage symlink yaratish
php artisan config:clear --no-interaction || true
php artisan storage:link --force --no-interaction || true

# 8. Migratsiya va boshlang'ich ma'lumotlarni yuklash
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction || true

# 9. Production tezligi uchun keshlash
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# 10. Fayl huquqlarini www-data ga berish
chown -R www-data:www-data storage bootstrap/cache database

# 11. Telegram Bot background listener (tugmalar va obuna tekshiruvi 24/7 ishlashi uchun)
(
    while true; do
        su -s /bin/sh www-data -c "php /var/www/html/artisan telegram:work --timeout=25" >/dev/null 2>&1 || true
        sleep 3
    done
) &

echo "🚀 Repetitor LMS Render serveri ${PORT}-portda ishga tushmoqda..."
exec apache2-foreground
