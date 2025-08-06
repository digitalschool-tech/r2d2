# Use official PHP image as the base
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
# Install system dependencies (including libzip-dev for ext-zip)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    gnupg \
    libicu-dev \
    iputils-ping


# Install PHP extensions required for Laravel, including intl and zip
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip


# Install Node.js 20 and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Copy the existing application directory contents
COPY . /var/www

# Ensure .env.example exists and copy it to .env
RUN if [ -f .env.example ]; then cp .env.example .env; else echo "No .env.example found"; fi

# Set proper permissions for storage and bootstrap/cache directories
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Set custom PHP configurations
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini

# Expose port 9000 and start PHP-FPM server
EXPOSE 9000
CMD ["php-fpm"]