---
layout: documentation
title: What's new in Propel 1.6?
---

# What's new in Propel 1.6? #

Propel 1.6 is a new backwards compatible iteration of the Propel 1.x branch. As usual, don't forget to rebuild your model once you upgrade to this new version.

## Migrations ##

How do you manage changes to your database as the Model evolves and the schema changes? Calling the `sql` and `insert-sql` task each time the schema is updated has one dreadful drawback: it erases all the data in the database.

Starting with Propel 1.6, the `sql`-`insert-sql` sequence is replaced by the `diff`-`migrate` sequence:

    > propel-gen diff

    [propel-sql-diff] Reading databases structure...
    [propel-sql-diff] 1 tables imported from databases.
    [propel-sql-diff] Loading XML schema files...
    [propel-sql-diff] 2 tables found in 1 schema file.
    [propel-sql-diff] Comparing models...
    [propel-sql-diff] Structure of database was modified: 1 added table, 1 modified table
    [propel-sql-diff] "PropelMigration_1286484196.php" file successfully created in /path/to/project/build/migrations
    [propel-sql-diff]   Please review the generated SQL statements, and add data migration code if necessary.
    [propel-sql-diff]   Once the migration class is valid, call the "migrate" task to execute it.

    > propel-gen migrate

    [propel-migration] Executing migration PropelMigration_1286484196 up
    [propel-migration] 4 of 4 SQL statements executed successfully on datasource "bookstore"
    [propel-migration] Migration complete. No further migration to execute.

`diff` compares the schema to the database, and generates a class with all the required `ALTER TABLE` and `CREATE TABLE` statements to update the database structure. This migration class then feeds the `migrate` task, which connects to the database and executes the migrations - with no data loss.

Migrations are a fantastic way to work on complex projects with always evolving models ; they are also a great tool for team work, since migration classes can be shared among all developers. That way, when a developer adds a table to the model, a second developer just needs to run the related migration to have the table added to the table.

Propel migrations can also be executed incrementally - the new `up` and `down` tasks are there for that. And when you're lost in migration, call the `status` task to check which migrations were already executed, and which ones should be executed to update the database structure.

The Propel documentation offers [an entire chapter on Migrations](10-migrations.html) to explain how to use them and how they work.

Migrations only work on MySQL and PostgreSQL for now. On other platforms, you should continue to use `sql` and `insert-sql`.

## New Behaviors ##

Propel 1.6 ships with more core behaviors than ever.

### Versionable behavior ###

Once enabled on a table, the `versionable` behavior will store a copy of the ActiveRecord object in a separate table each time it is saved. This allows to keep track of the changes made on an object, whether to review modifications, or revert to a previous state.

The classic Wiki example is a good illustration of the utility of the `versionable` behavior:

```xml
<table name="wiki_page">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <column name="body" type="LONGVARCHAR" />
  <behavior name="versionable" />
</table>
```

After rebuild, the `WikiPage` model has versioning abilities:

```php
<?php
$page = new WikiPage();

// automatic version increment
$page->setTitle('Propel');
$page->setBody('Propel is a CRM built in PHP');
$page->save(); 
echo $page->getVersion(); // 1
$page->setBody('Propel is an ORM built in PHP5');
$page->save();
echo $page->getVersion(); // 2

// reverting to a previous version
$page->toVersion(1);
echo $page->getBody(); // 'Propel is a CRM built in PHP'
// saving a previous version creates a new one
$page->save();
echo $page->getVersion(); // 3

// checking differences between versions
print_r($page->compareVersions(1, 2));
// array(
//   'Body' => array(1 => 'Propel is a CRM built in PHP', 2 => 'Propel is an ORM built in PHP5'),
// );

// deleting an object also deletes all its versions
$page->delete();
```

The `versionable` behavior offers audit log functionality, so you can track who made a modification, when, and why:

```php
<?php
$page = new WikiPage();
$page->setTitle('PEAR');
$page->setBody('PEAR is a framework and distribution system for reusable PHP components');
$page->setVersionCreatedBy('John Doe');
$page->setVersionComment('First draft');
$page->save();
// do more modifications...

// list all modifications
foreach ($page->getAllVersions() as $pageVersion) {
  echo sprintf("'%s', Version %d, updated by %s on %s (%s)\n",
    $pageVersion->getTitle(),
    $pageVersion->getVersion(),
    $pageVersion->getVersionCreatedBy(),
    $pageVersion->getVersionCreatedAt(),
    $pageVersion->getVersionComment(),
  );
}
// 'PEAR', Version 1, updated by John Doe on 2010-12-21 22:53:02 (First draft)
// 'PEAR', Version 2, updated by ...
```

