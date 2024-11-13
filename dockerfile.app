# Start from PHP 8.1 with Apache
FROM php:8.1.0-apache
WORKDIR /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install libraries and extensions
RUN apt-get update -y && apt-get install -y \
    libicu-dev \
    libmariadb-dev \
    unzip zip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    curl \
    && apt-get clean

# Install PHP extensions
RUN docker-php-ext-install gettext intl pdo_mysql zip

# Install GD extension with JPEG and FreeType support
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Apache Configuration for Laravel
RUN echo "<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n" > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Set Apache Document Root to Laravel public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Set ServerName to suppress warning
RUN echo "ServerName localhost" | tee /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Copy the Laravel project to the container
COPY . /var/www/html/

# Set appropriate permissions for storage and bootstrap/cache directories
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install MongoDB PHP extension
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Run Composer install to set up Laravel dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose port 80 for Apache
EXPOSE 80

# Start the Apache server (entry point for container)
CMD ["apache2-foreground"]
