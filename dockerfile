# Base image
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader



# Expose port (Railway uses 8080)
EXPOSE 8080

# Start Laravel server using PORT env variable
CMD ["sh", "-c", "php artisan serve --host=mainline.proxy.rlwy.net --port=${PORT:-53469}"]