If it was just for that, the `versionable` behavior would already be awesome. Versioning is a very common feature, and there is no doubt that this behavior will replace lots of boilerplate code. Consider the fact that it's very configurable, [fully documented](../behaviors/versionable.html), and unit tested, and there is no reason to develop your own versioning layer.

But there is more. The `versionable` behavior also works on relationships. If the `WikiPage` has one `Category`, and if the `Category` model also uses the `versionable` behavior, then each time a `WikiPage` is saved, it saves the version of the related `Category` it is related to, and it is able to restore it:

```php
<?php
$category = new Category();
$category->setName('Libraries');
$page = new WikiPage();
$page->setTitle('PEAR');
$page->setBody('PEAR is a framework and distribution system for reusable PHP components');
$page->setCategory($category);
$page->save(); // version 1

$page->setTitle('PEAR - PHP Extension and Application Repository');
$page->save(); // version 2

$category->setName('PHP Libraries');
$page->save(); // version 3

$page->toVersion(1);
echo $page->getTitle(); // 'PEAR'
echo $page->getCategory()->getName(); // 'Libraries'
$page->toVersion(3);
echo $page->getTitle(); // 'PEAR - PHP Extension and Application Repository'
echo $page->getCategory()->getName(); // 'PHP Libraries'
```

Now the versioning is not limited to a single class anymore. You can even design a fully versionable **application** - it all depends on your imagination.

### I18n behavior ###

The `i18n` behavior provides support for internationalization on the model. Using this behavior, the text columns of an !ActiveRecord object can have several translations.

This is useful in multilingual applications, such as an e-commerce website selling home appliances across the world. This website should keep the name and description of each item separated from the other details, and keep one version for each supported language.

Starting with Propel 1.6, this is possible by adding a simple `<behavior>` tag to the table that needs internationalization:

```xml
#!xml
<table name="item">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="name" type="VARCHAR" required="true" />
  <column name="description" type="LONGVARCHAR" />
  <column name="price" type="FLOAT" />
  <column name="is_in_store" type="BOOLEAN" />
  <behavior name="i18n">
    <parameter name="i18n_columns" value="name, description" />
  </behavior>
</table>
```

In this example, the `name` and `description` columns are moved to a new table, called `item_i18n`, which shares a many-to-one relationship with Item - one Item has many Item translations. But all this happens in the background; for the end user, everything happens as if there were only one main `Item` object:

```php
<?php
$item = new Item();
$item->setPrice('12.99');
$item->setName('Microwave oven');
$item->save();
```

This creates one record in the `item` table with the price, and another in the `item_i18n` table with the English (default language) translation for the name. Of course, you can add more translations:

```php
<?php
$item->setLocale('fr_FR');
$item->setName('Four micro-ondes');
$item->setLocale('es_ES');
$item->setName('Microondas');
$item->save();
```

This works both for setting AND for getting internationalized columns:

```php
<?php
$item->setLocale('en_US');
echo $item->getName(); //'Microwave oven'
$item->setLocale('fr_FR');
echo $item->getName(); // 'Four micro-ondes'
```

**Tip**: The big advantage of Propel behaviors is that they use code generation. Even though it's only a proxy method to the `ItemI18n` class, `Item::getName()` has all the phpDoc required to make your IDE happy.

This new behavior also adds special capabilities to the Query objects. The most interesting allows you to execute less queries when you need to query for an Item and one of its translations - which is common to display a list of items in the locale of the user:

```php
<?php
$items = ItemQuery::create()->find(); // one query to retrieve all items
$locale = 'en_US';
foreach ($items as $item) {
  echo $item->getPrice();
  $item->setLocale($locale);
  echo $item->getName(); // one query to retrieve the English translation
}
```

This code snippet requires 1+n queries, n being the number of items. But just add one more method call to the query, and the SQL query count drops to 1:

