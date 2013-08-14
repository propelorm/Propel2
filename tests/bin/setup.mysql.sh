#!/bin/sh

DIR=`dirname $0`;
. $DIR/setup.base.sh;

mysql=`which mysql`;
if [ "$mysql" = "" ]; then
    mysql=`which mysql5`;
fi

if [ "$mysql" = "" ]; then
    echo "Can not find mysql binary. Is it installed?";
    exit;
fi

$mysql -u$DB_USER -e '\
SET FOREIGN_KEY_CHECKS = 0; \
DROP DATABASE IF EXISTS test; \
DROP SCHEMA IF EXISTS second_hand_books; \
DROP SCHEMA IF EXISTS contest; \
DROP SCHEMA IF EXISTS bookstore_schemas; \
SET FOREIGN_KEY_CHECKS = 1; \
';
check;

$mysql -u$DB_USER -e '\
CREATE DATABASE test; \
CREATE SCHEMA bookstore_schemas; \
CREATE SCHEMA contest; \
CREATE SCHEMA second_hand_books; \
';
check;

if [ "$DB_PW" = "" ]; then
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=test" --user="$DB_USER";
else
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=test" --user="$DB_USER" --password="$DB_PW";
fi