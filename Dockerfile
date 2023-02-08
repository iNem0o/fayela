# syntax=docker/dockerfile:1.4

FROM caddy:2.6-builder-alpine AS caddy_builder

RUN xcaddy build \
    --with github.com/baldinof/caddy-supervisor


FROM php:8.2-fpm-alpine

# caddy
COPY --from=caddy_builder --link /usr/bin/caddy /usr/bin/caddy
COPY --link ./Caddyfile /etc/Caddyfile


# phpfpm
RUN docker-php-ext-install bcmath

# set www-data user/group id
RUN echo http://dl-2.alpinelinux.org/alpine/edge/community/ >> /etc/apk/repositories \
    && apk --no-cache add shadow

COPY --link ./fpm-downloader/fpm.conf /usr/local/etc/php-fpm.d/fpm.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# app
COPY --link ./app /srv/app
COPY --link ./run_fayela.sh /run_fayela.sh

WORKDIR /srv/app

RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader \
    && chown -R www-data:www-data /srv/app \
    && mkdir -p /srv/data && chown -R www-data:www-data /srv/data \
    && chmod +x /run_fayela.sh


CMD ["/run_fayela.sh"]
