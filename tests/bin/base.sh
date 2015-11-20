#!/bin/sh

check() {
    if [ $? != 0 ]; then
        echo "Aborted."; exit 1;
    fi
}

if [ "$PHPUNIT_COVERAGE" = "1" ]; then
    PHPUNIT="./vendor/bin/phpunit --coverage-php=tests/clover.cov"
else
    PHPUNIT="./vendor/bin/phpunit"
fi