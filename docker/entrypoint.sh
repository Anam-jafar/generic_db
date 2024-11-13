#!/bin/bash

# Install composer dependencies if not already installed
if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Copy .env file if it doesn't exist
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Start PHP-FPM in the foreground
php-fpm

# Start Nginx in the foreground (corrected 'daemon' flag)
nginx -g "daemon off;"