```php
<?php
$items = ItemQuery::create()
  ->joinWithI18n('en_US')
  ->find(); // one query to retrieve both all items and their translations
foreach ($items as $item) {
  echo $item->getPrice();
  echo $item->getName(); // no additional query
}
```

In addition to hydrating translations, `joinWithI18n()` sets the correct locale on results, so you don't need to call `setLocale()` for each result.

**Tip**: `joinWithI18n()` adds a left join with two conditions. That means that the query returns all items, including those with no translation. If you need to return only objects having translations, add `Criteria::INNER_JOIN` as second parameter to `joinWithI18n()`.

Last but not least, Propel's `i18n` behavior is a drop-in replacement for symfony's `i18n` behavior. That means that with a couple more parameters, the locale can be accessed using `setCulture()`, and the `i18n_columns` parameter can be omitted if you explicit the `i18n` table.

Just like the `versionable` behavior, the `i18n` behavior is thoroughly unit-tested and [fully documented]((../behaviors/i18n.html)).

## XML/YAML/JSON/CSV Parsing and Dumping ##

ActiveRecord and Collection objects now have the ability to be converted to and from a string, using any of the XML, YAML, JSON, and CSV formats.

The syntax is very intuitive: ActiveRecord and collection objects now offer a `toXML()` and a `fromXML()` method (same for YAML, JSON, and CSV). Here are a few examples:

```php
<?php
// dump a collection to YAML
$books = BookQuery::create()
  ->orderByTitle()
  ->joinWith('Author')
  ->find();
echo $books->toYAML();
// Book_1:
//   Id: 123
//   Title: Pride and Prejudice
//   AuthorId: 456
//   Author:
//     Id: 456
//     FirstName: Jane
//     LastName: Austen
// Book_2:
//   Id: 789
//   Title: War and Peace
//   AuthorId: 147
//   Author:
//     Id: 147
//     FirstName: Leo
//     LastName: Tolstoi

// parse an XML string into an object
$bookString = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <Id>9012</Id>
  <Title><![CDATA[Don Juan]]></Title>
  <ISBN><![CDATA[0140422161]]></ISBN>
  <Price>12.99</Price>
  <PublisherId>1234</PublisherId>
  <AuthorId>5678</AuthorId>
</data>
EOF;
$book = new Book();
$book->fromXML($bookString);
echo $book->getTitle(); // Don Juan
```

## Model Objects String Representation ##

Taking advantage of the dumping abilities just introduced, all ActiveRecord objects now have a string representation based on a YAML dump of their properties:

```php
<?php
$author = new Author();
$author->setFirstName('Leo');
$author->setLastName('Tolstoi');
echo $author;
// Id: null
// FirstName: Leo
// LastName: Tolstoi
// Email: null
// Age: null
```

**Tip**: Tables with a column using the `isPrimaryString` attribute still output the value of a single column as string representation.

`PropelCollection` objects also take advantage from this possibility:

```php
<?php
$authors = AuthorQuery::create()
  ->orderByLastName()
  ->find();
echo $authors;
// Author_0:
//   Id: 456
//   FirstName: Jane
//   LastName: Austen
//   Email: null
//   Age: null
// Author_1:
//   Id: 147
//   FirstName: Leo
//   LastName: Tolstoi
//   Email: null
//   Age: null
```

If you want to use another format for the default string representation instead of YAML, you can set the `defaultStringFormat` attribute to any of the supported formats in either the `<database>` or the `<table>` elements in the XML schema:

```xml
<table name="publisher" defaultStringFormat="XML">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="name" required="true" type="VARCHAR" size="128" />
</table>
```

```php
<?php
$publisher = new Publisher();
$publisher->setName('Penguin');
echo $publisher;
// <?xml version="1.0" encoding="UTF-8"?>
// <data>
//   <Id></Id>
//   <Name><![CDATA[Peguin]]></Name>
// </data>
```

## Easier OR in Queries ##

Combining two generated filters with a logical `OR` used to be impossible in previous Propel versions - the alternative was to use `orWhere()` or `combine()`, but that meant losing all the smart defaults of generated filters.

Propel 1.6 introduces a new method for Query objects: `_or()`. It just specifies that the next condition will be combined with a logical `OR` rather than an `AND`.

