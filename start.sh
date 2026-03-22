#!/bin/bash
set -e

# Initialize MariaDB data directory if needed
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "Initializing MariaDB data directory..."
        mysql_install_db --user=mysql --datadir=/var/lib/mysql
        fi

        # Start MariaDB in background
        echo "Starting MariaDB..."
        mysqld_safe --user=mysql --datadir=/var/lib/mysql &

        # Wait for MySQL/MariaDB to be ready
        echo "Waiting for MariaDB to start..."
        for i in $(seq 1 30); do
            if mysqladmin ping --silent 2>/dev/null; then
                    echo "MariaDB is ready"
                            break
                                fi
                                    sleep 1
                                        echo "Attempt $i/30..."
                                        done

                                        # Create database and import schema if not exists
                                        mysql -e "CREATE DATABASE IF NOT EXISTS practice CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

                                        # Check if tables already exist
                                        TABLE_COUNT=$(mysql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='practice';" -sN 2>/dev/null || echo "0")

                                        if [ "$TABLE_COUNT" -eq "0" ]; then
                                            echo "Importing database schema..."
                                                mysql practice < /var/www/html/database_utf8.sql
                                                    echo "Database imported successfully"
                                                    else
                                                        echo "Database already initialized (${TABLE_COUNT} tables found)"
                                                        fi

                                                        # Set environment variables for PHP
                                                        export MYSQLHOST=127.0.0.1
                                                        export MYSQLDATABASE=practice
                                                        export MYSQLUSER=root
                                                        export MYSQLPASSWORD=
                                                        export MYSQLPORT=3306

                                                        PORT=${PORT:-8080}
                                                        echo "Starting PHP server on port $PORT..."
                                                        cd /var/www/html
                                                        exec php -S 0.0.0.0:$PORT
