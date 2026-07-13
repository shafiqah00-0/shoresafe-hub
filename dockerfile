FROM php:8.2-apache

# Install PostgreSQL extension for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy your project files to the server's web directory
COPY . /var/www/html/