FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git curl libonig-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql zip

# Upgrade Node & npm ke versi yang kompatibel
RUN npm install -g npm@latest \
    && npm install -g n \
    && n 20.29.1

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies & build assets
RUN npm install --legacy-peer-deps --no-fund \
    && npm run build

CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]
