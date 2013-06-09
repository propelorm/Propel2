---
layout: documentation
title: Versionable Behavior
---

# Versionable Behavior #

The `versionable` behavior provides versioning capabilities to any ActiveRecord object. Using this behavior, you can:

* Revert an object to previous versions easily
* Track and browse history of the modifications of an object
* Keep track of the modifications in related objects

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `versionable` behavior to a table:
```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <behavior name="versionable" />
</table>
```

Rebuild your model, insert the table creation sql again, and you're ready to go. The model now has one new column, `version`, which stores the version number. It also has a new table, `book_version`, which stores all the versions of all `Book` objects, past and present. You won't need to interact with this second table, since the behavior offers an easy-to-use API that takes care of all versioning actions from the main ActiveRecord object.

```php
<?php
$book = new Book();

// automatic version increment
$book->setTitle('War and Peas');
$book->save();
echo $book->getVersion(); // 1
$book->setTitle('War and Peace');
$book->save();
echo $book->getVersion(); // 2

// reverting to a previous version
$book->toVersion(1);
echo $book->getTitle(); // 'War and Peas'
// saving a previous version creates a new one
$book->save();
echo $book->getVersion(); // 3

// checking differences between versions
print_r($book->compareVersions(1, 2));
// array(
//   'Title' => array(1 => 'War and Peas', 2 => 'War and Pace'),
// );

// deleting an object also deletes all its versions
$book->delete();
```

## Adding details about each revision ##

For future reference, you probably need to record who edited an object, as well as when and why. To enable audit log capabilities, add the three following parameters to the `<behavior>` tag:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <behavior name="versionable">
    <parameter name="log_created_at" value="true" />
    <parameter name="log_created_by" value="true" />
    <parameter name="log_comment" value="true" />
  </behavior>
</table>
```

Rebuild your model, and you can now define an author name and a comment for each revision using the `setVersionCreatedBy()` and `setVersionComment()` methods, as follows:

```php
<?php
$book = new Book();
$book->setTitle('War and Peas');
$book->setVersionCreatedBy('John Doe');
$book->setVersionComment('Book creation');
$book->save();

$book->setTitle('War and Peace');
$book->setVersionCreatedBy('John Doe');
$book->setVersionComment('Corrected typo on book title');
$book->save();
```

## Retrieving revision history ##

```php
<?php
// details about each revision are available for all versions of an object
$book->toVersion(1);
echo $book->getVersionCreatedBy(); // 'John Doe'
echo $book->getVersionComment(); // 'Book creation'
// besides, the behavior also logs the creation date for all versions
echo $book->getVersionCreatedAt(); // '2010-12-21 22:57:19'

// if you need to list the revision details, it is better to use the version object
// than the main object. The following requires only one database query:
foreach ($book->getAllVersions() as $bookVersion) {
  echo sprintf("'%s', Version %d, updated by %s on %s (%s)\n",
    $bookVersion->getTitle(),
    $bookVersion->getVersion(),
    $bookVersion->getVersionCreatedBy(),
    $bookVersion->getVersionCreatedAt(),
    $bookVersion->getVersionComment(),
  );
}
// 'War and Peas', Version 1, updated by John Doe on 2010-12-21 22:53:02 (Book Creation)
// 'War and Peace', Version 2, updated by John Doe on 2010-12-21 22:57:19 (Corrected typo on book title)
```

## Conditional versioning ##

You may not need a new version each time an object is created or modified. If you want to specify your own condition, just override the `isVersioningNecessary()` method in your stub class. The behavior calls it behind the curtain each time you `save()` the main object. No version is created if it returns false.

```php
<?php
class Book extends BaseBook
{
  public function isVersioningNecessary()
  {
    return $this->getISBN() !== null && parent::isVersioningNecessary();
  }
}

$book = new Book();
$book->setTitle('Pride and Prejudice');
$book->save(); // book is saved, no new version is created
$book->setISBN('0553213105');
$book->save(): // book is saved, and a new version is created
```

Alternatively, you can choose to disable the automated creation of a new version at each save for all objects of a given model by calling the `disableVersioning()` method on the Query class. In this case, you still have the ability to manually create a new version of an object, using the `addVersion()` method on a saved object:

```php
<?php
BookQuery::disableVersioning();
$book = new Book();
$book->setTitle('Pride and Prejudice');
$book->setVersion(1);
$book->save(); // book is saved, no new version is created
$book->addVersion(); // a new version is created

