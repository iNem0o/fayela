#!/bin/sh


USER_ID="${USER_ID:-1000}"
GROUP_ID="${GROUP_ID:-1000}"


echo " === switching www-data to USER_ID: ${USER_ID} and GROUP_ID: ${GROUP_ID}"

usermod -u "${USER_ID}" www-data
groupmod -g "${GROUP_ID}" www-data


./fayela banner

/usr/bin/caddy run --config /etc/Caddyfile