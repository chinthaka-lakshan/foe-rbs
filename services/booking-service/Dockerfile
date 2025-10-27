# Dockerfile inside each Laravel service folder (e.g., auth-service/Dockerfile)
FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions (like pdo_pgsql)
RUN apk add --no-cache \
    postgresql-dev \
    # ... other tools like git, zip, etc.
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files (will be mounted by docker-compose, but good practice)
COPY . .

# Run composer install
RUN composer install --no-dev --optimize-autoloader

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]