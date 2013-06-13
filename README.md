# Propel2 #

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.4.

[![Build Status](https://secure.travis-ci.org/propelorm/Propel2.png?branch=master)](http://travis-ci.org/propelorm/Propel2)


## Requirements ##

Propel2 uses the following Symfony2 Components:

* [Console](https://github.com/symfony/Console)
* [Yaml](https://github.com/symfony/Yaml)
* [Finder](https://github.com/symfony/Finder)
* [Validator](https://github.com/symfony/Validator)
* [Filesystem](https://github.com/symfony/Filesystem)

Propel2 also relies on [**Composer**](https://github.com/composer/composer) to manage dependencies but you
also can use [ClassLoader](https://github.com/symfony/ClassLoader) (see the `autoload.php.dist` file for instance).

Propel2 is only supported on PHP 5.4 and up.


## Installation ##

Read the [Propel documentation](http://www.propelorm.org/).


## Contribute ##

Everybody can contribute to Propel2. Just fork it, and send Pull Requests.
You have to follow [Propel2 Coding Standards](https://github.com/propelorm/Propel2/wiki/Coding-Standards) and provides unit tests as much as possible.

**Note:** you can fix checkstyle before to submit a Pull Request by using the Symfony2 [php-cs-fixer](http://cs.sensiolabs.org/) script.
You just need to install the script:

    wget http://cs.sensiolabs.org/get/php-cs-fixer.phar

Then use it:

    php php-cs-fixer.phar fix .


## Unit Tests ##

To run unit tests, you'll have to install vendors by using [**Composer**](https://github.com/composer/composer).
If you don't have an available `composer.phar` command, just download it:

    wget http://getcomposer.org/composer.phar

If you haven't wget on your computer, use `curl` instead:

    curl -s http://getcomposer.org/installer | php

Then, install dependencies:

    php composer.phar install --dev


#### MySQL ####

The Propel test suite requires a database (`test` for instance, but feel free to choose the name you want), and
three database schemas: `bookstore_schemas`, `contest`, and `second_hand_books`.

Here is the set of commands to run in order to setup MySQL:

    mysql -uroot -e 'SET FOREIGN_KEY_CHECKS = 0; DROP DATABASE IF EXISTS test; DROP SCHEMA IF EXISTS second_hand_books; DROP SCHEMA IF EXISTS contest; DROP SCHEMA IF EXISTS bookstore_schemas; SET FOREIGN_KEY_CHECKS = 1;'
    mysql -uroot -e 'CREATE DATABASE test; CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books;'

Once done, build fixtures (default vendor is `mysql`):

    bin/propel test:prepare

To match Travis CI MySQL configuration, you must set `@@sql_mode` to `STRICT_ALL_TABLES` in yours.

#### PostgreSQL ####

Create mandatory databases, then run:

    bin/propel test:prepare --vendor=pgsql --dsn="pgsql:dbname=test" --user="postgres"

#### SQLite ####

There is nothing to setup, just run:

    bin/propel test:prepare --vendor=sqlite --dsn="sqlite:/tmp/database.sqlite" --user="" --password=""


Now you can run the test suite by running:

    phpunit


## License ##

See the `LICENSE` file.
