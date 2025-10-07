FROM php:8.5-rc-apache

# Install system dependencies required for GD
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with support for JPEG and FreeType
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql gd
# mbstring

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy the application source code
# COPY src/ .

# Create uploads directory and set permissions
RUN mkdir -p uploads && chown -R www-data:www-data uploads

# Expose the port the app runs on
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]