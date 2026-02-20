# Dockerfile para Producción - Solo Laravel/Blade (sin React/Vite)
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    oniguruma \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario para Laravel
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Crear directorios necesarios
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} && \
    mkdir -p /var/www/storage/logs && \
    mkdir -p /var/www/bootstrap/cache

WORKDIR /var/www

# Copiar archivos de la aplicación
COPY . /var/www

# Asegurar permisos correctos antes de cambiar de usuario
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Cambiar a usuario www
USER www

# Configurar Composer para producción
RUN composer global config process-timeout 600 && \
    composer global config preferred-install dist

# Exponer puerto
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
