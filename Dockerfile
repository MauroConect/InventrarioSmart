FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    pkg-config \
    gnupg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user for Laravel application
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Set permissions for storage and bootstrap/cache
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} && \
    mkdir -p /var/www/storage/logs && \
    mkdir -p /var/www/bootstrap/cache

# Copy application files
COPY --chown=www:www . /var/www

# Change to www user
USER www

# Configure Composer
RUN composer global config process-timeout 600 && \
    composer global config preferred-install dist

# Expose port 9000
EXPOSE 9000

# Start php-fpm
CMD ["php-fpm"]
