# Use official PHP 8.4 FPM image
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copy application files
COPY . .

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Install PHP dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader

# Expose port 8080 for Cloud Run
EXPOSE 8080

# Start PHP-FPM
CMD ["php-fpm"]