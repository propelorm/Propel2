#!/bin/sh

if [ "$CIRCLE_NODE_INDEX" = "0" ]; then
    echo "agnostic tests"
    ./tests/bin/phpunit.agnostic.sh;
fi

if [ "$CIRCLE_NODE_INDEX" = "1" ]; then
    echo "mysql tests"
    DB=mysql ./tests/bin/phpunit.mysql.sh;
fi

if [ "$CIRCLE_NODE_INDEX" = "2" ]; then
    echo "postgresql tests"
    DB=pgsql ./tests/bin/phpunit.pgsql.sh;
fi

if [ "$CIRCLE_NODE_INDEX" = "3" ]; then
    echo "sqlite tests"
    DB=sqlite ./tests/bin/phpunit.sqlite.sh;
fi

result=$?

cp tests/clover.cov $CIRCLE_ARTIFACTS

exit $result