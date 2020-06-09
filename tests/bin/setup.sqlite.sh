#!/bin/sh

DIR=`dirname $0`;

path=`realpath "$DIR/../test.sq3"`;

rm -f $path;

dsn="sqlite:$path";
php $DIR/../../bin/propel test:prepare --vendor="sqlite" --dsn="$dsn";
