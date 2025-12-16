# Use PHP 8.2 with Apache as base image
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set the working directory
WORKDIR /app

# Copy application files
COPY . /app/

# Create uploads directory and set permissions
RUN mkdir -p /app/uploads \
    && chown -R www-data:www-data /app \
    && chown -R www-data:www-data /app/uploads \
    && chmod -R 755 /app \
    && chmod -R 755 /app/uploads \
    # Fallback permission fix for www-data user (uid 33)
    && chown -R 33:33 /app/uploads

# Configure PHP upload settings
RUN { \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'memory_limit = 128M'; \
    echo 'max_execution_time = 300'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Create a verification script
RUN echo '#!/bin/bash\n\
if [ ! -d "/app/uploads" ]; then\n\
    mkdir -p /app/uploads\n\
fi\n\
chown -R www-data:www-data /app/uploads\n\
chmod -R 755 /app/uploads\n\
chown -R 33:33 /app/uploads\n\
apache2-foreground' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Configure Apache
RUN { \
    echo '<VirtualHost *:3012>'; \
    echo '    ServerAdmin webmaster@localhost'; \
    echo '    DocumentRoot /app'; \
    echo '    DirectoryIndex index.php index.php'; \
    echo '    <Directory /app>'; \
    echo '        Options Indexes FollowSymLinks'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf \
    && echo 'Listen 3012' > /etc/apache2/ports.conf

# Expose port 3012
EXPOSE 3012

# Set the entrypoint to our verification script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
