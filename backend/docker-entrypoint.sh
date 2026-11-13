#!/bin/sh
set -e

# Asegurar directorios de almacenamiento, caché de Laravel y OPcache en disco nativo Linux
mkdir -p /tmp/opcache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 777 /tmp/opcache storage bootstrap/cache

# Crear .env a partir de .env.example si no existe
if [ ! -f .env ]; then
    echo "Configurando archivo .env para el entorno Docker..."
    cp .env.example .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' .env
    sed -i 's/DB_USERNAME=/DB_USERNAME=postgres/' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=postgres/' .env
fi

# Instalar dependencias de Composer en volumen nativo Linux si falta vendor/autoload.php
if [ ! -f vendor/autoload.php ]; then
    echo "Instalando paquetes de Composer en volumen nativo Linux de alta velocidad..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar clave de aplicación si no está asignada
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generando clave de cifrado de Laravel..."
    php artisan key:generate --force
fi

# Limpiar cachés para reflejar cambios inmediatos de código en desarrollo local
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Iniciar el servidor
exec "$@"
