#!/bin/sh
DIR=`dirname $0`;
. $DIR/base.sh;

$PHPUNIT --group database --exclude-group mysql;
