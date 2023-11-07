#!/bin/sh

mysql=`which mysql`;
if [ "$mysql" = "" ]; then
    mysql=`which mysql5`;
fi

if [ "$mysql" = "" ]; then
    echo "Cannot find mysql binary. Is it installed?";
    exit 1;
fi

if [ "$DB_USER" = "" ]; then
	echo "\$DB_USER not set. Using 'root'.";
    DB_USER="root";
fi

pw_option=""
if [ "$DB_PW" != "" ]; then
	pw_option=" -p$DB_PW"
fi

DB_HOSTNAME=${DB_HOSTNAME-127.0.0.1};
DB_NAME=${DB_NAME-test};

"$mysql" --version;

retry_count=0
while true; do
    "$mysql" --host="$DB_HOSTNAME" -u"$DB_USER" $pw_option -e "
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS $DB_NAME;
DROP SCHEMA IF EXISTS second_hand_books;
DROP SCHEMA IF EXISTS contest;
DROP SCHEMA IF EXISTS bookstore_schemas;
DROP SCHEMA IF EXISTS migration;
SET FOREIGN_KEY_CHECKS = 1;
"
    if [ $? -eq 0 ] || [ $retry_count -ge 6 ]; then break; fi
    retry_count=$((retry_count + 1))
    sleep "$(awk "BEGIN{print 2 ^ $retry_count}")"
done

"$mysql" --host="$DB_HOSTNAME" -u"$DB_USER" $pw_option -e "
SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
CREATE DATABASE $DB_NAME;
CREATE SCHEMA bookstore_schemas;
CREATE SCHEMA contest;
CREATE SCHEMA second_hand_books;
CREATE SCHEMA migration;
";



DIR=`dirname $0`;
dsn="mysql:host=$DB_HOSTNAME;dbname=$DB_NAME";
php $DIR/../../bin/propel test:prepare --vendor="mysql" --dsn="$dsn" --user="$DB_USER" --password="$DB_PW";
