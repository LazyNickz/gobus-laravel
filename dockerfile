# =========================
# Base image
# =========================
FROM php:8.4-fpm

# =========================
# Set working directory
# =========================
WORKDIR /var/www/html

# =========================
# Install system dependencies
# =========================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    curl \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip

# =========================
# Install Composer
# =========================
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# =========================
# Copy project files
# =========================
COPY . .

# =========================
# Set permissions
# =========================
RUN chown -R www-data:www-data storage bootstrap/cache

# =========================
# Install PHP dependencies
# =========================
RUN composer install --no-dev --optimize-autoloader

# =========================
# Expose port for Cloud Run
# =========================
EXPOSE 8080

# =========================
# Laravel command for Cloud Run
# =========================
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]