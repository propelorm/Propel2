---
layout: documentation
title: How to Use PHP 5.3 Namespaces
---

# How to Use PHP 5.3 Namespaces #

The generated model classes can use a namespace. It eases the management of large database models, and makes the Propel model classes integrate with PHP 5.3 applications in a clean way.

## Namespace Declaration And Inheritance ##

To define a namespace for a model class, you just need to specify it in a `namespace` attribute of the `<table>` element for a single table, or in the `<database>` element to set the same namespace to all the tables.

Here is an example schema using namespaces:

```xml
<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<database name="bookstore" defaultIdMethod="native" namespace="Bookstore">

  <table name="book">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="title" type="VARCHAR" required="true" primaryString="true" />
    <column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
    <column name="price" required="false" type="FLOAT" />
    <column name="publisher_id" required="false" type="INTEGER" description="Foreign Key Publisher" />
    <column name="author_id" required="false" type="INTEGER" description="Foreign Key Author" />
    <foreign-key foreignTable="publisher" onDelete="setnull">
      <reference local="publisher_id" foreign="id" />
    </foreign-key>
    <foreign-key foreignTable="author" onDelete="setnull" onUpdate="cascade">
      <reference local="author_id" foreign="id" />
    </foreign-key>
  </table>

  <table name="author">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
    <column name="first_name" required="true" type="VARCHAR" size="128" />
    <column name="last_name" required="true" type="VARCHAR" size="128" />
    <column name="email" type="VARCHAR" size="128" />
  </table>

  <table name="publisher" namespace="Book">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="name" required="true" type="VARCHAR" size="128" default="Penguin" />
  </table>

  <table name="user" namespace="\Admin">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
    <column name="login" required="true" type="VARCHAR" size="128" />
    <column name="email" type="VARCHAR" size="128" />
  </table>

</database>
```

The `<database>` element defines a `namespace` attribute. The `book` and `author` tables inherit their namespace from the database, therefore the generated classes for these tables will be `\Bookstore\Book` and `\Bookstore\Author`.

The `publisher` table defines a `namespace` attribute on its own, which _extends_ the database namespace. That means that the generated class will be `\Bookstore\Book\Publisher`.

As for the `user` table, it defines an absolute namespace (starting with a backslash), which _overrides_ the database namespace. The generated class for the `user` table will be `Admin\User`.

>**Tip**<br />You can use subnamespaces (i.e. namespaces containing backslashes) in the `namespace` attribute.

## Using Namespaced Models ##

Namespaced models benefit from the Propel runtime autoloading just like the other model classes. You just need to alias them, or to use their fully qualified name.

```php
<?php
// use an alias
use Bookstore\Book;
$book = new Book();

// or use fully qualified name
$book = new \Bookstore\Book();
```

Relation names forged by Propel don't take the namespace into account. That means that related getter and setters make no mention of it:

```php
<?php
$author = new \Bookstore\Author();
$book = new \Bookstore\Book();
$book->setAuthor($author);
$book->save();
```

The namespace is used for the ActiveRecord class, but also for the Query classes. Just remember that when you use relation names ina query, the namespace should not appear:

```php
<?php
$author = \Bookstore\AuthorQuery::create()
  ->useBookQuery()
    ->filterByPrice(array('max' => 10))
  ->endUse()
  ->findOne();
```

Related tables can have different namespaces, it doesn't interfere with the functionality provided by the object model:

```php
<?php
$book = \Bookstore\BookQuery::create()
  ->findOne();
echo get_class($book->getPublisher());
// \Bookstore\Book\Publisher
```

>**Tip**<br />Using namespaces make generated model code incompatible with versions of PHP less than 5.3. Beware that you will not be able to use your model classes in an older PHP application.

## Using Namespaces As A Directory Structure ##

In a schema, you can define a `package` attribute on a `<database>` or a `<table>` tag to generate model classes in a subdirectory (see [Multi-Component](multi-component-data-model.html)). If you use namespaces to autoload your classes based on a SplClassAutoloader (see [http://groups.google.com/group/php-standards](http://groups.google.com/group/php-standards)), then you may find yourself repeating the `namespace` data in the `package` attribute:

```xml
<database name="bookstore" defaultIdMethod="native"
  namespace="Foo\Bar" package="Foo.Bar">
```

To avoid such repetitions, just set the `propel.namespace.autoPackage` setting to `true` in your `build.properties`:

```ini
propel.namespace.autoPackage = true
```

Now Propel will automatically create a `package` attribute, and therefore distribute model classes in subdirectories, based on the `namespace` attribute, and you can  omit the manual `package` attribute in the schema:

```xml
<database name="bookstore" defaultIdMethod="native" namespace="Foo\Bar">
```
