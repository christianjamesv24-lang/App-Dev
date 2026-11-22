FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libcurl4-openssl-dev libonig-dev unzip \
    && docker-php-ext-install curl mysqli mbstring \
    && printf 'upload_max_filesize=4M\npost_max_size=5M\n' > /usr/local/etc/php/conf.d/uploads.ini \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci --omit=dev --no-audit --no-fund

COPY . .
COPY uploads/products/default.svg /usr/local/share/baketrack-default.svg
COPY docker-entrypoint.sh /usr/local/bin/baketrack-entrypoint
RUN chmod +x /usr/local/bin/baketrack-entrypoint \
    && mkdir -p uploads/products \
    && chown -R www-data:www-data uploads

CMD ["baketrack-entrypoint"]
