# Propel2

Propel2 is an open-source Object-Relational Mapping (ORM) for PHP 5.5 and up.

[![Build Status](https://travis-ci.org/propelorm/Propel2.svg?branch=master)](https://travis-ci.org/propelorm/Propel2)
[![Code Climate](https://codeclimate.com/github/propelorm/Propel2/badges/gpa.svg)](https://codeclimate.com/github/propelorm/Propel2)
<a href="https://codeclimate.com/github/propelorm/Propel2"><img src="https://codeclimate.com/github/propelorm/Propel2/badges/coverage.svg" /></a>
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/propelorm/Propel)

## Requirements

Propel2 uses the following Symfony2 Components:

* [Console](https://github.com/symfony/Console)
* [Yaml](https://github.com/symfony/Yaml)
* [Finder](https://github.com/symfony/Finder)
* [Validator](https://github.com/symfony/Validator)
* [Filesystem](https://github.com/symfony/Filesystem)

Propel2 also relies on [**Composer**](https://github.com/composer/composer) to manage dependencies but you
also can use [ClassLoader](https://github.com/symfony/ClassLoader) (see the `autoload.php.dist` file for instance).

Propel2 is only supported on PHP 5.5 and up.


## Installation

Read the [Propel documentation](http://propelorm.org/documentation/01-installation.html).


## Contribute

Everybody can contribute to Propel2. Just fork it, and send Pull Requests.
You have to follow [Propel2 Coding Standards](https://github.com/propelorm/Propel2/wiki/Coding-Standards) and provides unit 
tests as much as possible. Also [check out the roadmap](https://github.com/propelorm/Propel2/wiki) to get an overview of what we are working on!

Please see our [contribution guideline](http://propelorm.org/contribute.html). Thank you!

## Status of the project

**Never ending alpha releases?**

Propel v2 started as very big refactoring of the old Propel version 1, making it PSR1-2 compatible, introducing namespaces 
and refactoring a lot of internal stuff.
However, the heart (and most complex code at the same time) of Propel 1 and 2 is the 
[Query](https://github.com/propelorm/Propel2/blob/master/src/Propel/Generator/Builder/Om/QueryBuilder.php) 
and 
[Object classes builder](https://github.com/propelorm/Propel2/blob/master/src/Propel/Generator/Builder/Om/ObjectBuilder.php),
together 8k unmaintainable lines of code. These 2 files (and with it the architecture) is the main problem why we can not 
release a stable version, because it never went stable and due to the lack of contributions (and mean, nobody is surprised 
when you look at the very complex structure of those 2 files) we are simply not able to release a version that indicates 
that you get somehow support for bugs and new features, which is our aspiration to do so once a official stable version is tagged.

Because Propel2 took so much time (several years) and ActiveRecord (the way it was built in Propel1 and 2) is not modern
anymore (only hardly testable, not so much enjoyable) along with other reasons, we decided to rewrite the very heart of Propel. 
The result is [Propel3 a data-mapper](https://github.com/propelorm/Propel3) implementation with optional active-record traits.
It is much cleaner now, is able to support also NoSQL databases and its data-mapper implementation allows you to write tests
in a more enjoyable way. To sum up: A very modern approach that is also designed to work in a high-performance 
application-server environment like [PHP-PM](https://github.com/php-pm/php-pm).   

In our eyes it makes no sense to tag a Propel2 stable version, because there is simply too less manpower on this very complex
project, we do not want to maintain an old obsolete architecture and want to invest our life time more in a modern approach
of doing ORM in modern PHP.

Our advice:

1. Use Doctrine if you are looking for a long-term supported, full-featured (and difficult to learn) and stable ORM.
2. Use easy to learn Eloquent for prototyping and smaller projects.
3. Help us build Propel3, which aims someday to be both of the above.

## License

See the `LICENSE` file.
