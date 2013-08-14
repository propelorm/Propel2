#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";

if [ "$DB" = "" ]; then
    echo "\$DB is not defined."
    exit;
fi

if [ "$DB_USER" = "" ]; then
    echo "\$DB_USER is not defined."
    exit;
fi

function check {
    if [ $? != 0 ]; then
        echo "Aborted."; exit;
    fi
}