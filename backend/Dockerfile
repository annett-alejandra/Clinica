# 1. Imagen base con PHP 8.3 FPM Alpine (requerido por Laravel 13)
FROM php:8.3-fpm-alpine

# 2. Instalar herramientas del sistema y librerías
RUN apk add --no-cache \
    nginx \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    postgresql-dev

# 3. Instalar extensiones de PHP necesarias para Laravel y Postgres
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring bcmath gd opcache

# 4. Obtener Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 5. Copiar el código del repositorio al contenedor
COPY . /var/www

# 6. Instalar dependencias de producción sin paquetes de testing/dev
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Asignar permisos requeridos a storage y cache
RUN mkdir -p /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/logs /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 8. Configurar Nginx para que apunte directamente a /var/www/public y use la variable de puerto de Render ($PORT o 80)
RUN mkdir -p /etc/nginx/http.d && echo 'server { \
    listen 80; \
    index index.php index.html; \
    root /var/www/public; \
    client_max_body_size 50M; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/http.d/default.conf

# 9. Script de arranque: adapta el puerto si Render asigna $PORT, limpia cachés, corre migraciones y arranca PHP-FPM + Nginx
RUN printf '#!/bin/sh\n\
if [ -n "$PORT" ]; then\n\
    sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf\n\
fi\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
php artisan migrate --force || true\n\
php-fpm -D\n\
exec nginx -g "daemon off;"\n' > /start.sh \
    && chmod +x /start.sh

# 10. Exponer el puerto estándar HTTP
EXPOSE 80

CMD ["/start.sh"]
