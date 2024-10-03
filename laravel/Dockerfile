# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Update the package lists and install required dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    cron && \
    docker-php-ext-install pdo pdo_mysql zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer (official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set the working directory inside the container
WORKDIR /var/www

# Copy all files from the local directory to the container's working directory
COPY . .

# Ensure the correct file permissions
RUN chown -R www-data:www-data /var/www

# Update Apache's document root to use the Laravel 'public' folder
RUN sed -i 's#/var/www/html#/var/www/public#' /etc/apache2/sites-available/000-default.conf

# Add cron job to run Laravel scheduler every minute for testing
RUN echo "* * * * * www-data php /var/www/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel_scheduler

# Give execution rights to the cron job
RUN chmod 0644 /etc/cron.d/laravel_scheduler

# Apply the cron job
RUN crontab /etc/cron.d/laravel_scheduler

# Create the cron log file
RUN touch /var/log/cron.log

# Expose port 80
EXPOSE 80

# Start both cron and Apache together
CMD ["sh", "-c", "cron && apache2-foreground"]