```php
<?php
// Basic usage: _or() as a drop-in replacement for orWhere()
$books = BookQuery::create()
  ->where('Book.Title = ?', 'War And Peace')
  ->_or()
  ->where('Book.Title LIKE ?', 'War%')
  ->find();
// SELECT * FROM book WHERE book.TITLE = 'War And Peace' OR book.TITLE LIKE 'War%'

// _or() also works on generated filters:
$books = BookQuery::create()
  ->filterByTitle('War And Peace')
  ->_or()
  ->filterByTitle('War%')
  ->find();
// SELECT * FROM book WHERE book.TITLE = 'War And Peace' OR book.TITLE LIKE 'War%'

// _or() also works on embedded queries
$books = BookQuery::create()
  ->filterByTitle('War and Peace')
  ->_or()
  ->useAuthorQuery()
    ->filterByName('Leo Tolstoi')
  ->endUse()
  ->find();
// SELECT book.* from book
// INNER JOIN author ON book.AUTHOR_ID = author.ID
// WHERE book.TITLE = 'War and Peace'
//    OR author.NAME = 'Leo Tolstoi'
```

This new method is implemented in the `Criteria` class, so it also works for the old-style queries (using `add()` for conditions).

**Tip**: Since `ModelCriteria::orWhere()` is a synonym for `->_or()->where()`, it is now deprecated.

## Multiple Buildtime Connections ##

Propel 1.5 used the `build.properties` for buildtime connection settings. This had one major drawback: it used to be impossible to deal with several connections at buildtime, let alone several RDBMS.

In Propel 1.6, you can write your buildtime connection settings in a `buildtime-conf.xml` file. The format is the same as the `runtime-conf.xml` file, so a good starting point is to copy the runtime configuration, and change the settings for users with greater privileges.

Here is an example buildtime configuration file that defines a MySQL and a SQLite connection:

```xml
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>mysql</adapter>
        <connection>
          <dsn>mysql:host=localhost;dbname=bookstore</dsn>
          <user>testuser</user>
          <password>password</password>
        </connection>
      </datasource>
      <datasource id="cms">
        <adapter>sqlite</adapter>
        <connection>
          <dsn>sqlite:/opt/databases/mydb.sq3</dsn>
        </connection>
      </datasource>
    </datasources>
  </propel>
</config>
```

Now that Propel can deal with database vendors at buildtime more accurately, the generated classes offer more optimizations for the database they rely one. Incidentally, that means that you should rebuild your model if you use different database vendors. That includes cases when your development and production environments use different vendors.

## Support For SQL Schemas ##

For complex models showing a large number of tables, database administrators often like to group tables into "SQL schemas", which are namespaces in the SQL server. Starting with Propel 1.6, it is now possible to assign tables to SQL schemas using the `schema` attribute in the `<database>` of the `<table>` tag:

```xml
<database name="my_connection">
  <table name="book" schema="bookstore">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="title" type="VARCHAR" required="true" />
    <column name="author_id" type="INTGER" />
    <foreign-key foreignTable="author" foreignSchema="people" onDelete="setnull" onUpdate="cascade">
      <reference local="author_id" foreign="id" />
    </foreign-key>
  </table>
  <table name="author" schema="people">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="name" type="VARCHAR" required="true" />
  </table>
</database>
```

**Tip**: This feature is only available in PostgreSQL, MSSQL, and MySQL. The `schema` attribute is simply ignored in Oracle and SQLite.

Propel also supports foreign keys between tables assigned to two different schemas. For MySQL, where "SQL schema" is a synonym for "database", this allows for cross-database queries.

The Propel documentation contains a new tutorial about the SQL schema attributes and usage, called [Using SQL Schemas](../cookbook/using-sql-schemas.html).

## Foreign Key Filters Now Accept a Collection ##

The generated `filterByRelationName()` methods in the model queries now accept a `PropelCollection` as argument. This will allow you to keep using objects and avoid dealing with foreign keys completely:

```php
<?php
// get young authors
$authors = AuthorQuery::create()
  ->filterByAge(array('max' => 35))
  ->find(); // $authors is a PropelObjectCollection
// get all books by young authors
$books = BookQuery::create()
  ->filterByAuthor($authors) // <= That's new
  ->find();
```

