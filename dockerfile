FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install
COPY composer.json composer.lock ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"
RUN composer install --no-dev --optimize-autoloader

# Copy app files
COPY . .

# Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Generate key
RUN php artisan key:generate

# Expose port 8080
EXPOSE 8080

# Start Laravel built-in server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]