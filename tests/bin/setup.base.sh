#!/bin/sh

if [ "$DB" = "" ]; then
    echo "\$DB is not defined."
    exit;
fi

check() {
    if [ $? != 0 ]; then
        echo "Aborted."; exit 1;
    fi
}