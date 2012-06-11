# Propel2 #

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.3.

[![Build Status](https://secure.travis-ci.org/propelorm/Propel2.png?branch=master)](http://travis-ci.org/propelorm/Propel2)


## Requirements ##

Propel2 uses the following Symfony2 Components:

* [Console](https://github.com/symfony/Console)
* [Yaml](https://github.com/symfony/Yaml)
* [Finder](https://github.com/symfony/Finder)
* [Validator](https://github.com/symfony/Validator)

Propel2 also relies on [**Composer**](https://github.com/composer/composer) to manage dependencies but you
also can use [ClassLoader](https://github.com/symfony/ClassLoader) (see the `autoload.php.dist` file for instance).

Propel2 is only supported on PHP 5.3.3 and up.


## Installation ##

Read the [Propel documentation](http://www.propelorm.org/).


## Contribute ##

Everybody can contribute to Propel2. Just fork it, and send Pull Requests.
You have to follow [Propel2 Coding Standards](https://github.com/propelorm/Propel2/wiki/Coding-Standards) and provides unit tests as much as possible.

**Note:** you can fix checkstyle before to submit a Pull Request by using the Symfony2 `check_cs` script.
You just need to install [Finder](http://github.com/symfony/Finder) and the script:

    git clone git://github.com/symfony/Finder.git vendor/Symfony/Component/Finder

    wget http://cs.sensiolabs.org/get/php-cs-fixer.phar

Then use it:

    php php-cs-fixer.phar fix .


## Unit Tests ##

To run unit tests, you'll have to install vendors by using [**Composer**](https://github.com/composer/composer).
If you don't have an available `composer.phar` command, just download it:

    wget http://getcomposer.org/composer.phar

If you haven't wget on your computer, use `curl` instead :
    curl -s http://getcomposer.org/install | php

Then, install dependencies:

    php composer.phar install


#### MySQL ####

The Propel test suite requires a database (`test` for instance, but feel free to choose the name you want), and
three database schemas: `bookstore_schemas`, `contest`, and `second_hand_books`.

Here is the set of commands to run in your shell in order to setup requirements if you want to use MySQL:

    mysql -uroot -e 'create database test'
    mysql -uroot -e 'create schema bookstore_schemas'
    mysql -uroot -e 'create schema contest'
    mysql -uroot -e 'create schema second_hand_books'

Once done, build fixtures (default vendor is `mysql`):

    bin/propel test:prepare

#### PostgreSQL ####

Create mandatory databases, then run:

    bin/propel test:prepare --vendor=postgres --dsn="dbname=test" --user="postgres"

#### SQLite ####

There is nothing to setup, just run:

    bin/propel test:prepare --vendor=sqlite --dsn="sqlite:/tmp/database.sqlite" --user="" --password=""


Now you can run the test suite by running:

    phpunit

You can also pass a `--verbose` argument if you want to know where tests fail. It's useful if you want to contribute.

## License ##

See the `LICENSE` file.
