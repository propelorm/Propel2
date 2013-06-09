---
layout: documentation
title: Model Introspection At Runtime
---

# Model Introspection At Runtime #

In addition to the object classes used to do C.R.U.D. operations, Propel generates an object mapping for your tables to allow runtime introspection.

The introspection objects are instances of the map classes. Propel maps databases, tables, columns and relations into objects that you can easily use.

## Retrieving a TableMap ##

The starting point for runtime introspection is usually a table map. This objects stores every possible property of a table, as defined in the `schema.xml`, but accessible at runtime.

To retrieve a table map for a table, use the `getTableMap()` static method of the related TableMap class. For instance, to retrieve the table map for the `book` table, just call:

```php
<?php
$bookTable = BookTableMap::getTableMap();
```

## TableMap properties ##

A `TableMap` object carries the same information as the schema. Check the following example to see how you can read the general properties of a table from its map:

```php
<?php
echo $bookTable->getName();          // 'table'
echo $bookTable->getPhpName();       // 'Table'
echo $bookTable->getPackage();       // 'bookstore'
echo $bookTable->isUseIdGenerator(); // true
```

>**Tip**<br />A TableMap object also references the `DatabaseMap` that contains it. From the database map, you can also retrieve other table maps using the table name or the table phpName:

```php
<?php
$dbMap = $bookTable->getDatabaseMap();
$authorTable = $dbMap->getTable('author');
$authorTable = $dbMap->getTablebyPhpName('Author');
```

To introspect the columns of a table, use any of the `getColumns()`, `getPrimaryKeys()`, and `getForeignKeys()` `TableMap` methods. They all return an array of `ColumnMap` objects.

```php
<?php
$bookColumns = $bookTable->getColumns();
foreach ($bookColumns as $column) {
  echo $column->getName();
}
```

Alternatively, if you know a column name, you can retrieve the corresponding ColumnMap directly using the of `getColumn($name)` method.

```php
<?php
$bookTitleColumn = $bookTable->getColumn('title');
```

The `DatabaseMap` object offers a shortcut to every `ColumnMap` object if you know the fully qualified column name:
```php
<?php
$bookTitleColumn = $dbMap->getColumn('book.TITLE');
```

## ColumnMaps ##

A `ColumnMap` instance offers a lot of information about a table column. Check the following examples:

```php
<?php
$bookTitleColumn->getTableName();    // 'book'
$bookTitleColumn->getTablePhpName(); // 'Book'
$bookTitleColumn->getType();         // 'VARCHAR'
$bookTitleColumn->getSize();         // 255
$bookTitleColumn->getDefaultValue(); // null
$bookTitleColumn->isLob();           // false
$bookTitleColumn->isTemporal();      // false
$bookTitleColumn->isEpochTemporal(); // false
$bookTitleColumn->isNumeric();       // false
$bookTitleColumn->isText();          // true
$bookTitleColumn->isPrimaryKey();    // false
$bookTitleColumn->isForeignKey();    // false
$bookTitleColumn->isPrimaryString(); // true
```

`ColumnMap` objects also keep a reference to their parent `TableMap` object:

```php
<?php
$bookTable = $bookTitleColumn->getTable();
```

Foreign key columns give access to more information, including the related table and column:

```php
<?php
$bookPublisherIdColumn = $bookTable->getColumn('publisher_id');
echo $bookPublisherIdColumn->isForeignKey();         // true
echo $bookPublisherIdColumn->getRelatedName();       // 'publisher.ID'
echo $bookPublisherIdColumn->getRelatedTableName();  // 'publisher'
echo $bookPublisherIdColumn->getRelatedColumnName(); // 'ID'
$publisherTable = $bookPublisherIdColumn->getRelatedTable();
$publisherRelation = $bookPublisherIdColumn->getRelation();
```

## RelationMaps ##

To get an insight on all the relationships of a table, including the ones relying on a foreign key located in another table, you must use the `RelationMap` objects related to a table.

If you know its name, you can retrieve a `RelationMap` object using `TableMap::getRelation($relationName)`. Note that the relation name is the phpName of the related table, unless the foreign key defines a phpName in the schema. For instance, the name of the `RelationMap` object related to the `book.PUBLISHER_ID` column is 'Publisher'.

```php
<?php
$publisherRelation = $bookTable->getRelation('Publisher');
```

alternatively, you can access a `RelationMap` from a foreign key column using `ColumnMap::getRelation()`, as follows:

```php
<?php
$publisherRelation = $bookTable->getColumn('publisher_id')->getRelation();
```

Once you have a `RelationMap` instance, inspect its properties using any of the following methods:

```php
<?php
echo $publisherRelation->getType();     // RelationMap::MANY_TO_ONE
echo $publisherRelation->getOnDelete(); // 'SET NULL'
$bookTable      = $publisherRelation->getLocalTable();
$publisherTable = $publisherRelation->getForeignTable();
print_r($publisherRelation->getColumnMappings());
  // array('book.PUBLISHER_ID' => 'publisher.ID')
print_r(publisherRelation->getLocalColumns());
  // array($bookPublisherIdColumn)
print_r(publisherRelation->getForeignColumns());
  // array($publisherBookIdColumn)
```

This also works for relationships referencing the current table:

```php
<?php
$reviewRelation = $bookTable->getRelation('Review');
echo $reviewRelation->getType();     // RelationMap::ONE_TO_MANY
echo $reviewRelation->getOnDelete(); // 'CASCADE'
$reviewTable = $reviewRelation->getLocalTable();
$bookTable   = $reviewRelation->getForeignTable();
print_r($reviewRelation->getColumnMappings());
  // array('review.BOOK_ID' => 'book.ID')
```

To retrieve all the relations of a table, call `TableMap::getRelations()`. You can then iterate over an array of `RelationMap` objects.

>**Tip**<br />RelationMap objects are lazy-loaded, which means that the `TableMap` will not instantiate any relation object until you call `getRelations()`. This allows the `TableMap` to remain lightweight for when you don't use relationship introspection.
