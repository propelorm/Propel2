---
layout: default
title: How To Contribute ?
---

# How To Contribute ? #

You can easily contribute to the Propel project since all projects are hosted by [GitHub](https://github.com).
You just have to _fork_ the Propel2 project on the [PropelORM organization](https://github.com/propelorm) and
to provide Pull Requests or to submit issues. Note, we are using [Git](http://git-scm.com) as main Source Code Management.

The Propel organization maintains five projects:

* [Propel2](https://github.com/propelorm/Propel2) : the main version.
* [Propel](https://github.com/propelorm/Propel) : the previous major release of Propel.
* [PropelBundle](https://github.com/propelorm/PropelBundle) : a bundle to integrate Propel with [Symfony2](http://www.symfony.com).
* [sfPropelORMPlugin](https://github.com/propelorm/sfPropelORMPlugin) : a plugin to integrate Propel 1.x with [symfony 1.x](http://www.symfony-project.org);
* [propelorm.github.com](https://github.com/propelorm/propelorm.github.com) : the Propel documentation (aka this website).

## Submit an issue ##

The ticketing system is also hosted on GitHub:

* Propel 2: [https://github.com/propelorm/Propel2/issues](https://github.com/propelorm/Propel2/issues)
* Propel (1.x): [https://github.com/propelorm/Propel/issues](https://github.com/propelorm/Propel/issues)
* PropelBundle: [https://github.com/propelorm/PropelBundle/issues](https://github.com/propelorm/PropelBundle/issues)
* sfPropelORMPlugin: [https://github.com/propelorm/sfPropelORMPlugin/issues](https://github.com/propelorm/sfPropelORMPlugin/issues)

## Make a Pull Request ##

The best way to submit a patch is to [make a Pull Request on GitHub](https://help.github.com/articles/creating-a-pull-request).
First, you should create a new branch from the `master`.
Assuming you are in your local Propel project:

```bash
$ git checkout -b master fix-my-patch
```

Now you can write your patch in this branch. Don't forget to provide unit tests
with your fix to prove both the bug and the patch. It will ease the process to
accept or refuse a Pull Request.

When you're done, you have to rebase your branch to provide a clean and safe Pull
Request.

```bash
$ git remote add upstream https://github.com/propelorm/Propel2
$ git checkout master
$ git pull --ff-only upstream master
$ git checkout fix-my-patch
$ git rebase master
```

In this example, the `upstream` remote is the PropelORM organization repository.

Once done, you can submit the Pull Request by pushing your branch to your fork:

```bash
$ git push origin fix-my-patch
```

Go to your fork of the project on GitHub and switch to the patched branch (here
in the above example, `fix-my-patch`) and click on the "Compare and pull request"
button. Add a short description to this Pull Request and submit it.

## Running Unit Tests ##

Propel uses [PHPUnit][] to test the build and runtime frameworks.

You can find the unit test classes and support files in the `tests/` directory.

### Install Composer ###

In order to run the tests, you must install the development dependencies. Propel
uses [Composer][] to manage its dependencies. To install it, you are done with:

    $ wget http://getcomposer.org/composer.phar

Or if you don't have `wget` on your machine:

    $ curl -s http://getcomposer.org/installer | php

Then run Composer to install the necessary stuff:

    $ php composer.phar install --dev

#### Setup MySQL ####

The Propel test suite requires a database (`test` for instance, but feel free to
choose the name you want), and three database schemas: `bookstore_schemas`,
`contest`, and `second_hand_books`.

Here is the set of commands to run in order to setup MySQL:

    $ mysql -uroot -e 'SET FOREIGN_KEY_CHECKS = 0; DROP DATABASE IF EXISTS test; DROP SCHEMA IF EXISTS second_hand_books; DROP SCHEMA IF EXISTS contest; DROP SCHEMA IF EXISTS bookstore_schemas; SET FOREIGN_KEY_CHECKS = 1;'
    $ mysql -uroot -e 'CREATE DATABASE test; CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books;'

Once done, build fixtures (default vendor is `mysql`):

    $ bin/propel test:prepare

To match Travis CI MySQL configuration, you must set `@@sql_mode` to `STRICT_ALL_TABLES` in yours.

#### Configure PostgreSQL ####

Create mandatory databases, then run:

    $ bin/propel test:prepare --vendor=pgsql --dsn="pgsql:dbname=test" --user="postgres"

#### For SQLite ####

There is nothing to setup, just run:

    $ bin/propel test:prepare --vendor=sqlite --dsn="sqlite:/tmp/database.sqlite" --user="" --password=""

Now you can run the test suite by running:

    $ phpunit

### How the Tests Work ###

Every method in the test classes that begins with 'test' is run as a test case by PHPUnit.  All tests are run in isolation; the `setUp()` method is called at the beginning of ''each'' test and the `tearDown()` method is called at the end.

The `BookstoreTestBase` class specifies `setUp()` and `tearDown()` methods which populate and depopulate, respectively, the database.  This means that every unit test is run with a cleanly populated database.  To see the sample data that is populated, take a look at the `BookstoreDataPopulator` class. You can also add data to this class, if needed by your tests; however, proceed cautiously when changing existing data in there as there may be unit tests that depend on it. More typically, you can simply create the data you need from within your test method. It will be deleted by the `tearDown()` method, so no need to clean up after yourself.

## Writing Tests ##

If you've made a change to a template or to Propel behavior, the right thing to do is write a unit test that ensures that it works properly -- and continues to work in the future.

Writing a unit test often means adding a method to one of the existing test classes. For example, let's test a feature in the Propel templates that supports saving of objects when only default values have been specified. Just add a `testSaveWithDefaultValues()` method to the `GeneratedObjectTest` class, as follows:

```php
<?php

/**
 * Test saving object when only default values are set.
 */
public function testSaveWithDefaultValues() {

  // Relies on a default value of 'Penguin' specified in schema
  // for publisher.name col.

  $pub = new Publisher();
  $pub->setName('Penguin');
    // in the past this wouldn't have marked object as modified
    // since 'Penguin' is the value that's already set for that attrib
  $pub->save();

  // if getId() returns the new ID, then we know save() worked.
  $this->assertNotNull($pub->getId(), "Expect Publisher->save() to work  with only default values.");
}
?>
```

Run the test again using the command line to check that it passes:

    $ phpunit GeneratedObjectTest


You can also write additional unit test classes to any of the directories in `test/testsuite/` (or add new directories if needed). The `phpunit` command will find these files automatically and run them.

## Fix checkstyle ##

You can fix checkstyle __before to create your commit__ by using the Symfony2
[php-cs-fixer][] script. You just need to install the script:

    $ wget http://cs.sensiolabs.org/get/php-cs-fixer.phar

Then use it on the file you have edited:

    $ php php-cs-fixer.phar fix $(git ls-files -m)

## Improve the documentation ##

The Propel documentation is written in [Markdown][] syntax and runs through
[GitHub Pages][]. Everybody can contribute to the documentation by forking the
[propelorm.github.com][] project and to submit Pull Requests. Please, try to
wrap your additions around 80 characters.

[Composer]: http://getcomposer.org/
[php-cs-fixer]: http://cs.sensiolabs.org/
[Markdown]: http://daringfireball.net/projects/markdown/
[propelorm.github.com]: https://github.com/propelorm/propelorm.github.com
[GitHub Pages]: http://pages.github.com/
[PHPUnit]: http://www.phpunit.de
