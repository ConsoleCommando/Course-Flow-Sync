# Use the official PHP-FPM image
FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y \
    libzip-dev unzip curl libpq-dev python3 python3-pip python3-dev \
    libgsf-1-114 libatk-bridge2.0-0 libgtk-3-0 libx11-xcb1 libgdk-pixbuf2.0-0 \
    libgbm1 gnupg wget ca-certificates nginx libasound2 libnss3 libxshmfence1 \
    fonts-liberation libu2f-udev libvulkan1 --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Copy the JDK folder from your local directory into the Docker container
COPY java /opt/java

# Set environment variables for Java
ENV JAVA_HOME=/opt/java
ENV PATH="${JAVA_HOME}/bin:${PATH}"

# Install PHP extensions and Composer
RUN docker-php-ext-install zip pdo pdo_mysql pdo_pgsql && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Selenium and BrowserMob Proxy
RUN pip3 install selenium browsermob-proxy --break-system-packages

# Install Google Chrome
RUN wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - && \
    sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list' && \
    apt-get update && \
    apt-get install -y google-chrome-stable

# Set the working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Install PHP dependencies
RUN composer install

# Copy the NGINX configuration file
COPY nginx.conf /etc/nginx/sites-available/default

# Expose port 80
EXPOSE 80

# Expose port 8080
EXPOSE 8080

# Start both PHP-FPM and NGINX using a shell script
CMD service nginx start && php-fpm
