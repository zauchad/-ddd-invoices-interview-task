FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Configure Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache for Symfony
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Copy composer files
COPY composer.json ./

# Install dependencies
RUN composer update --no-interaction --no-scripts --no-autoloader

# Copy application
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Create var directories and set permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/ && \
    chmod -R 775 var/
