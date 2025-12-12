# Stage 1: Base PHP-FPM image
FROM php:8.4-fpm

# Arguments
ARG APP_ENV=production

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    nginx \
    supervisor \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy Nginx and Supervisor configuration
COPY deploy/nginx.conf /etc/nginx/nginx.conf
COPY deploy/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Make storage and bootstrap/cache writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port (Railway sets $PORT)
EXPOSE 8080

# Run Supervisor to manage Nginx + PHP-FPM
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
