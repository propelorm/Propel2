---
layout: documentation
title: AutoAddPk Behavior
---

# AutoAddPk Behavior #

The `auto_add_pk` behavior adds a primary key columns to the tables that don't have one. Using this behavior allows you to omit the declaration of primary keys in your tables.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `auto_add_pk` behavior to a table:

```xml
<table name="book">
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="auto_add_pk" />
</table>
```

Rebuild your model, and insert the table creation sql. You will notice that the `book` table has two columns and not just one. The behavior added an `id` column, of type integer and autoincremented. This column can be used as any other column:

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
echo $b->getId(); // 1
```

This behavior is more powerful if you add it to the database instead of a table. That way, it will alter all tables not defining a primary key column - and leave the others unchanged.

```xml
<database name="bookstore" defaultIdMethod="native">
  <behavior name="auto_add_pk" />
  <table name="book">
    <column name="title" type="VARCHAR" required="true" primaryString="true" />
  </table>
</database>
```

You can even enable it for all your databases by adding it to the default behaviors in your `build.properties` file:

```ini
propel.behavior.default = auto_add_pk
```

## Parameters ##

By default, the behavior adds a column named `id` to the table if the table has no primary key. You can customize all the attributes of the added column by setting corresponding parameters in the behavior definition:

```xml
<database name="bookstore" defaultIdMethod="native">
  <behavior name="auto_add_pk">
    <parameter name="name" value="identifier" />
    <parameter name="autoIncrement" value="false" />
    <parameter name="type" value="BIGINT" />
  </behavior>
  <table name="book">
    <column name="title" type="VARCHAR" required="true" primaryString="true" />
  </table>
</database>
```

Once you regenerate your model, the column is now named differently:

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->setIdentifier(1);
$b->save();
echo $b->getIdentifier(); // 1
```
