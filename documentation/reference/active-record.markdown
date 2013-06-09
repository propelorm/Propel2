---
layout: documentation
title: "Active Record Reference"
---

# Active Record Reference #

Propel generates smart Active Record classes based on the schema definition of tables. Active Record objects offer a powerful API to manipulating database records in an intuitive way.

## Overview ##

For each table present in the XML schema, Propel generates one Active Record class - also called the Model class on some parts of the documentation. Instances of the Active Record classes represent a single row from the database, as specified in the [Active record design pattern](http://en.wikipedia.org/wiki/Active_record_pattern). That makes it easy to create, edit, insert or delete an individual row in the persistence layer.

Consider the following schema, describing a simple `book` table with four columns:

```xml
<table name="book" description="Book Table">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
  <column name="author_id" required="false" type="INTEGER" />
</table>
```

Based on this schema, Propel generates a `Book` class that lets you manipulate `book` records:

```php
<?php
$book = new Book();
$book->setTitle('War and Peace');
$book->setISBN('067003469X');
$book->save();
// INSERT INTO book (title, isbn) VALUES ('War and Peace', '067003469X')

$book->delete();
// DELETE FROM book WHERE id = 1234;
```

The best way to learn what a generated Active Record class can do is to inspect the generated code - all methods are fully documented.

## Active Record Class Naming Conventions ##

```xml
<!-- For each table, Propel creates an Active Record class in PHP
     named using a CamelCase version of the table name !-->
<table name="book">
<table name="book_reader">
<!-- generates the following Active Record classes: Book, BookReader !-->

<!-- Propel advocates the use of singular table names !-->
<table name="books">
<!-- generates the following Active Record class: Books (not good) !-->

<!-- You can customize the name of an Active Record class in PHP
     by setting the phpName attribute in the <table> tag !-->
<table name="foo_books" phpName="Book" />
<!-- generates the following Active Record class: Book !-->

<!-- Active Record classes are generated in the directory specified in build.properties
     under the propel.php.dir setting !-->
<table name="book">
<!-- generates the Book class under /path/to/project/build/classes/Book.php !-->

<!-- To group Active Record classes into subdirectories, set the package attribute in the <table> tag !-->
<table name="book" package="bookstore">
<!-- generates the Book class under /path/to/project/build/classes/bookstore/Book.php !-->
```

```php
<?php
// Generated Active Record classes are empty, but they extend another generated class.
// That's why they are called "stub" classes
class Book extends BaseBook
{
}

// Most of the generated code is actually in the abstract Base- classes
abstract class BaseBook extends BaseObject implements Persistent
{
  // lots of generated code
}

// BaseObject and Persistent are classes bundled by Propel

// Do not alter the code of the Base- classes, as your modifications will be overridden
// each time you rebuild the model. Instead, add your custom code to the stub class
class Book extends BaseBook
{
  public function getCapitalTitle()
  {
    return strtoupper($this->getTitle());
  }
}

// To generate Active Record classes using a particular namespace in PHP 5.3,
// set the namespace attribute in the <table> tag.
// <table name="book" namespace="Bookstore">
// generates the following stub Active Record class:
namespace Bookstore;
use Bookstore\om\BaseBook;

class Book extends BaseBook
{
}
```

>**Tip**<br />See the [PHP 5.3 Namespaces](../cookbook/namespaces) chapter for more information on namespace usage in Propel.

## Generated Getter and Setter ##

```php
<?php
// For each column, Propel generates a setter method, also called "mutator"
$book->setTitle('War and Peace');
$book->setISBN('067003469X');
$book->setAuthorId(456745);

// For each column, Propel also generates a getter method, also called "accessor"
echo $book->getTitle();    // 'War and Peace'
echo $book->getISBN();     // '067003469X'
echo $book->getAuthorId(); // 456745

// Every class has a getPrimaryKey() method.
// For tables with single column PK, it is a synonym for the PK getter
echo $book->getPrimaryKey(); // 1234
// same as
echo $book->getId();         // 1234
// For tables with composite PKs, getPrimaryKey() returns an array
print_r($bookOpinion->getPrimaryKey()); // array(1234, 67)
```

By default, Propel uses a CamelCase version of the column name for these methods:

|  column name  |  getter & setter method names
|---------------|---------------------------------------
| title         | `getTitle()`, `setTitle()`
| is_published  | `getIsPublished()`, `setIsPublished()`
| author_id     | `getAuthorId()`, `setAuthorId()`
| isbn          | `getIsbn()`, `setIsbn()`

To use a custom name for these methods, set the `phpName` attribute in the `<column>` tag

```xml
<!-- set the phpName to have Uppercase getter and setter in PHP !-->
<column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
<!-- getISBN(), setISBN() !-->

<!-- set the phpName to customize the PHP name when you don't control the column name !-->
<column name="bz_ygt" required="true" type="VARCHAR" size="24" phpName="Title" />
<!-- getTitle(), setTitle() !-->
```

>**Tip**<br />Calling the setter on an autoincremented PK will throw an exception as soon as you try to save the object. You can allow PK inserts on such columns by setting the `allowPkInsert` attribute to `true` in the `<table>` tag of the XML schema.
<br />
>**Tip**<br />Calling a getter doesn't issue any database query, except for lazy-loaded columns.

## Persistence Methods ##

Active Record objects provide only two methods that may alter the data stored in the database: `save()`, and `delete()`.

```php
<?php
// To insert an object to the database, call the save() method
$book = new Book();
$book->setTitle('War and Peas');
$book->save();
// INSERT INTO book (title) VALUES ('War and Peace')

// On tables with autoincremented PKs, the PK value is available immediately after saving
echo $book->getId(); // 1234

// To update an object in the database, also use save().
// Propel knows when an object is new and when it was already persisted
// Therefore it correctly translates save() to either INSERT or UPDATE in SQL
$book->setTitle('War and Peace');
$book->save();
// UPDATE book SET title = 'War and Peace' WHERE id = 1234

// Generated SQL statements use PDO for binding, so the database is safe from SQL injections
// http://en.wikipedia.org/wiki/SQL_injection
$title = $_REQUEST['title'];
$book->setTitle($title);
$book->save(); // no need to worry

// Propel inspects changes in the properties of Active Record objects before saving,
// so calling save() on an unchanged object issues no query to the database
$book->save(); // no additional query

// To delete an object from the database, call the delete() method
$book->delete();
// DELETE FROM book WHERE id = 1234

// All persistence methods accept a connection object
$con = Propel::getWriteConnection(BookTableMap::DATABASE_NAME);
$book->delete($con);
```

## Relationship Getters and Setters ##

Consider the previous `book` table, now with a foreign key to an `author` table:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
  <column name="author_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="author">
    <reference local="author_id" foreign="id" />
  </foreign-key>
</table>
<table name="author" >
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="first_name" required="true" type="VARCHAR" size="128" />
  <column name="last_name" required="true" type="VARCHAR" size="128"/>
</table>
```

Based on this schema, Propel defines:

* A many-to-one relationship from the `Book` class to the `Author` class
* A one-to-many relationship from the `Author` class to the `Book` class

See the [Relationships documentation](../documentation/04-relationships) for more details.

For each relationship, Propel generates additional getters and setters.

### One-to-many Relationships ###

```php
<?php
// On columns holding a Foreign Key, Propel adds a getter and a setter for the related object
$author = new Author();
$author->setFirstName('Leo');
$author->setLastName('Tolstoi');
$book = new Book();
$book->setTitle('War and Peace');
// A Book has a one Author, therefore Propel generates Book::setAuthor() and Book::getAuthor() methods
$book->setAuthor($author);
echo $book->getAuthor()->getLastName(); // Tolstoi
// This allows to relate two objects without worrying about the primary and foreign keys,
// and it even works on objects not yet persisted.
```

>**Tip**<br />By default, Propel uses the name of the Active Record class to generate the related object getter and setter. However, you can customize this name by setting the `phpName` attribute in the `<foreign-key>` tag:

```xml
<table name="book">
  <foreign-key foreignTable="author" phpName="Writer">
    <reference local="author_id" foreign="id" />
  </foreign-key>
</table>
<!-- Generated methods will then be Book::setWriter(), and Book::getWriter() -->
```

### Many-to-one Relationships ###

```php
<?php
// On the other member of the relationship, Propel generates 4 methods instead of 2
$book = new Book();
$book->setTitle('War and Peace');
$author = new Author();
$author->setFirstName('Leo');
$author->setLastName('Tolstoi');
// An Author has many Books, therefore Propel generates Author::addBook() and Author::getBooks() methods
$author->addBook($book);
echo $author->getBooks(); // array($book)
// Propel also generates two other methods on that part of the relationship
echo $author->countBooks(); // 1
$author->clearBooks(); // removes the relationship
```

>**Tip**<br />As for one-to-many relationships, you can customize the phpName used to forge these method names, by setting the `refPhpName` attribute in the `<foreign-key>` tag:

```xml
<table name="book">
  <foreign-key foreignTable="author" refName="Publication">
    <reference local="author_id" foreign="id" />
  </foreign-key>
</table>
<!-- Generated methods will then be Author::addPublication(), Author::getPublications(),
     Author::countPublications(), and Author::clearPublications() -->
```

### Many-to-many relationships ###

A Many-to-many relationship is defined by a cross reference table. Both sides of the relationship see it as a one-to-many relationship.

```php
<?php
// If a Book can be written by several Authors, then they share a many-to-many relationship
// Therefore Propel generates the following methods
Book::addAuthor(), Book::getAuthors(), Book::countAuthors(), Book::clearAuthors()
Author::addBook(), Author::getBooks(), Author::countBooks(), Author::clearBooks()
```

### One-to-one relationships ###

If a table contains a foreign key that is also a primary key, Propel sees it as a one-to-one relationship, seen as a many-to-one relationship from both sides.

```php
<?php
// If a User has one Profile using the user PK as foreign key, that's a one-to-one relationship.
// Therefore Propel generates the following methods:
User::getProfile(), User::setProfile()
Profile::getUser(), Profile::setUser()
```

## Datatype-Specific Getter and Setter ##

For some column types, Propel generates getter and setters with additional functionality.

### Temporal Columns ###

```php
<?php
// No need to convert a date or time before using the setter on a temporal column
// (i.e. of type DATE, TIME, TIMESTAMP, BU_DATE, or BU_TIMESTAMP).
// The generated setter accepts strings, timestamps, and DateTime objects,
// And automatically converts the argument to the internal storage format.
// So the three following calls are equivalent:
$book->setCreatedAt('now');
$book->setCreatedAt(time());
$book->setCreatedAt(new DateTime());

// The generated getter returns a DateTime object, but accepts a format string as argument
echo $book->getCreatedAt(); // DateTime Object
echo $book->getCreatedAt('U'); // 1291065396 (timestamp)
echo $book->getCreatedAt('Y-m-d H:i:s'); // 2010-11-29 22:20:21
```

### Boolean Columns ###

```php
<?php
// The generated setter converts non-boolean values to boolean in a smart way.
// The following statements are equivalent:
$book->setIsPublished(true);
$book->setIsPublished('true');
$book->setIsPublished('1');
$book->setIsPublished('yes');
$book->setIsPublished('on');
// Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
```

### BLOB Columns ###

```php
<?php
// The setter for a BLOB column accepts either a string or a stream as parameter.
// Setting the value from a string
$media = new Media();
$media->setCoverImage(file_get_contents("/path/to/file.ext"));
// Setting the value from a stream
$fp = fopen("/path/to/file.ext", "rb");
$media = new Media();
$media->setCoverImage($fp);

// The getter for a BLOB column returns a PHP stream resource,
// or NULL if the value is not set in the database.
$media = MediaQuery::create()->findPk(43564376);
$fp = $media->getCoverImage();
if ($fp !== null) {
  echo stream_get_contents($fp);
}
```

>**Tip**<br />CLOB (_Character_ Locator Objects) are treated as strings in Propel, so the getter and setter for a CLOB column have no special functionality.

### ENUM columns ###

```php
<?php
// ENUM columns accept only values chosen from a list of permitted values
// that are enumerated explicitly in the column specification at table creation time.

// Example for the book table:
// <column name="style" type="ENUM" valueSet="novel, essay, poetry" />
$book = new Book();
$book->setStyle('novel');
echo $book->getStyle(); // novel
// An enum is stored as a TINYINT in the database

// Each value in an ENUM column has a related constant in the TableMap class
// Your IDE with code completion should love this
echo BookTableMap::STYLE_NOVEL;  // 'novel'
echo BookTableMap::STYLE_ESSAY;  // 'essay'
echo BookTableMap::STYLE_POETRY; // 'poetry'
// The TableMap class also gives access to list of available values
print_r(BookTableMap::getValueSet(BookTableMap::STYLE)); // array('novel', 'essay', 'poetry')
```

### OBJECT columns ###

```php
<?php
// OBJECT columns allow to store PHP objects in the database.
// That's especially useful for Value Objects

// The 'house' table has a 'coordinates' column of type OBJECT
$house = new House();
$house->setCoordinates(new GeographicCoordinates(48.8527, 2.3510));
echo $house->getCoordinates()->isInNorthernHemisphere(); // true
// The setter serializes the PHP object and stores it as a string
// The getter deserializes the string into a PHP object
// All that is transparent to the end user, who just manipulates PHP objects

class GeographicCoordinates
{
  public $latitude, $longitude;

  public function __construct($latitude, $longitude)
  {
    $this->latitude = $latitude;
    $this->longitude = $longitude;
  }

  public function isInNorthernHemisphere()
  {
    return $this->latitude > 0;
  }
}
```

>**Tip**<br />OBJECT columns are searchable, given an object as the search value. See the [ColumnFilterMethods Query reference](./model-criteria#column-filter-methods) for more details.

### ARRAY columns ###

```php
<?php
// ARRAY columns allow to store simple PHP arrays in the database.
// Nested arrays and associative arrays are not accepted.

// The 'book' table has a 'tags' column of type ARRAY
$book = new Book();
$book->setTags(array('novel', 'russian'));
print_r($book->getTags()); // array('novel', 'russian')
// The setter serializes the PHP array and stores it as a string
// The getter deserializes the string into a PHP array
// All that is transparent to the end user, who just manipulates PHP arrays

// If the column name is plural, Propel also generates hasXXX(), addXXX(),
// and removeXXX() methods, where XXX is the singular column name
echo $book->hasTag('novel'); // true
$book->addTag('romantic');
print_r($book->getTags()); // array('novel', 'russian', 'romantic')
$book->removeTag('russian');
print_r($book->getTags()); // array('novel', 'romantic')
```

>**Tip**<br />ARRAY columns are searchable, given an array or a scalar as the search value. See the [ColumnFilterMethods Query reference](./model-criteria#column-filter-methods) for more details.

## Generic Getters and Setters ##

```php
<?php
// Each Active Record class offers generic getter and setter by name
$book = new Book();
$book->setByName('Title', 'War and Peace');
echo $book->getByName('Title'); // War and Peace
// The name used is the column phpName - the same name used in generated getters and setters.
// You can also use the table column name by adding a converter argument
$book->setByName('title', 'War and Peace', BookTableMap::TYPE_FIELDNAME);
echo $book->getByName('title', BookTableMap::TYPE_FIELDNAME); // War and Peace

// Each Active Record class also offers generic getter and setter by position
$book->setByPosition(2, 'War and Peace'); // 'title' is the second column of the table
echo $book->getPosition(2); // War and Peace

// Each ActiveRecord class offers the ability to dump to and populate from an array
$properties = array(
  'Title'    => 'War and Peace',
  'ISBN'     => '067003469X',
  'AuthorId' => 456745
);
$book = new Book();
$book->fromArray($properties);
echo $book->getTitle(); // 'War and Peace'
print_r($book->toArray());
// array(
//  'Id'       => null
//  'Title'    => 'War and Peace',
//  'ISBN'     => '067003469X',
//  'AuthorId' => 456745
// )

// As with getByName() and setByName(), you can use the table column names by adding a converter argument
print_r($book->toArray(BookTableMap::TYPE_FIELDNAME));
// array(
//  'id'        => null
//  'title'     => 'War and Peace',
//  'isbn'      => '067003469X',
//  'author_id' => 456745
// )

// If the class has lazy-loaded columns, those are included by default in the output of toArray().
// To exclude them, set the second argument to false.

// If the class has related objects, they are not included by default in the output of toArray().
// To include them, set the third argument to true.
print_r($book->toArray($keyType = BaseTableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $includeForeignObjects = true));
// array(
//  'Id'       => null
//  'Title'    => 'War and Peace',
//  'ISBN'     => '067003469X',
//  'AuthorId' => 456745,
//  'Author' => array(
//    'Id'        => 456745,
//    'FirstName' => 'Leo',
//    'LastName'  => 'Tolstoi',
//   )
// )
```

>**Tip**<br />If you never use these generic getters and setters, you can disable their generation to clean up the Active Record class by modifying the `build.properties` as follows:

```ini
propel.addGenericAccessors = false
propel.addGenericMutators  = false
```

## Validation ##

Active Record classes for tables using validate behavior have three additional methods: `validate()`, `getValidationFailures()` and `loadValidatorMetadata()`.

```php
<?php
$book = new Book();
$book->setTitle('a'); // too short for a length validator
if ($book->validate()) {
  // no validation errors, so the data can be persisted
  $book->save();
} else {
  // Something went wrong.
  // Use the validationFailures to check what
  foreach ($book->getValidationFailures() as $failure) {
    echo $failure->getMessage() . "\n";
  }
}
```

See the [Validate behavior documentation](../behaviors/validate.html) for more details.

## Import and Export Capabilities ##

Active Record objects have the ability to be converted to and from a string, using any of the XML, YAML, JSON, and CSV formats. This ability uses magic methods, but the phpDoc blocks defined in the `BaseObject` class make the related methods visible to an IDE.

Each Active Record object accepts the following method calls:

| format   | dumper     |parser
|----------|------------|--------------
| XML      | `toXML()`  | `fromXML()`
| YAML     | `toYAML()` | `fromYAML()`
| JSON     | `toJSON()` | `fromJSON()`
| CSV      | `toCSV()`  | `fromCSV()`

```php
<?php
// Dumping an object to a string
echo $book->toXML();
// <?xml version="1.0" encoding="UTF-8"?>
// <data>
//   <Id>1234</Id>
//   <Title><![CDATA[War and Peace]]></Title>
//   <ISBN><![CDATA[067003469X]]></ISBN>
//   <AuthorId>456745</AuthorId>
//   <Author>
//     <Id>456745</Id>
//     <FirstName><![CDATA[Leo]]></FirstName>
//     <LastName><![CDATA[Tolstoi]]></LastName>
//   </Author>
// </data>

echo $book->toYAML();
//   Id: 1234
//   Title: War and Peace
//   ISBN: 067003469X
//   AuthorId: 456745
//   Author:
//     Id: 456745
//     FirstName: Leo
//     LastName: Tolstoi

echo $book->toJSON();
// {
//   "Id":1234,
//   "Title":"War and Peace",
//   "ISBN":"067003469X",
//   "AuthorId":456745,
//   "Author": {
//     "Id":456745,
//     "FirstName":"Leo",
//     "LastName":"Tolstoi"
//   }
// }

// Parsing a string into an Active Record object
$xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <Id>1234</Id>
  <Title><![CDATA[War and Peace]]></Title>
  <ISBN><![CDATA[067003469X]]></ISBN>
  <AuthorId>456745</AuthorId>
  <Author>
    <Id>456745</Id>
    <FirstName><![CDATA[Leo]]></FirstName>
    <LastName><![CDATA[Tolstoi]]></LastName>
  </Author>
</data>
EOF;
$book = new Book();
$book->fromXML($xml);
echo $book->getTitle(); // War and Peace

// Active Record Objects also provide generic importFrom() and exportTo() methods,
// accepting either a format name, or a parser instance (extending PropelParser)
$book->importFrom('XML', $xml);
echo $book->exportTo('XML');
// This allows for custom parser formats
```

>**Tip**<br />Active Record objects use YAML as their default string representation. That means that echoing an Active Record object returns the result of `toYAML()`. You can customize the default string representation on a per table basis by setting the `defaultStringFormat` attribute in the `<table>` tag.

```xml
<table name="book" defaultStringFormat="XML">
```

## Virtual Columns ##

Propel queries allow to hydrate additional columns from related objects, at runtime. These columns can be fetched using the `getVirtualColumn($name)` method, or using the magic getter supported by the generated `__call()` method:

```php
<?php
$book = BookQuery::create()
  ->filterByTitle('War and Peace')
  ->join('Book.Author')
  ->withColumn('Author.LastName', 'AuthorName')
  ->find();
echo $author->getVirtualColumn('AuthorName'); // Tolstoi
echo $author->getAuthorName(); // Tolstoi
```

See the [Query API reference](./model-criteria) for more details.

## Lifecycle Events ##

To execute custom code before or after any of the persistence methods, just create methods using any of the following names in a stub Active Record class:

```php
<?php
// save() hooks
preInsert()            // code executed before insertion of a new object
postInsert()           // code executed after insertion of a new object
preUpdate()            // code executed before update of an existing object
postUpdate()           // code executed after update of an existing object
preSave()              // code executed before saving an object (new or existing)
postSave()             // code executed after saving an object (new or existing)
// delete() hooks
preDelete()            // code executed before deleting an object
postDelete()           // code executed after deleting an object
```

See the [Behaviors guide](../documentation/07-behaviors) for more details.

## Persistence Status ##

```php
<?php
// At all times, you can monitor the status of an Active Record object regarding persistence
$book = new Book();

// isNew() returns true if the object has not yet been persisted, false otherwise
echo $book->isNew(); // true
$book->save();
echo $book->isNew(); // false

// isModified() returns true if some columns were changed since the last save, false otherwise
echo $book->isModified(); // false
$book->setTitle('War and Peace');
echo $book->isModified(); // true
$book->save();
echo $book->isModified(); // false

// To cancel modifications on an object and return to the persisted state, call reload()
$book->setTitle('War and Peace and Love');
$book->reload();
// SELECT * FROM book WHERE id = 1234
echo $book->getTitle(); // War and Peace

// isDeleted() returns true if an object has been deleted, false otherwise.
// Note that deleted objects continue to live in the PHP space after deletion.
echo $book->isDeleted(); // false
$book->delete();
echo $book->isDeleted(); // true

// You can test and list the modified columns using isColumnModified() and getModifiedColumns()
// The function uses fully qualified column names (i.e. of type BaseTableMap::TYPE_COLNAME)
$book = new Book();
$book->setTitle('War and Peace');
echo $book->isColumnModified('book.ISBN'); // false
echo $book->isColumnModified('book.TITLE'); // true
print_r($book->getModifiedColumns());
// array('book.TITLE')
// To use column phpNames, just convert the parameter using translateFieldName()
$colName = BookTableMap::translateFieldName('Title', TableMap::TYPE_PHPNAME, TableMap::TYPE_COLNAME);
echo $book->isColumnModified($colname); // true
```

## Miscellaneous ##

```php
<?php
// Active Record objects have even more methods
echo $book->hasOnlyDefaultValues(); // returns true if the column values are default ones
echo $book->hashCode(); // returns a hash code for the instance

$book->clear(); // resets all the user-modified properties and returns the object to a new state
$book->clearAllReferences() // resets all collections of referencing foreign keys
// clear() and clearAllReferences() may help in PHP 5.2 to compensate PHP's inability to garbage collect

$book1 = BookQuery::create()->findPk(1234);
$book2 = book1->copy(); // creates a copy of an Active Record instance
echo $book1->equals($book2); // compares two ActiveRecord instances
```

## Conclusion ##

Active Record classes are not only an object-oriented tool to execute SQL queries in a database-independent way. They provide a lot of features to streamline day-to-day work with persisted objects. They can also be seen as a persistence ability that can be added to any PHP object.
