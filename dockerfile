FROM php:8.2-fpm

# Install extension
RUN apt-get update && apt-get install -y \
    zip unzip git curl libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]
