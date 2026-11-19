#!/bin/sh
set -eu

port="${PORT:-10000}"
if ! printf '%s' "$port" | grep -Eq '^[0-9]+$'; then
    echo "PORT must be a number." >&2
    exit 1
fi

sed -ri "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p /var/www/html/uploads/products
if [ ! -f /var/www/html/uploads/products/default.svg ]; then
    cp /usr/local/share/baketrack-default.svg /var/www/html/uploads/products/default.svg
fi
chown -R www-data:www-data /var/www/html/uploads

attempt=0
until php -r '$db = @new mysqli(getenv("DB_HOST") ?: "127.0.0.1", getenv("DB_USER") ?: "root", getenv("DB_PASS") ?: "", getenv("DB_NAME") ?: "baketrack", (int) (getenv("DB_PORT") ?: 3306)); exit($db->connect_errno ? 1 : 0);'
do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "Database did not become available after 120 seconds." >&2
        exit 1
    fi
    sleep 2
done

exec apache2-foreground
