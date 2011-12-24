# Propel2 #

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.3.


## Requirements ##

Propel2 uses the following Symfony2 Components:

* [Console](https://github.com/symfony/Console)
* [Yaml](https://github.com/symfony/Yaml)

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

    wget https://raw.github.com/symfony/symfony/master/check_cs

Then use it:

    php check_cs fix


## Unit Tests ##

To run unit tests, you'll have to install vendors by using [**Composer**](https://github.com/composer/composer).
If you don't have an available `composer.phar` command, just download it:

    wget http://getcomposer.org/composer.phar

Then, install dependencies:

    php composer.phar install

Once done, build fixtures:

    php bin/propel test:prepare

Now you can run the test suite by running:

    phpunit
    

## License ##

See the `LICENSE` file.