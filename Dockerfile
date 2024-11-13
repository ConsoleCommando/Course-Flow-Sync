# Use the official PHP-FPM image
FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y libzip-dev unzip curl libpq-dev nginx firefox-esr wget gnupg xvfb xclip && \
    docker-php-ext-install zip && \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Selenium and related dependencies (including geckodriver)
RUN wget https://github.com/mozilla/geckodriver/releases/download/v0.33.0/geckodriver-v0.33.0-linux64.tar.gz && \
    tar -xvzf geckodriver-v0.33.0-linux64.tar.gz && \
    mv geckodriver /usr/local/bin/

# Install Python and pip for Selenium and pyperclip
RUN apt-get install -y python3 python3-pip && \
    pip3 install selenium pyperclip --break-system-packages

# Set the working directory for PHP
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Install PHP dependencies
RUN composer install

# Copy the NGINX configuration file
COPY nginx.conf /etc/nginx/sites-available/default

# Expose ports for NGINX and PHP-FPM
EXPOSE 80

# Start both NGINX and PHP-FPM
CMD service nginx start && php-fpm

RUN chmod +x /var/www/html/run-scraper-test.sh