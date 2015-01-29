#!/bin/sh

DIR=`dirname $0`;
. $DIR/base.sh;

mysql=`which mysql`;
if [ "$mysql" = "" ]; then
    mysql=`which mysql5`;
fi

if [ "$mysql" = "" ]; then
    echo "Can not find mysql binary. Is it installed?";
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

"$mysql" --host="$DB_HOSTNAME" -u"$DB_USER" $pw_option -e '
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS test;
DROP SCHEMA IF EXISTS second_hand_books;
DROP SCHEMA IF EXISTS contest;
DROP SCHEMA IF EXISTS bookstore_schemas;
DROP SCHEMA IF EXISTS migration;
SET FOREIGN_KEY_CHECKS = 1;
'
check;

"$mysql" --host="$DB_HOSTNAME" -u"$DB_USER" $pw_option -e '
CREATE DATABASE test;
CREATE SCHEMA bookstore_schemas;
CREATE SCHEMA contest;
CREATE SCHEMA second_hand_books;
CREATE SCHEMA migration;
';
check;
