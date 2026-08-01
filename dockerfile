FROM php:8.2-apache

# 1. Install PostgreSQL client libraries and PHP PDO extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Enable Apache rewrite module
RUN a2enmod rewrite

# 3. Copy all project files to Apache default web folder
COPY . /var/www/html/

# 4. Set permissions so Apache can read all files
RUN chown -R www-data:www-data /var/www/html

# 5. Set working directory
WORKDIR /var/www/html

# Expose standard web port
EXPOSE 80