#!/bin/sh

if ! command -v mysql > /dev/null; then
    echo "Cannot find mysql binary. Is it installed?";
    exit 1;
fi

if [ "$DB_USER" = "" ]; then
	echo "\$DB_USER not set. Using 'root'.";
    DB_USER="root";
fi

if [ "$DB_NAME" = "" ]; then
    echo "\$DB_NAME not set. Using 'test'.";
    DB_NAME="test";
fi

DB_HOSTNAME=${DB_HOSTNAME-127.0.0.1};
DB_PW=${DB_PW-$MYSQL_PWD};
DB_PORT=${DB_PORT-3306};

(
    export MYSQL_PWD="$DB_PW"

    echo "Dropping existing databases and schemas"
    mysql --host="$DB_HOSTNAME" -P $DB_PORT -u"$DB_USER" -e "
    SET FOREIGN_KEY_CHECKS = 0;
    DROP DATABASE IF EXISTS $DB_NAME;
    DROP SCHEMA IF EXISTS second_hand_books;
    DROP SCHEMA IF EXISTS contest;
    DROP SCHEMA IF EXISTS bookstore_schemas;
    DROP SCHEMA IF EXISTS migration;
    SET FOREIGN_KEY_CHECKS = 1;
    " || exit 1;

    echo "Creating existing databases and schemas"
    mysql --host="$DB_HOSTNAME" -P $DB_PORT -u"$DB_USER" -e "
    SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
    CREATE DATABASE $DB_NAME;
    CREATE SCHEMA bookstore_schemas;
    CREATE SCHEMA contest;
    CREATE SCHEMA second_hand_books;
    CREATE SCHEMA migration;
    " || exit 1;
) || exit 1;

DIR=`dirname $0`;
dsn="mysql:host=$DB_HOSTNAME;port=$DB_PORT;dbname=$DB_NAME";
php $DIR/../../bin/propel test:prepare --vendor="mysql" --dsn="$dsn" --user="$DB_USER" --password="$DB_PW";
