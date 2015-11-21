#!/bin/sh

DIR=`dirname $0`;
. $DIR/base.sh;

$PHPUNIT --exclude-group database;
