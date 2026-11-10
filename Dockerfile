# 1. Imagen base con PHP 8.3 FPM Alpine (Laravel 13)
FROM php:8.3-fpm-alpine

# 2. Instalar herramientas del sistema y librerías nativas
RUN apk add --no-cache \
    nginx \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    postgresql-dev

# 3. Instalar extensiones de PHP requeridas por Laravel y PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring bcmath gd zip opcache

# 4. Obtener Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Permitir que Composer corra como superusuario en contenedor
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www

# 5. Copiar el código del backend al contenedor
COPY backend/ /var/www

# 6. Instalar dependencias de Composer sin scripts previos
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# 7. Asegurar directorios de almacenamiento y permisos iniciales
RUN mkdir -p /var/www/storage/framework/cache/data \
             /var/www/storage/framework/sessions \
             /var/www/storage/framework/views \
             /var/www/storage/logs \
             /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# 8. Configurar Nginx para servir /var/www/public
RUN mkdir -p /etc/nginx/http.d && printf 'server {\n\
    listen 80;\n\
    index index.php index.html;\n\
    root /var/www/public;\n\
    client_max_body_size 50M;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}\n' > /etc/nginx/http.d/default.conf

# 9. Script de arranque resiliente para Render:
#    - Puerto dinámico ($PORT)
#    - Genera APP_KEY si falta
#    - Prepara caché, migraciones y re-asigna permisos a www-data antes de iniciar php-fpm
RUN printf '#!/bin/sh\n\
set -e\n\
if [ -n "$PORT" ]; then\n\
    sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf\n\
fi\n\
if [ ! -f .env ]; then\n\
    if [ -f .env.example ]; then cp .env.example .env; else touch .env; fi\n\
fi\n\
if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=base64:" .env; then\n\
    php artisan key:generate --force || true\n\
fi\n\
php artisan package:discover --ansi || true\n\
php artisan config:clear || true\n\
php artisan route:clear || true\n\
php artisan view:clear || true\n\
php artisan view:cache || true\n\
php artisan migrate --force || true\n\
touch /var/www/storage/logs/laravel.log\n\
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache\n\
chmod -R 777 /var/www/storage /var/www/bootstrap/cache\n\
php-fpm -D\n\
exec nginx -g "daemon off;"\n' > /start.sh \
    && chmod +x /start.sh

# 10. Exponer el puerto estándar
EXPOSE 80

CMD ["/start.sh"]
