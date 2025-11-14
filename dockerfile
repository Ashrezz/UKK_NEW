# Base image PHP
FROM php:8.2-fpm

# Install system dependencies + Node 20
RUN apt-get update && apt-get install -y \
    zip unzip git curl libonig-dev libzip-dev ca-certificates \
    curl gnupg && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    docker-php-ext-install pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies & build assets
RUN npm install --legacy-peer-deps --no-fund \
    && npm run build

# Serve Laravel
CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]
