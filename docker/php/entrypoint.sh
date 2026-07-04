#!/bin/sh
set -e

chown -R www-data:www-data /var/www/storage/videos 2>/dev/null || true
mkdir -p /var/www/backend/storage/app/livewire-tmp
chown -R www-data:www-data /var/www/backend/storage

exec docker-php-entrypoint "$@"
