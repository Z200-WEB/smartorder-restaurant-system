FROM php:8.2-cli

# Install system dependencies and MariaDB server
RUN apt-get update && apt-get install -y \
    default-mysql-server \
        default-mysql-client \
            && rm -rf /var/lib/apt/lists/*

            # Install PHP extensions
            RUN docker-php-ext-install pdo pdo_mysql

            # Set working directory
            WORKDIR /var/www/html

            # Copy application files
            COPY . .

            # Create itemImages directory with write permissions
            RUN mkdir -p /var/www/html/itemImages && chmod 777 /var/www/html/itemImages

            # Make start script executable
            RUN chmod +x /var/www/html/start.sh

            # Expose port (Render uses PORT env variable)
            EXPOSE 10000

            # Start MySQL and PHP server
            CMD ["/var/www/html/start.sh"]
