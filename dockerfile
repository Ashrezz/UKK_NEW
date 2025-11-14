# Stage 1: Build assets
FROM node:20 AS build
WORKDIR /app

# Copy package.json & package-lock.json
COPY package.json package-lock.json* ./

# Install Node dependencies
RUN npm install --legacy-peer-deps --no-fund

# Copy seluruh project
COPY . .

# Build assets + fix manifest
RUN npm run build

# Stage 2: PHP / Laravel
FROM php:8.2-fpm

# Install PHP extensions & dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git curl libonig-dev libzip-dev ca-certificates \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy project + hasil build assets
COPY --from=build /app /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Clear Laravel cache
RUN php artisan config:clear \
    && php artisan cache:clear \
    && php artisan view:clear \
    && php artisan migrate --force \
    && php artisan db:seed --force \
    && php artisan app:generate-placeholder-blobs --force \
    && php artisan app:normalize-blob-records \
    && php artisan app:clear-bukti-path-use-blob
# Expose port & run PHP server
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]
