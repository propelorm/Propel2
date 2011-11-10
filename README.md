# Propel2 #

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.3.


## Requirements ##

Propel2 uses the following Symfony2 Components:

* [ClassLoader](https://github.com/symfony/ClassLoader)
* [Yaml](https://github.com/symfony/Yaml)

Propel2 is only supported on PHP 5.3.2 and up.


## Installation ##

Read the [Propel documentation](http://www.propelorm.org/).


## Contribute ##

Everybody can contribute to Propel2. Just fork it, and send Pull Requests.
You have to follow [Propel2 Coding Standards](http://github.com/propelorm/Propel2/issues/2) and provides unit tests as much as possible.

**Note:** you can fix checkstyle before to submit a Pull Request by using the Symfony2 `check_cs` script.
You just need to install [Finder](http://github.com/symfony/Finder) and the script:

    git clone git://github.com/symfony/Finder.git vendor/Symfony/Component/Finder

    wget https://raw.github.com/symfony/symfony/master/check_cs

Then use it:

    php check_cs fix


## Unit Tests ##

To run unit tests, you'll have to install vendors:

    ./bin/install_vendors.sh

Once done, build fixtures:

    ./tests/reset_tests.sh

Now you can run unit tests:

    phpunit


## License ##

See the `LICENSE` file.
