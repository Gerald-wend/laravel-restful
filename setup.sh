#!/usr/bin/env bash

if [ ! -d "vendor" ]; then
    composer install
fi

if [[ "${ARTISAN_MIGRATE}" = 1 ]]; then
    php artisan migrate
    if [[ "${ARTISAN_SEED}" = 1 ]]; then
        php artisan db:seed
    fi
fi

if [ $# -gt 0 ];then
    exec gosu $WWWUSER "$@"
else
    if [[ "${ARTISAN_SERVE}" = 1 ]]; then
        php artisan serve --host 0.0.0.0 --port 8000
    fi
fi