#!/bin/sh

DIR=`dirname $0`;
. $DIR/base.sh;

rm -f ./test.sq3;

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

path=`realpath "$DIR/../test.sq3"`;

rm -f $path;