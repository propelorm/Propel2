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
* [Propel](https://github.com/propelorm/Propel) : the previous release of Propel.
* [PropelBundle](https://github.com/propelorm/PropelBundle) : a bundle to integrate Propel with [Symfony2](http://www.symfony.com).
* [sfPropelORMPlugin](https://github.com/propelorm/sfPropelORMPlugin) : a plugin to integrate Propel with [symfony 1.x](http://www.symfony-project.org);
* [propelorm.github.com](https://github.com/propelorm/propelorm.github.com) : the Propel documentation (aka this website).

## Submit an issue ##

The ticketing system is also hosted on GitHub:

* Propel2: [https://github.com/propelorm/Propel2/issues](https://github.com/propelorm/Propel2/issues)
* Propel (1.x): [https://github.com/propelorm/Propel/issues](https://github.com/propelorm/Propel/issues)
* PropelBundle: [https://github.com/propelorm/PropelBundle/issues](https://github.com/propelorm/PropelBundle/issues)
* sfPropelORMPlugin: [https://github.com/propelorm/sfPropelORMPlugin/issues](https://github.com/propelorm/sfPropelORMPlugin/issues)

## Make a Pull Request ##

The best way to submit a patch is to make a Pull Request on GitHub. First, you should create a new branch from the `master`.
Assuming you are in your local Propel project:

```bash
$ git checkout -b master fix-my-patch
```

Now you can write your patch in this branch. Don't forget to provide unit tests with your fix to prove both the bug and the patch.
It will ease the process to accept or refuse a Pull Request.

When you're done, you have to rebase your branch to provide a clean and safe Pull Request.

```bash
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

Go to www.github.com and press the _Pull Request_ button. Add a short description to this Pull Request and submit it.

## Running Unit Tests ##

Propel uses [PHPUnit](http://www.phpunit.de) to test the build and runtime frameworks.

You can find the unit test classes and support files in the `test/testsuite` directory.

### Install PHPUnit ###

In order to run the tests, you must install PHPUnit:

```bash
$ pear channel-discover pear.phpunit.de
$ pear install phpunit/PHPUnit
```

### Configure the Database to be Used in the Tests ###

You must configure both the generator and the runtime connection settings.

```ini
// in test/fixtures/bookstore/build.properties
propel.database = mysql
propel.database.url = mysql:dbname=test
propel.mysqlTableType = InnoDB
propel.disableIdentifierQuoting = true
# For MySQL or Oracle, you also need to specify username & password
propel.database.user = myusername
propel.database.password = p@ssw0rd
```

```xml
// in test/fixtures/bookstore/runtime-conf.xml
<datasource id="bookstore">
  <!-- the Propel adapter to use for this connection -->
  <adapter>mysql</adapter>
  <!-- Connection parameters. See PDO documentation for DSN format and available option constants. -->
  <connection>
      <classname>DebugPDO</classname>
      <dsn>mysql:dbname=test</dsn>
      <user>myusername</user>
      <password>p@ssw0rd</password>
      <options>
        <option id="ATTR_PERSISTENT">false</option>
      </options>
      <attributes>
        <!-- For MySQL, you should also turn on prepared statement emulation,
                        as prepared statements support is buggy in mysql driver -->
        <option id="ATTR_EMULATE_PREPARES">true</option>
      </attributes>
      <settings>
        <!--  Set the character set for client connection -->
        <setting id="charset">utf8</setting>
      </settings>
  </connection>
</datasource>
```

>**Tip**<br />To run the unit tests for the namespace support in PHP 5.3, you must also configure the `fixtures/namespaced` project.

<br />

>**Tip**<br />To run the unit tests for the database schema support, you must also configure the `fixtures/schemas` project. This projects requires that your database supports schemas, and already contains the following schemas: `bookstore_schemas`, `contest`, and `second_hand_books`. Note that the user defined in `build.properties` and `runtime-conf.xml` must have access to these schemas.

### Build the Propel Model and Initialize the Database ###

```bash
$ cd /path/to/propel/test
$ ../generator/bin/propel-gen fixtures/bookstore main
$ mysqladmin create test
$ ../generator/bin/propel-gen fixtures/bookstore insert-sql
```

>**Tip**<br />To run the unit tests for the namespace support in PHP 5.3, you must also build the `fixtures/namespaced/` project.

<br />

>**Tip**<br />To run the unit tests for the database schema support, you must also build the `fixtures/schemas/` project.

If you test on MySQL, the following SQL script will create all the necessary test databases and grant access to the anonymous user, so the unit tests should pass without any special configuration:

```sql
CREATE DATABASE test;
GRANT ALL ON test.* TO ''@'localhost';

CREATE DATABASE bookstore_schemas;
GRANT ALL ON bookstore_schemas.* TO ''@'localhost';

CREATE DATABASE contest;
GRANT ALL ON contest.* TO ''@'localhost';

CREATE DATABASE second_hand_books;
GRANT ALL ON second_hand_books.* TO ''@'localhost';

CREATE DATABASE reverse_bookstore;
GRANT ALL ON reverse_bookstore.* TO ''@'localhost';
```

You can build all fixtures by running the `reset_tests.sh` shell script:

```bash
$ cd /path/to/propel/test
$ ./reset_tests.sh
```

### Run the Unit Tests ###

Run all the unit tests at once using the `phpunit` command:

```bash
$ cd /path/to/propel/test
$ phpunit testsuite
```

To run a single test, go inside the unit test directory, and run the test using the command line. For example to run only GeneratedObjectTest:

```
$ cd testsuite/generator/builder/om
$ phpunit GeneratedObjectTest
```

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

```bash
$ phpunit GeneratedObjectTest
```

You can also write additional unit test classes to any of the directories in `test/testsuite/` (or add new directories if needed). The `phpunit` command will find these files automatically and run them.

## Improve the documentation ##

The Propel documentation is written in [Markdown](http://daringfireball.net/projects/markdown/) syntax and runs through [GitHub Pages](http://pages.github.com/). Everybody can contribute to the documentation by forking the [propelorm.github.com](https://github.com/propelorm/propelorm.github.com) project and to submit Pull Requests.
