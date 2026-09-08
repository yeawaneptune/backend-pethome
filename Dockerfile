FROM php:8.2-apache

# Install PHP extensions needed for TiDB/MySQL SSL connection
RUN apt-get update && apt-get install -y \
    libssl-dev \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy backend files into Apache document root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy Apache virtual host config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
