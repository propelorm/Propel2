#!/bin/sh

if [ "$CIRCLE_PROJECT_USERNAME" = "propelorm" ]; then
    # only primary repo (not forks) should do the expensive code coverage report.
    export PHPUNIT_COVERAGE=1
fi

if [ "$CIRCLE_NODE_INDEX" = "0" ]; then
    echo "agnostic tests"
    ./vendor/bin/phpunit -c "tests/agnostic.phpunit.xml" --coverage-php="tests/clover.cov";
fi

if [ "$CIRCLE_NODE_INDEX" = "1" ]; then
    echo "mysql tests"
    ./vendor/bin/phpunit -c "tests/mysql.phpunit.xml" --coverage-php="tests/clover.cov";
fi

if [ "$CIRCLE_NODE_INDEX" = "2" ]; then
    echo "postgresql tests"
    ./vendor/bin/phpunit -c "tests/pgsql.phpunit.xml" --coverage-php="tests/clover.cov";
fi

if [ "$CIRCLE_NODE_INDEX" = "3" ]; then
    echo "sqlite tests"
    ./vendor/bin/phpunit -c "tests/sqlite.phpunit.xml" --coverage-php="tests/clover.cov";
fi

if [ "$PHPUNIT_COVERAGE" = "1" ]; then
    cp tests/clover.cov $CIRCLE_ARTIFACTS
fi
