#!/bin/bash
set -e

# Start MySQL
service mysql start

# Wait for MySQL to be ready
echo "Waiting for MySQL to start..."
for i in {1..30}; do
    if mysqladmin ping -u root --silent 2>/dev/null; then
            echo "MySQL is ready"
                    break
                        fi
                            sleep 1
                            done

                            # Create database and import schema if not exists
                            mysql -u root <<EOF
                            CREATE DATABASE IF NOT EXISTS practice CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
                            EOF

                            # Check if tables already exist
                            TABLE_COUNT=$(mysql -u root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='practice';" -sN 2>/dev/null || echo "0")

                            if [ "$TABLE_COUNT" -eq "0" ]; then
                                echo "Importing database schema..."
                                    mysql -u root practice < /var/www/html/database_utf8.sql
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