// you can reenable versioning using the Query static method enableVersioning()
BookQuery::enableVersioning();
```

## Versioning Related objects ##

If a model uses the versionable behavior, and is related to another model also using the versionable behavior, then each object automatically keeps track of the modifications of related objects. This means that calling `toVersion()` restores the state of the main object _and of the related objects as well_.

The following example assumes that both the `Book` model and the `Author` model are versionable - one `Author` has many `Books`:

```php
<?php
$author = new Author();
$author->setFirstName('Leo');
$author->setLastName('Totoi');
$book = new Book();
$book->setTitle('War and Peas');
$book->setAuthor($author);
$book->save(); // version 1

$book->setTitle('War and Peace');
$book->save(); // version 2

$author->setLastName('Tolstoi');
$book->save(); // version 3

$book->toVersion(1);
echo $book->getTitle(); // 'War and Peas'
echo $book->getAuthor()->getLastName(); // 'Totoi'
$book->toVersion(3);
echo $book->getTitle(); // 'War and Peace'
echo $book->getAuthor()->getLastName(); // 'Tolstoi'
```

>**Tip**<br />Versioning of related objects is only possible for simple foreign keys. Relationships based on composite foreign keys cannot preserve relation versionning for now.

## Parameters ##

You can change the name of the column added by the behavior by setting the `version_column` parameter. Propel only adds the column if it's not already present, so you can easily customize this column by adding it manually to your schema:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <column name="my_version_column" type="BIGINT" description="Version column" />
  <behavior name="versionable">
    <parameter name="version_column" value="my_version_column" />
  </behavior>
</table>
```

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
echo $b->getMyVersionColumn(); // 1
// For convenience and ease of use, Propel creates a getVersion() anyway
echo $b->getVersion(); // 1
```

You can also change the name of the version table by setting the `version_table` parameter. Again, Propel automatically creates the table, unless it's already present in the schema:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <behavior name="versionable">
    <parameter name="version_table" value="my_book_version" />
  </behavior>
</table>
```

The audit log abilities need to be enabled in the schema as well:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <behavior name="versionable">
    <!-- Log the version creation date -->
    <parameter name="log_created_at" value="true" />
    <!-- Log the version creator name, using setVersionCreatedBy() -->
    <parameter name="log_created_by" value="true" />
    <!-- Log the version comment, using setVersionComment() -->
    <parameter name="log_comment" value="true" />
  </behavior>
</table>
```

## Public API ##

### ActiveRecord class ###

* `void save()`: Adds a new version to the object version history and increments the `version` property
* `void delete()`: Deletes the object version history
* `boolean isVersioningNecessary(PropelPDO $con = null)`: Checks whether a new version needs to be saved
* `BaseObject toVersion(integer $version_number)`: Populates the properties of the current object with values from the requested version. Beware that saving the object afterwards will create a new version (and not update the previous version).
* `integer getLastVersionNumber(PropelPDO $con)`: Queries the database for the highest version number recorded for this object
* `boolean isLastVersion()`: Returns true if the current object is the last available version
* `Version addVersion(PropelPDO $con)`: Creates a new Version record and saves it. To be used when isVersioningNecessary() is false. Beware that it doesn't take care of incrementing the version number of the main object, and that the main object must be saved prior to calling this method.
* `array getAllVersions(PropelPDO $con)`: Returns all Version objects related to the main object in a collection
* `Version getOneVersion(integer $versionNumber PropelPDO $con)`: Returns a given version object
* `array compareVersions(integer $version1, integer $version2)`: Returns an array of differences showing which parts of a resource changed between two versions
* `BaseObject populateFromVersion(Version $version, PropelPDO $con)`: Populates an ActiveRecord object based on a Version object
* `array compareVersions(integer $version1, integer $version2)`: Returns an array of differences showing which parts of a resource changed between two versions

* `BaseObject setVersionCreatedBy(string $createdBy)`: Defines the author name for the revision
* `string getVersionCreatedBy()`: Gets the author name for the revision
* `mixed getVersionCreatedAt()`: Gets the creation date for the revision (the behavior takes care of setting it)
* `BaseObject setVersionComment(string $comment)`: Defines the comment for the revision
* `string getVersionComment()`: Gets the comment for the revision

### Query static methods ###

* `void enableVersioning()`: Enables versionning for all instances of the related ActiveRecord class
* `void disableVersioning()`: Disables versionning for all instances of the related ActiveRecord class
* `boolean isVersioningEnabled()`: Checks whether the versionnig is enabled
