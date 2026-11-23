#!/bin/sh
set -eu

port="${PORT:-80}"
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
attempt_limit=60
if [ "${VERCEL:-}" = "1" ]; then
    attempt_limit=15
fi
until php -r '
$db = mysqli_init();
$ssl = filter_var(getenv("DB_SSL") ?: "false", FILTER_VALIDATE_BOOLEAN);
$flags = $ssl ? MYSQLI_CLIENT_SSL : 0;
if ($ssl) {
    $ca = getenv("DB_SSL_CA") ?: "/etc/ssl/certs/ca-certificates.crt";
    $db->ssl_set(null, null, $ca, null, null);
}
$db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
$connected = $db->real_connect(
    getenv("DB_HOST") ?: "127.0.0.1",
    getenv("DB_USER") ?: "root",
    getenv("DB_PASS") ?: "",
    getenv("DB_NAME") ?: "baketrack",
    (int) (getenv("DB_PORT") ?: 3306),
    null,
    $flags
);
exit($connected ? 0 : 1);
'
do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$attempt_limit" ]; then
        echo "Database did not become available after $((attempt_limit * 2)) seconds." >&2
        exit 1
    fi
    sleep 2
done

php /var/www/html/scripts/migrate.php

exec apache2-foreground
