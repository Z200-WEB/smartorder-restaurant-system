#!/bin/bash
set -e

echo "=== SmartOrder Startup Script ==="

# Fix permissions for mysql data directory
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld
chmod 777 /var/run/mysqld

# Ensure itemImages directory exists and is writable
mkdir -p /var/www/html/itemImages
chmod 777 /var/www/html/itemImages

# Initialize MariaDB data directory if needed
if [ ! -d /var/lib/mysql/mysql ]; then
  echo "Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
      echo "MariaDB initialized."
      fi

      # Start MariaDB in background
      echo "Starting MariaDB daemon..."
      mysqld_safe --user=mysql --socket=/var/run/mysqld/mysqld.sock --datadir=/var/lib/mysql &

      # Wait for MariaDB to be ready
      echo "Waiting for MariaDB to be ready..."
      MYSQL_UP=0
      for i in $(seq 1 60); do
        if mysqladmin -u root --socket=/var/run/mysqld/mysqld.sock ping --silent 2>/dev/null; then
            echo "MariaDB is ready after ${i}s"
                MYSQL_UP=1
                    break
                      fi
                        sleep 1
                        done

                        if [ "$MYSQL_UP" -eq 0 ]; then
                          echo "ERROR: MariaDB did not start in time"
                            exit 1
                            fi

                            # Setup root user for TCP access (fix auth_socket issue in MariaDB)
                            echo "Configuring MariaDB root user for TCP access..."
                            mysql -u root --socket=/var/run/mysqld/mysqld.sock -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;"

                            # Create database if not exists
                            echo "Creating database..."
                            mysql -u root --socket=/var/run/mysqld/mysqld.sock -e "CREATE DATABASE IF NOT EXISTS practice CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

                            # Check if tables already exist
                            TABLE_COUNT=$(mysql -u root --socket=/var/run/mysqld/mysqld.sock -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='practice';" -sN 2>/dev/null || echo "0")

                            if [ "$TABLE_COUNT" -eq "0" ] 2>/dev/null || [ -z "$TABLE_COUNT" ]; then
                              echo "Importing database schema and seed data..."
                                mysql -u root --socket=/var/run/mysqld/mysqld.sock practice < /var/www/html/database_utf8.sql
                                  echo "Database imported successfully."
                                  else
                                    echo "Database already has ${TABLE_COUNT} tables. Running schema migration..."
                                      mysql -u root --socket=/var/run/mysqld/mysqld.sock practice -e "
                                          ALTER TABLE sCategory
                                                ADD COLUMN IF NOT EXISTS icon varchar(50) DEFAULT '🍽️',
                                                      ADD COLUMN IF NOT EXISTS sort_order int(11) DEFAULT 0;
                                                          ALTER TABLE sItem
                                                                ADD COLUMN IF NOT EXISTS description varchar(500) DEFAULT NULL,
                                                                      ADD COLUMN IF NOT EXISTS image_url varchar(500) DEFAULT NULL,
                                                                            ADD COLUMN IF NOT EXISTS is_popular int(11) DEFAULT 0,
                                                                                  ADD COLUMN IF NOT EXISTS is_new int(11) DEFAULT 0,
                                                                                        ADD COLUMN IF NOT EXISTS is_spicy int(11) DEFAULT 0,
                                                                                              ADD COLUMN IF NOT EXISTS sort_order int(11) DEFAULT 0;
                                                                                                  ALTER TABLE sManagement
                                                                                                        ADD COLUMN IF NOT EXISTS kitchen_state int(11) DEFAULT 0,
                                                                                                              ADD COLUMN IF NOT EXISTS estimated_minutes int(11) DEFAULT 15,
                                                                                                                    ADD COLUMN IF NOT EXISTS notes varchar(500) DEFAULT NULL;
                                                                                                                        ALTER TABLE sOrder
                                                                                                                              ADD COLUMN IF NOT EXISTS item_notes varchar(255) DEFAULT NULL;
                                                                                                                                "
                                                                                                                                  echo "Schema migration completed."
                                                                                                                                  fi
                                                                                                                                  
                                                                                                                                  # Export environment variables for PHP
                                                                                                                                  export MYSQLHOST=127.0.0.1
                                                                                                                                  export MYSQLDATABASE=practice
                                                                                                                                  export MYSQLUSER=root
                                                                                                                                  export MYSQLPASSWORD=
                                                                                                                                  export MYSQLPORT=3306
                                                                                                                                  
                                                                                                                                  PORT=${PORT:-10000}
                                                                                                                                  echo "Starting PHP built-in server on 0.0.0.0:${PORT}..."
                                                                                                                                  cd /var/www/html
                                                                                                                                  exec php -S 0.0.0.0:${PORT}
