---
layout: documentation
title: Documentation
---

# Documentation #

 * [What's New in Propel 1.6](whats-new.html) Users of previous versions can check the changes here.
 * [Changelog](https://raw.github.com/propelorm/Propel/master/CHANGELOG) Updates in the 1.6 branch since the release of 1.6.0 stable.
 * [API Documentation](http://api.propelorm.org/) The generated API documentation.

## Project Setup ##

 * [Installing Propel](01-installation.html) Install Propel using Git, PEAR, or a tarball.
 * [Building A Project](02-buildtime.html) Generate a PHP model based on a XML schema

## Propel Basics ##

* [Basic CRUD](03-basic-crud.html) The basics of Propel C.R.U.D. (Create, Retrieve, Update, Delete) operations
* [Relationships](04-relationships.html) Searching and manipulating data from related tables.
* [Transactions](06-transactions.html) Where and when to use transactions.
* [Behaviors](07-behaviors.html) The behavior system allows to package and reuse common model features.
* [Logging And Debugging](08-logging.html) Propel can log a lot of information, including the SQL queries it executes.
* [Inheritance](09-inheritance.html) Single Table Inheritance, Class Table Inheritance, and Concrete Table Inheritance come free with Propel.
* [Migrations](10-migrations.html) Change the structure of the database without altering the data.

## Reference ##

* [XML Schema Format](../reference/schema.html) All the database, table, column and foreign key options explained
* [Active Record Classes](../reference/active-record.html) Complete list of the methods of Active Record classes.
* [Active Query Classes](../reference/model-criteria.html) Complete list of the methods of Propel Query classes.
* [Build Properties](../reference/buildtime-configuration.html) Reference for the `build.properties` file (`propel.ini` in symfony).
* [Runtime Configuration File](../reference/runtime-configuration.html) Reference for the `runtime-conf.xml` file.

## Behaviors Reference ##

* [`aggregate_column`](../behaviors/aggregate-column.html)
* [`alternative_coding_standards`](../behaviors/alternative-coding-standards.html)
* [`archivable`](../behaviors/archivable.html)
* [`auto_add_pk`](../behaviors/auto-add-pk.html)
* [`delegate`](../behaviors/delegate.html)
* [`i18n`](../behaviors/i18n.html)
* [`nested_set`](../behaviors/nested-set.html)
* [`query_cache`](../behaviors/query-cache.html)
* [`sluggable`](../behaviors/sluggable.html)
* [`timestampable`](../behaviors/timestampable.html)
* [`sortable`](../behaviors/sortable.html)
* [`validate`](../behaviors/validate.html)
* [`versionable`](../behaviors/versionable.html)
* And [`concrete_inheritance`](09-inheritance.html), documented in the Inheritance Chapter even if it's a behavior

You can also look at [user contributed behaviors](../cookbook/user-contributed-behaviors.html).

## Cookbook ##

### Common Tasks ###

* [Additional SQL Files](../cookbook/adding-additional-sql-files.html) How to execute custom SQL statements at buildtime
* [Advanced Column Types](../cookbook/working-with-advanced-column-types.html) How to work with BLOBs, serialized PHP objects, ENUM, and ARRAY column types.
* [Customizing build](../cookbook/customizing-build.html) How to customize the Phing build process.
* [DB Designer](../cookbook/dbdesigner.html) How to import an XML schema from existing DBDesigner 4 file.
* [How to Use PHP 5.3 Namespaces](../cookbook/namespaces.html) How to generate model classes with namespaces, and how to use them.
* [Model Introspection At Runtime](../cookbook/runtime-introspection.html) How to use the Map classes to discover table properties at runtime.
* [Multi-Component Data Model](../cookbook/multi-component-data-model.html) How to generate model classes in subdirectories, and organize your model into independent packages / modules.
* [Object Copy](../cookbook/copying-persisted-objects.html) How to clone and copy persisted objects.
* [Replication](../cookbook/replication.html) How to use Propel in a Master-Slave Replication Environment.
* [Using Propel With MSSQL Server](../cookbook/using-mssql-server.html) How to choose and configure Propel to persist data to a Microsoft SQL Server database.
* [Using SQL Schemas](../cookbook/using-sql-schemas.html) How to organize tables into SQL schemas (only for MySQL, PostgreSQL, and MSSQL).
* [Working With Existing Databases](../cookbook/working-with-existing-databases.html) How to build an XML schema from an existing db structure, how to dump data to XML, how to import it into a new database, etc.

### Extending Propel ###

* [Writing A Behavior](../cookbook/writing-behavior.html) How to write a custom behavior to reuse model code horizontally.

### Working with symfony 1.4 ###

* [Using Propel as Default ORM](../cookbook/symfony1/init-a-Symfony-project-with-Propel-git-way.html) How to initialize a symfony project with Propel as default ORM - the git way.
* [Using the `i18n` behavior](../cookbook/symfony1/how-to-use-old-SfPropelBehaviori18n-with-sf1.4.html) How to use Propel's `i18n` behavior with symfony 1.4.
* [Using the legacy `symfony_i18n` behavior](../cookbook/symfony1/how-to-use-old-SfPropelBehaviori18n-with-sf1.4.html) How to use the old `SfPropelBehaviori18n` (a.k.a. `symfony_i18n`) with symfony 1.4.

### Working with Symfony2 ###

* [Working with Symfony2 (Introduction)](../cookbook/symfony2/working-with-symfony2.html)
* [Symfony2 And Propel In Real Life](../cookbook/symfony2/symfony2-and-propel-in-real-life.html)
* [Mastering Symfony2 Forms With Propel](../cookbook/symfony2/mastering-symfony2-forms-with-propel.html)
* [The Symfony2 Security Component And Propel](../cookbook/symfony2/the-symfony2-security-component-and-propel.html)

>**Tip**<br />This is the up-to-date documentation for the last Propel version. To access the old documentation, please visit [trac.propelorm.org](http://trac.propelorm.org).
