#!/bin/sh

if ! command -v psql > /dev/null; then
    echo "Cannot find psql binary. Is it installed?";
    exit 1;
fi

if [ "$DB_USER" = "" ]; then
    echo "\$DB_USER not set. Using 'postgres'.";
    DB_USER="postgres";
fi

if [ "$DB_NAME" = "" ]; then
    echo "\$DB_NAME not set. Using 'postgres'.";
    DB_NAME="postgres";
fi

DB_HOSTNAME=${DB_HOSTNAME-127.0.0.1};
DB_PW=${DB_PW-$PGPASSWORD};

if [ -z "$DB_PW" ]; then
    echo "\$DB_PW not set. Leaving empty."
else
    NO_PWD="--no-password"
fi

(
    export PGPASSWORD=$DB_PW;

    echo "removing existing test db"
    dropdb  --host="$DB_HOSTNAME" --username="$DB_USER" $NO_PWD "$DB_NAME";

    echo "creating new test db"
    createdb  --host="$DB_HOSTNAME" --username="$DB_USER" $NO_PWD "$DB_NAME";
    
    echo "creating schema"
    psql --host="$DB_HOSTNAME" --username="$DB_USER" $NO_PWD -c '
    CREATE SCHEMA bookstore_schemas;
    CREATE SCHEMA contest;
    CREATE SCHEMA second_hand_books;
    CREATE SCHEMA migration;
    ' "$DB_NAME" >/dev/null;
)

DIR=`dirname $0`;
dsn="pgsql:host=$DB_HOSTNAME;dbname=$DB_NAME";
php $DIR/../../bin/propel test:prepare --vendor="pgsql" --dsn="$dsn" --user="$DB_USER" --password="$DB_PW";
