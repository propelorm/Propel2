#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";
. $DIR/setup.base.sh;

dropdb -U postgres postgres;
echo "Delete databases and schemas in PostresSQL database.";

createdb -U postgres postgres;
psql --username=postgres -c 'CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books';

echo "PostgreSQL with the databases and schemas of the testsuite equipped.";

php $DIR/../../bin/propel test:prepare --vendor="$DB" --dsn="$DB:dbname=postgres" --user="$DB_USER"