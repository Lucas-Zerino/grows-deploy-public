FROM php:8.1-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    postgresql-dev \
    bash \
    curl \
    git \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    pcntl \
    sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create logs directory
RUN mkdir -p logs && chmod 777 logs

# Expose port
EXPOSE 9000

CMD ["php-fpm"]

