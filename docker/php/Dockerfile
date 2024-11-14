FROM php:8.1-fpm

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
    libzip-dev \
    git \
    curl \
    && apt-get clean

# Install PHP extensions
RUN docker-php-ext-install gettext intl pdo_mysql zip

# Install GD extension with JPEG and FreeType support
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Install MongoDB PHP extension
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Run Composer install to set up Laravel dependencies
WORKDIR /var/www/html
COPY . /var/www/html

# Set appropriate permissions for storage and bootstrap/cache directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
    
# Ensure permissions are correctly set for the storage and bootstrap/cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache


RUN composer install --no-interaction --prefer-dist --optimize-autoloader --optimize-autoloader --no-dev


