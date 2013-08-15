#!/bin/sh

DIR=`dirname $0`;
. $DIR/setup.base.sh;

psql=`which psql`;

if [ "$psql" = "" ]; then
    echo "Can not find psql binary. Is it installed?";
    exit 1;
fi

if [ "$DB_USER" = "" ]; then
    echo "\$DB_USER not set. Using 'postgres'.";
    DB_USER="postgres";
fi

DB="pgsql";

$psql --version;

dropdb -U $DB_USER postgres;

createdb -U $DB_USER postgres;
check;

$psql --username=$DB_USER -c 'CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books';
check;

if [ "$DB_PW" = "" ]; then
    echo "\$DB_PW not set. Using no password.";
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=postgres" --user="$DB_USER";
else
    php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=postgres" --user="$DB_USER" --password="$DB_PW";
fi
