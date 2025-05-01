#!/bin/sh

set -e

echo "Setting up required storage folders and permissions..."

mkdir -p /var/www/html/storage/app/public

chown -R www-data:www-data /var/www/html/storage
chmod -R 777 /var/www/html/storage

echo "Starting Nginx and PHP-FPM..."
exec php-fpm &
exec nginx -g 'daemon off;'
