FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# Point document root at public/
RUN sed -i \
    's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf \
 && sed -i \
    '/<\/VirtualHost>/i \    <Directory /var/www/html/public>\n        AllowOverride All\n        Require all granted\n    </Directory>' \
    /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Copy source and install production dependencies
COPY . .
RUN composer install --no-dev --no-interaction --optimize-autoloader --quiet

# Render injects $PORT (default 10000); wire Apache to it
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
