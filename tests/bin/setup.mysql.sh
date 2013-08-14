#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";
. $DIR/setup.base.sh;

mysql -u$DB_USER -e '\
SET FOREIGN_KEY_CHECKS = 0; \
DROP DATABASE IF EXISTS test; \
DROP SCHEMA IF EXISTS second_hand_books; \
DROP SCHEMA IF EXISTS contest; \
DROP SCHEMA IF EXISTS bookstore_schemas; \
SET FOREIGN_KEY_CHECKS = 1; \
';
echo "Delete databases and schemas in MySQL database.";

mysql -u$DB_USER -e '\
CREATE DATABASE test; \
CREATE SCHEMA bookstore_schemas; \
CREATE SCHEMA contest; \
CREATE SCHEMA second_hand_books; \
';
echo "MySQL with the databases and schemas of the testsuite equipped.";

php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=test" --user="$DB_USER"