FROM php:8.1-apache

# Install required dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    wget \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli zip

# Install Composer
RUN wget https://getcomposer.org/installer -O composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm composer-setup.php

# Copy the application files
COPY . /var/www/html

# Copy start.sh into the container
COPY start.sh /usr/local/bin/start.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod +x /usr/local/bin/start.sh

# Set working directory
WORKDIR /var/www/html

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Enable Apache rewrite module
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# Run Apache with start.sh
CMD ["start.sh"]
