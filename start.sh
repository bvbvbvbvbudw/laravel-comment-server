#!/bin/bash
composer install
chmod -R 775 /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
chown -R www-data:www-data /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
# Run migrations
php artisan migrate --force
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
# Generate application key
php artisan key:generate
# Create storage link
php artisan storage:link
php artisan websockets:serve &
# Start Apache
exec "apache2-foreground"
