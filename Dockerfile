# syntax=docker/dockerfile:1.4

FROM caddy:2.6-builder-alpine AS caddy_builder

RUN xcaddy build \
    --with github.com/baldinof/caddy-supervisor


FROM php:8.2-fpm-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install bcmath

RUN mkdir -p /srv/app && chown -R www-data:www-data /srv/app \
    && mkdir -p /srv/data && chown -R www-data:www-data /srv/data
COPY --link ./fpm-downloader/fpm.conf /usr/local/etc/php-fpm.d/fpm.conf
COPY --link ./app /srv/app


COPY --from=caddy_builder --link /usr/bin/caddy /usr/bin/caddy
COPY --link ./Caddyfile /etc/Caddyfile
CMD ["/usr/bin/caddy", "run", "--config", "/etc/Caddyfile"]

WORKDIR /srv/app