## Join With Several Conditions ##

Creating a Join with more than one condition is now very easy. Just call `ModelCriteria::addJoinCondition($joinName, $condition)` after a `ModelCriteria::join()` to add further conditions to a join:

```php
<?php
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->addJoinCondition('Book', 'Book.Title IS NOT NULL')
  ->find();
// SELECT * FROM author
// INNER JOIN book ON (author.ID=book.AUTHOR_ID AND book.TITLE IS NOT NULL);
```

If you need to bind a variable to the condition, set the variable as last parameter of the `addJoinCondition()` call. Propel correctly binds the value using PDO:

```php
<?php
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->addJoinCondition('Book', 'Book.Title LIKE ?', 'War%')
  ->find();
// SELECT * FROM author
// INNER JOIN book ON (author.ID=book.AUTHOR_ID AND book.TITLE LIKE 'War%');
```

**Tip**: `Criteria::addMultipleJoin()`, which allowed the same feature to some extent in previous versions, is now deprecated, since it was vulnerable in SQL injection attacks.

## Model-Only Relationships ##

Propel models can share relationships even though the underlying tables aren't linked by a foreign key. This ability may be of great use when using Propel on top of a legacy database.

For example, a `review` table designed for a MyISAM database engine is linked to a `book` table by a simple `book_id` column:

```xml
<table name="review">
  <column name="review_id" type="INTEGER" primaryKey="true" required="true"/>
  <column name="reviewer" type="VARCHAR" size="50" required="true"/>
  <column name="book_id" required="true" type="INTEGER"/>
</table>
```

To enable a model-only relationship, add a `<foreign-key>` tag using the `skipSql` attribute, as follows:

```xml
<table name="review">
  <column name="review_id" type="INTEGER" primaryKey="true" required="true"/>
  <column name="reviewer" type="VARCHAR" size="50" required="true"/>
  <column name="book_id" required="true" type="INTEGER"/>
  <!-- Model-only relationship -->
  <foreign-key foreignTable="book" onDelete="CASCADE" skipSql="true">
    <reference local="book_id" foreign="id"/>
  </foreign-key>
</table>
```

Such a foreign key is not translated into SQL when Propel builds the table creation or table migration code. It can be seen as a "virtual foreign key". However, on the PHP side, the `Book` model actually has a one-to-many relationship with the `Review` model. The generated !ActiveRecord and !ActiveQuery classes take advantage of this relationship to offer smart getters and filters. 

## Advanced Column Types ##

Propel 1.6 introduces a new set of column types. The database-agnostic implementation allows these column types to work on all supported RDBMS.

### ENUM Columns ###

Although stored in the database as integers, ENUM columns let users manipulate a set of predefined values, without worrying about their storage.

```xml
<table name="book">
  ...
  <column name="style" type="ENUM" valueSet="novel, essay, poetry" />
</table>
```

```php
<?php
// The ActiveRecord setter and getter let users use any value from the valueSet
$book = new Book();
$book->setStyle('novel');
echo $book->getStyle(); // novel
// Trying to set a value not in the valueSet throws an exception

// Each value in an ENUM column has a related constant in the TableMap class
// Your IDE with code completion should love this
echo BookTableMap::STYLE_NOVEL;  // 'novel'
echo BookTableMap::STYLE_ESSAY;  // 'essay'
echo BookTableMap::STYLE_POETRY; // 'poetry'
// The TableMap class also gives access to list of available values
print_r(BookTableMap::getValueSet(BookTableMap::STYLE)); // array('novel', 'essay', 'poetry')

// ENUM columns are also searchable, using the generated filterByXXX() method
// or other ModelCriteria methods (like where(), orWhere(), condition())
$books = BookQuery::create()
  ->filterByStyle('novel')
  ->find();
```

### OBJECT Columns ###

An `OBJECT` column can store PHP objects (mostly Value Objects) in the database. The column setter serializes the object, which is later stored to the database as a string. The column getter unserializes the string and returns the object. Therefore, for the end user, the column contains an object.

```php
<?php
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

// The 'house' table has a 'coordinates' column of type OBJECT
$house = new House();
$house->setCoordinates(new GeographicCoordinates(48.8527, 2.3510));
echo $house->getCoordinates()->isInNorthernHemisphere(); // true
$house->save();
```

