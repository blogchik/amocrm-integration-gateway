FROM php:8.2-fpm-alpine

# Maintainer
LABEL maintainer="AmoCRM Gateway"

# Install dependencies
RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache

# Enable additional extensions
RUN apk add --no-cache \
    icu-dev \
    && docker-php-ext-install intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
