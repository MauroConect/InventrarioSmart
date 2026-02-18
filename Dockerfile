# ============================================
# STAGE 1: Build - Compilar assets con Node.js
# ============================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copiar package.json
COPY package.json ./

# Instalar dependencias
# Usar --legacy-peer-deps para resolver conflictos de versiones
# Si tienes package-lock.json, cópialo antes de ejecutar este build
# Si no lo tienes, npm install lo generará automáticamente
RUN npm install --prefer-offline --no-audit --progress=false --legacy-peer-deps

# Copiar archivos fuente
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources

# Compilar assets para producción
RUN npm run build

# ============================================
# STAGE 2: PHP Runtime - Solo PHP, sin Node.js
# ============================================
FROM php:8.2-fpm-alpine

# Instalar solo dependencias esenciales del sistema
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

# Copiar archivos de la aplicación (como root primero)
COPY . /var/www

# Copiar assets compilados desde el stage de build
COPY --from=node-builder /app/public/build /var/www/public/build

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
