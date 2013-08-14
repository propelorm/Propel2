#!/bin/sh

DIR=`dirname $0`;
. $DIR/setup.base.sh;

if [ "$DB_USER" = "" ]; then
    DB_USER="postgres";
fi

dropdb -U $DB_USER postgres;
check;

createdb -U $DB_USER postgres;
check;

psql --username=$DB_USER -c 'CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books';
check;

if [ "$DB_PW" = "" ]; then
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=postgres" --user="$DB_USER";
else
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=postgres" --user="$DB_USER" --password="$DB_PW";
fi
