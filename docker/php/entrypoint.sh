#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Uygulama anahtarı kalıcı volume'da saklanır; konteyner yeniden oluşturulsa da
# oturumlar ve CSRF token'ları geçersiz olmaz.
KEY_FILE=/var/www/storage/app/app.key
if [ ! -s "$KEY_FILE" ]; then
    php artisan key:generate --show > "$KEY_FILE"
fi
export APP_KEY="$(cat "$KEY_FILE")"

echo "Veritabanı bekleniyor..."
until php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' >/dev/null 2>&1; do
    sleep 2
done

php artisan app:setup
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
