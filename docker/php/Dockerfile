FROM php:8.3-fpm-alpine

RUN apk add --no-cache git unzip icu-dev libzip-dev \
 && docker-php-ext-install intl pdo_mysql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/app

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]