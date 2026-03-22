FROM php:8.2-cli

# Install system dependencies and MySQL server
RUN apt-get update && apt-get install -y \
    default-mysql-server \
        default-mysql-client \
            && rm -rf /var/lib/apt/lists/*

            # Install PHP MySQL extension
            RUN docker-php-ext-install pdo pdo_mysql

            # Set working directory
            WORKDIR /var/www/html

            # Copy application files
            COPY . .

            # Make start script executable
            RUN chmod +x /var/www/html/start.sh

            # Expose port
            EXPOSE 8080

            # Start MySQL and PHP server
            CMD ["/var/www/html/start.sh"]
