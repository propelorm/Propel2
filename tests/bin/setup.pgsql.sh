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

DB_HOSTNAME=${DB_HOSTNAME-127.0.0.1};

$psql --version;

dropdb --host=$DB_HOSTNAME --username=$DB_USER postgres;

createdb --host=$DB_HOSTNAME --username=$DB_USER postgres;
check;

$psql --host=$DB_HOSTNAME --username=$DB_USER -c '
CREATE SCHEMA bookstore_schemas;
CREATE SCHEMA contest;
CREATE SCHEMA second_hand_books;
CREATE SCHEMA migration;
';
check;
