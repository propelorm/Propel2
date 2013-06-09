---
layout: documentation
title: Timestampable Behavior
---

# Timestampable Behavior #

The `timestampable` behavior allows you to keep track of the date of creation and last update of your model objects.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `timestampable` behavior to a table:
```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="timestampable" />
</table>
```

Rebuild your model, insert the table creation sql again, and you're ready to go. The model now has two new columns, `created_at` and `updated_at`, that store a timestamp automatically updated on save:

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
echo $b->getCreatedAt(); // 2009-10-02 18:14:23
echo $b->getUpdatedAt(); // 2009-10-02 18:14:23
$b->setTitle('Anna Karenina');
$b->save();
echo $b->getCreatedAt(); // 2009-10-02 18:14:23
echo $b->getUpdatedAt(); // 2009-10-02 18:14:25
```

The object query also has specific methods to retrieve recent objects and order them according to their update date:

```php
<?php
$books = BookQuery::create()
  ->recentlyUpdated()  // adds a minimum value for the update date
  ->lastUpdatedFirst() // orders the results by descending update date
  ->find();
```

You can use any of the following methods in the object query:

```php
<?php
// limits the query to recent objects
ModelCriteria   recentlyCreated($nbDays = 7)
ModelCriteria   recentlyUpdated($nbDays = 7)
// orders the results
ModelCriteria   lastCreatedFirst()  // order by creation date desc
ModelCriteria   firstCreatedFirst() // order by creation date asc
ModelCriteria   lastUpdatedFirst()  // order by update date desc
ModelCriteria   firstUpdatedFirst() // order by update date asc
```

>**Tip**<br />You may need to keep the update date unchanged after an update on the object, for instance when you only update a calculated row. In this case, call the `keepUpdateDateUnchanged()` method on the object before saving it.


## Parameters ##

You can change the name of the columns added by the behavior by setting the `create_column` and `update_column` parameters:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <column name="my_create_date" type="TIMESTAMP" />
  <column name="my_update_date" type="TIMESTAMP" />
  <behavior name="timestampable">
    <parameter name="create_column" value="my_create_date" />
    <parameter name="update_column" value="my_update_date" />
  </behavior>
</table>
```

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
echo $b->getMyCreateDate(); // 2009-10-02 18:14:23
echo $b->getMyUpdateDate(); // 2009-10-02 18:14:23
$b->setTitle('Anna Karenina');
$b->save();
echo $b->getMyCreateDate(); // 2009-10-02 18:14:23
echo $b->getMyUpdateDate(); // 2009-10-02 18:14:25
```
