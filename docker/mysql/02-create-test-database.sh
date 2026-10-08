#!/bin/sh
set -eu

MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --user=root <<SQL
CREATE DATABASE IF NOT EXISTS dentissa_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON dentissa_test.* TO '$MYSQL_USER'@'%';
SQL