Not only do `OBJECT` columns benefit from these smart getter and setter in the generated Active Record class, they are also searchable using the generated `filterByXXX()` method in the query class:

```php
<?php
$house = HouseQuery::create()
  ->filterByCoordinates(new GeographicCoordinates(48.8527, 2.3510))
  ->find();
```

Propel looks in the database for a serialized version of the object passed as parameter of the `filterByXXX()` method.

### ARRAY Columns ###

An `ARRAY` column can store a simple PHP array in the database (nested arrays and associative arrays are not accepted). The column setter serializes the array, which is later stored to the database as a string. The column getter unserializes the string and returns the array. Therefore, for the end user, the column contains an array.

```php
<?php
// The 'book' table has a 'tags' column of type ARRAY
$book = new Book();
$book->setTags(array('novel', 'russian'));
print_r($book->getTags()); // array('novel', 'russian')

// If the column name is plural, Propel also generates hasXXX(), addXXX(),
// and removeXXX() methods, where XXX is the singular column name
echo $book->hasTag('novel'); // true
$book->addTag('romantic');
print_r($book->getTags()); // array('novel', 'russian', 'romantic')
$book->removeTag('russian');
print_r($book->getTags()); // array('novel', 'romantic')
```

Propel doesn't use `serialize()` to transform the array into a string. Instead, it uses a special serialization function, that makes it possible to search for values of `ARRAY` columns.

```php
<?php
// Search books that contain all the specified tags
$books = BookQuery::create()
  ->filterByTags(array('novel', 'russian'), Criteria::CONTAINS_ALL)
  ->find();

// Search books that contain at least one of the specified tags
$books = BookQuery::create()
  ->filterByTags(array('novel', 'russian'), Criteria::CONTAINS_SOME)
  ->find();

// Search books that don't contain any of the specified tags
$books = BookQuery::create()
  ->filterByTags(array('novel', 'russian'), Criteria::CONTAINS_NONE)
  ->find();

// If the column name is plural, Propel also generates singular filter methods
// expecting a scalar parameter instead of an array
$books = BookQuery::create()
  ->filterByTag('russian')
  ->find();
```

**Tip**: Filters on array columns translate to SQL as LIKE conditions. That means that the resulting query often requires a full table scan, and is not suited for large tables.

**Warning**: Only generated Query classes (through generated `filterByXXX()` methods) and `ModelCriteria` (through `where()`, `orWhere()`, and `condition()`) allow conditions on `ENUM`, `OBJECT`, and `ARRAY` columns. `Criteria` alone (through `add()`, `addAnd()`, and `addOr()`) does not support conditions on such columns.

## Table Subqueries (a.k.a "Inline Views") ##

SQL supports table subqueries to solve complex cases that a single query can't solve, or to optimize slow queries with several joins. For instance, to find the latest book written by every author in SQL, it usually takes a query like the following:

```sql
SELECT book.ID, book.TITLE, book.AUTHOR_ID, book.PRICE, book.CREATED_AT, MAX(book.CREATED_AT) 
FROM book 
GROUP BY book.AUTHOR_ID
```

Now if you want only the cheapest latest books with a single query, you need a subquery:
```sql
SELECT lastBook.ID, lastBook.TITLE, lastBook.AUTHOR_ID, lastBook.PRICE, lastBook.CREATED_AT 
FROM
(
  SELECT book.ID, book.TITLE, book.AUTHOR_ID, book.PRICE, book.CREATED_AT, MAX(book.CREATED_AT) 
  FROM book 
  GROUP BY book.AUTHOR_ID
) AS lastBook 
WHERE lastBook.PRICE < 20
```

To achieve this query using Propel, call the new `addSelectQuery()` method to use a first query as the source for the SELECT part of a second query:

```php
$latestBooks = BookQuery::create()
  ->withColumn('MAX(Book.CreatedAt)')
  ->groupBy('Book.AuthorId');
$latestCheapBooks = BookQuery::create()
  ->useSelectQuery($latestBooks, 'lastBook')
  ->where('lastBook.Price < ?', 20)
  ->find();
```

You could use two queries or a WHERE IN to achieve the same result, but it wouldn't be as effective.

Inline views are used a lot in Oracle, so this addition should make Propel even more Oracle-friendly.

