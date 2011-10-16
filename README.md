# Propel2 #

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.3.


## Requirements ##

Propel2 uses the following Symfony2 Components:

* [ClassLoader](https://github.com/symfony/ClassLoader)
* [Yaml](https://github.com/symfony/Yaml)

Propel2 is only supported on PHP 5.3.2 and up.


## Installation ##

_// Not yet documented._


## Running unit tests ##

To run unit tests, you'll have to install vendors:

    ./bin/install_vendors.sh

Once done, build fixtures:

    ./tests/reset_tests.sh

Now you can run unit tests for both `Generator/` and `Runtime/`:

    phpunit -c phpunit.xml.dist tests/Propel/Tests/Generator/

    phpunit -c phpunit.xml.dist tests/Propel/Tests/Runtime/


## License ##

See the `LICENSE` file.
