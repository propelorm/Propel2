#!/bin/sh

check() {
    if [ $? != 0 ]; then
        echo "Aborted."; exit 1;
    fi
}