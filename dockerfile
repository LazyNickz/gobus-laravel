FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libonig-dev libzip-dev libpng-dev \
    nginx supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip

# Copy app
WORKDIR /var/www/html
COPY . .

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Nginx config
COPY deploy/nginx.conf /etc/nginx/nginx.conf

# Supervisor config
COPY deploy/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

EXPOSE 80

CMD ["supervisord", "-n"]