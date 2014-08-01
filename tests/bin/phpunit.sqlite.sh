#!/bin/sh
./vendor/bin/phpunit --group database --exclude-group mysql;
