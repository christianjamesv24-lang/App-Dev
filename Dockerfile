FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip \
    && docker-php-ext-install mysqli mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

COPY . .
COPY uploads/products/default.svg /usr/local/share/baketrack-default.svg
COPY docker-entrypoint.sh /usr/local/bin/baketrack-entrypoint
RUN chmod +x /usr/local/bin/baketrack-entrypoint \
    && mkdir -p uploads/products \
    && chown -R www-data:www-data uploads

CMD ["baketrack-entrypoint"]
