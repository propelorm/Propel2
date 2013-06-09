---
layout: documentation
title: Using SQL Schemas
---

# Using SQL Schemas #

Some database vendors support "schemas", a.k.a. namespaces of collections of database objects (tables, views, etc.). MSSQL, PostgreSQL, and to a lesser extent MySQL, all provide the ability to group and organize tables into schemas. Propel supports tables organized into schemas, and works seamlessly in this context.

## Schema Definition ##

### Assigning a Table to a Schema ###

In a XML schema, you can assign all the tables included into a `<database>` tag to a given schema by setting the `schema` attribute on the `<database>` tag:

```xml
<database name="bookstore" schema="bookstore">
  <table name="book">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="title" type="VARCHAR" required="true" />
  </table>
</database>
```

>**Tip**<br />On RDBMS that do not support SQL schemas (Oracle, SQLite), the `schema` attribute is ignored.

You can also assign a table to a given schema individually ; this overrides the `schema` of the parent `<database>`:

```xml
<table name="book" schema="bookstore1">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
</table>
```

### Foreign Keys Between Schemas ###

You can create foreign keys between tables assigned to different schemas, provided you set the `foreignSchema` attribute in the `<foreign-key>` tag.

```xml
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
```

## Schemas in Generated SQL ##

When generating the SQL for table creation, Propel correctly adds the schema prefix (example for MySQL):

```sql
CREATE TABLE `bookstore`.`book`
(
  `id` INTEGER NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255),
  PRIMARY KEY (`id`)
)
```

>**Tip**<br />Propel does not take care of creating the schema. The target database must already contain the required schemas, and the user credentials must allow Propel to access this schema.

## Schemas in PHP Code ##

Just like actual table names, SQL schemas don't appear in the PHP code. For the PHP developer, who manipulates phpNames, it's as if schemas didn't existed.

Of course, you can make queries spanning across several schemas.

>**Tip**<br />in Mysql, "SCHEMA" and "DATABASE" are synonyms. Therefore, the ability to define another schema for a given table actually allows cross-database queries.

## Using the Schema As Base for PHP code Organization ##

Propel provides other features to organize your model:

* _Packages_ are subdirectories in which Model classes get generated (see [Multi-Component Data Model](./multi-component-data-model.html))
* _Namespaces_ are actual PHP5.3 namespaces for generated Model classes (see [PHP 5.3 Namespaces](./namespaces.html))

You can easily tell Propel to copy the `schema` attribute to both the `package` and the `namespace` attributes, in order to reproduce the SQL organization at the PHP level. To that extent, modify the following settings in `build.properties`:

```ini
propel.schema.autoPackage = true
propel.schema.autoNamespace = true
```

With such a configuration, a `book` table assigned to the `bookstore` schema will generate a `Bookstore\Book` ActiveRecord class under the `bookstore/` subdirectory.

If you're stuck with PHP 5.2, you probably need to use the schema name as a class prefix rather than a namespace. That's what the `autoPrefix` setting is for:

```ini
propel.schema.autoPrefix = true
```

With such a configuration, a `book` table assigned to the `bookstore` schema will generate a `BookstoreBook` ActiveRecord class.
