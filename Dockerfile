# Use the official PHP-FPM image
FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y libzip-dev unzip curl libpq-dev && \
    docker-php-ext-install zip && \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Install PHP dependencies
RUN composer install

# Install NGINX
RUN apt-get install -y nginx

# Copy the NGINX configuration file
COPY nginx.conf /etc/nginx/sites-available/default

# Expose port 80
EXPOSE 80

# Start both PHP-FPM and NGINX
CMD service nginx start && php-fpm
