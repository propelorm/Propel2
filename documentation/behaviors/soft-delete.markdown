---
layout: documentation
title: SoftDelete Behavior
---

# SoftDelete Behavior #

The `soft_delete` behavior overrides the deletion methods of a model object to make them 'hide' the deleted rows but keep them in the database. Deleted objects still don't show up on select queries, but they can be retrieved or undeleted when necessary.

**Warning**: This behavior is deprecated due to limitations that can't be fixed. Use the [`archivable`](archivable.html) behavior instead.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `soft_delete` behavior to a table:
{% highlight xml %}
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="soft_delete" />
</table>
{% endhighlight %}

Rebuild your model, insert the table creation sql again, and you're ready to go. The model now has one new column, `deleted_at`, that stores the deletion date. Select queries don't return the deleted objects:

{% highlight php %}
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
$b->delete();
echo $b->isDeleted(); // false
echo $b->getDeletedAt(); // 2009-10-02 18:14:23
$books = BookQuery::create()->find(); // empty collection
{% endhighlight %}

Behind the curtain, the behavior adds a condition to every SELECT query to return only records where the `deleted_at` column is null. That's why the deleted objects don't appear anymore upon selection.

_Warning_gg Deleted results may show up in related results (i.e. when you use `joinWith()` on a query and point to a `soft_delete` model). This is something that can't be fixed, and a good reason to use the `archivable` behavior instead.

You can include deleted results in a query by calling the `includeDeleted()` filter:

{% highlight php %}
<?php
$book = BookQuery::create()
  ->includeDeleted()
  ->findOne();
echo $book->getTitle(); // 'War And Peace'
{% endhighlight %}

You can also turn off the query alteration for the next query by calling the static method `disableSoftDelete()` on the related Query object:

{% highlight php %}
<?php
BookQuery::disableSoftDelete();
$book = BookQuery::create()->findOne();
echo $book->getTitle(); // 'War And Peace'
{% endhighlight %}

Note that `find()` and other selection methods automatically re-enable the `soft_delete` filter, so `disableSoftDelete()` is really a single shot method. You can also enable the query alteration manually by calling the `enableSoftDelete()` method on Query objects.

>**Tip**<br />`ModelCriteria::paginate()` executes two queries, so `disableSoftDelete()` doesn't work in this case. Prefer `includeDeleted()` in queries using `paginate()`.

If you want to recover a deleted object, use the `unDelete()` method:

{% highlight php %}
<?php
$book->unDelete();
$books = BookQuery::create()->find();
$book = $books[0];
echo $book->getTitle(); // 'War And Peace'
{% endhighlight %}

If you want to force the real deletion of an object, call the `forceDelete()` method:

{% highlight php %}
<?php
$book->forceDelete();
echo $book->isDeleted(); // true
$books = BookQuery::create()->find(); // empty collection
{% endhighlight %}

The query methods `delete()` and `deleteAll()` also perform a soft deletion, unless you disable the behavior on the peer class:

{% highlight php %}
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();

BookQuery::create()->delete($b);
$books = BookQuery::create()->find(); // empty collection
// the rows look deleted, but they are still there
BookQuery::disableSoftDelete();
$books = BookQuery::create()->find();
$book = $books[0];
echo $book->getTitle(); // 'War And Peace'

// To perform a true deletion, disable the softDelete feature
BookQuery::disableSoftDelete();
BookQuery::create()->delete();
// Alternatively, use forceDelete()
BookQuery::create()->forceDelete();
{% endhighlight %}

## Parameters ##

You can change the name of the column added by the behavior by setting the `deleted_column` parameter:

{% highlight xml %}
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <column name="my_deletion_date" type="TIMESTAMP" />
  <behavior name="soft_delete">
    <parameter name="deleted_column" value="my_deletion_date" />
  </behavior>
</table>
{% endhighlight %}

{% highlight php %}
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
$b->delete();
echo $b->getMyDeletionDate(); // 2009-10-02 18:14:23
$books = BookQuery::create()->find(); // empty collection
{% endhighlight %}
