#!/bin/sh

DIR=`dirname $0`;
. $DIR/base.sh;

if [ "$CIRCLE_PROJECT_USERNAME" = "propelorm" ]; then
    # only primary repo (not forks) should do the expensive code coverage report.
    export PHPUNIT_COVERAGE=1
fi

if [ "$CIRCLE_NODE_INDEX" = "0" ]; then
    echo "agnostic tests"
    ./tests/bin/phpunit.agnostic.sh;
    check;
fi

if [ "$CIRCLE_NODE_INDEX" = "1" ]; then
    echo "mysql tests"
    DB=mysql ./tests/bin/phpunit.mysql.sh;
    check;
fi

if [ "$CIRCLE_NODE_INDEX" = "2" ]; then
    echo "postgresql tests"
    DB=pgsql ./tests/bin/phpunit.pgsql.sh;
    check;
fi

if [ "$CIRCLE_NODE_INDEX" = "3" ]; then
    echo "sqlite tests"
    DB=sqlite ./tests/bin/phpunit.sqlite.sh;
    check;
fi

if [ "$PHPUNIT_COVERAGE" = "1" ]; then
    cp tests/clover.cov $CIRCLE_ARTIFACTS
fi
