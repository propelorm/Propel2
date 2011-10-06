#!/bin/bash
# Reset Propel tests Fixtures
# 2011 - William Durand <william.durand1@gmail.com>

CURRENT=`pwd`

function rebuild
{
    local dir=$1

    echo "[ $dir ]"

    if [ -d "$FIXTURES_DIR/$dir/build/" ] ; then
        rm -r "$FIXTURES_DIR/$dir/build/"
    fi

    $ROOT/tools/generator/bin/propel-gen $FIXTURES_DIR/$dir main
    $ROOT/tools/generator/bin/propel-gen $FIXTURES_DIR/$dir insert-sql
}

ROOT_DIR=""
FIXTURES_DIR=""

if [ -d "$CURRENT/Fixtures" ] ; then
    ROOT=".."
    FIXTURES_DIR="$CURRENT/Fixtures"
elif [ -d "$CURRENT/tests/Fixtures" ] ; then
    ROOT="."
    FIXTURES_DIR="$CURRENT/tests/Fixtures"
else
    echo "ERROR: No 'tests/Fixtures/' directory found !"
    exit 1
fi

DIRS=`ls $FIXTURES_DIR`

for dir in $DIRS ; do
    rebuild $dir
done

# Special case for reverse Fixtures

REVERSE_DIRS=`ls $FIXTURES_DIR/reverse`

for dir in $REVERSE_DIRS ; do
    echo "[ $dir ]"

    $ROOT/tools/generator/bin/propel-gen $FIXTURES_DIR/reverse/$dir insert-sql
done
