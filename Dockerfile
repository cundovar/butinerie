FROM wordpress:6.8.2-apache

# Copy configuration files
COPY wp-config.php /var/www/html/wp-config.php

# Create WordPress completely installed state
RUN touch /var/www/html/.htaccess
RUN mkdir -p /var/www/html/wp-content
RUN touch /var/www/html/wp-content/index.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/

# Disable WordPress auto-initialization by marking as installed
ENV WORDPRESS_DB_HOST=placeholder
ENV WORDPRESS_DB_NAME=placeholder
ENV WORDPRESS_DB_USER=placeholder
ENV WORDPRESS_DB_PASSWORD=placeholder