## Better Pluralizer ##

Have you ever considered Propel as a lame English speaker? Due to its poor pluralizer, Propel used to be unable to create proper getter methods in one-to-many relationships when dealing with foreign objects named 'Child', 'Category', 'Wife', or 'Sheep'.

Starting with Propel 1.6, Propel adds a new pluralizer class named `StandardEnglishPluralizer`, which should take care of most of the irregular plural forms of your domain class names. This new pluralizer is disabled by default for backwards compatibility reasons, but it's very easy to turn it on in your `build.properties` file:

    propel.builder.pluralizer.class = builder.util.StandardEnglishPluralizer

Rebuild your model, and voila: ActiveRecord objects can now retrieve foreign objects using `getChildren()`, `getCategories()`, `getWives()`, and... `getSheep()`.

## New Reference Chapter in the Documentation: Active Record ##

There wasn't any one-stop place to read about the abilities of the generated Active Record objects in the Propel documentation. Since Propel 1.6, the new [Active Record reference](active-record.html) makes it easier to learn the  usage of Propel models using code examples.

## Miscellaneous ##

 * The runtime performance was tweaked on various places. Small boosts may be visible when logging is enabled, and on large batch processes.
 * A new `dbd2propel` task allows to convert [DBDesigner 4](http://www.fabforce.net/dbdesigner4/) models to a Propel schema in an easier way than the previous PHP script located under the `contrib/` folder. See the new [DBDesigner2Propel chapter]((../cookbook/dbdesigner.html)) for details.
 * By the way, the `contrib/` directory was removed from the core - it was not maintained anymore. Previous versions can be found in the 1.5 branch.
 * `ModelCriteria::keepQuery()` is now ON by default. This will remove bad surprises when doing a `count()` before a `find()`, and has a very limited impact on performance. You should not need to manually call `keepQuery()` anymore.
 * `ModelCriteria::with()` is now a little smarter, and therefore reduces even more the query count when hydrating one-to-many relationships.
 * Runtime Exceptions now look better in PHP 5.3, since Propel takes advantage of exception linking
 * Read queries (issued by Query termination methods `findPk()`, `find()`, `findOne()`, and `count()`) now don't create a transaction by default. Write queries still do.
 * The ActiveRecord and ActiveQuery APIs have been harmonized to provide similar parameter conversion for temporal and boolean columns in generated setters and filters. For instance, you can now set a boolean column to 'yes', or filter a temporal column on the 'now' string. The phpDoc blocks of generated setters and filters were rewritten to reflect these changes, so check the generated classes for details.
 * The generated `toArray()` method for ActiveRecord objects can now include collections, without risk of infinite recursion. Therefore `$author->toArray()` also outputs the collection of Books by the author. The dumping abilities introduced in 1.6 take advantage of this feature.
 * `PropelModelPager` now behaves more like a `PropelCollection`, thanks to the addition of `isFirst()`, `isLast()`, `isOdd()`, and `isEven()` methods. That means that it's easier to replace a `find()` by a `paginate()` as termination method for a query if there is a complex formatting.
 * Despite all that you may have read about PHP 5.3's garbage collector, memory leaks still exist on some particular circular references. Propel generates a `clearAllReferences()` method on all !ActiveRecord classes, and this method has been improved in Propel 1.6 to handle the existing leaks. Use it when you iterate on large collections of objects, or where the memory is limited.
 * For each database, Behaviors are now added to a priority queue. This allows to execute some behaviors before others, and to solve conflicts between behaviors. See [How to Write a Behavior](../cookbook/writing-behavior.html#specifying-a-priority-for-behavior-execution).
 * Behaviors can now modify existing methods even if no hook is called in the builders, thanks to a new service class called `PropelPHPParser`. This class can remove a method, replace a method by another one, or add a new method before or after an existing one. See the [Behavior Documentation](07-behaviors.html#replacing-or-removing-existing-methods) for details.
 * Propel now supports tablespace definition on Oracle tables using custom `<vendor>` tags. See the [Schema reference](../reference/schema.html#adding-vendor-info) for details.
 * The built-in [Runtime Introspection](../cookbook/runtime-introspection.html) classes are now a little smarter: `TableMap` objects have the knowledge of the primary string column.