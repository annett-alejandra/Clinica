FROM php:8.3-cli-alpine

# Instalar dependencias del sistema y librerías PostgreSQL
RUN apk add --no-cache \
    postgresql-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    bash

# Instalar extensiones de PHP requeridas por Laravel y PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Instalar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Exponer el puerto de Laravel
EXPOSE 8000

# Script de entrada para preparación de entorno
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

