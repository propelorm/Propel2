---
layout: documentation
title: Aggregate Column Behavior
---

# Aggregate Column Behavior #

The `aggregate_column` behavior keeps a column updated using an aggregate function executed on a related table.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `aggregate_column` behavior to a table. You must provide parameters for the aggregate column `name`, the foreign table name, and the aggregate `expression`. For instance, to add an aggregate column keeping the comment count in a `post` table:

```xml
<table name="post">
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="aggregate_column">
    <parameter name="name" value="nb_comments" />
    <parameter name="foreign_table" value="comment" />
    <parameter name="expression" value="COUNT(id)" />
  </behavior>
</table>
<table name="comment">
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="post_id" type="INTEGER" />
  <foreign-key foreignTable="post" onDelete="cascade">
    <reference local="post_id" foreign="id" />
  </foreign-key>
</table>
```

Rebuild your model, and insert the table creation sql again. The model now has an additional `nb_comments` column, of type `integer` by default. And each time an record from the foreign table is added, modified, or removed, the aggregate column is updated:

```php
<?php
$post = new Post();
$post->setTitle('How Is Life On Earth?');
$post->save();
echo $post->getNbComments(); // 0
$comment1 = new Comment();
$comment1->setPost($post);
$comment1->save();
echo $post->getNbComments(); // 1
$comment2 = new Comment();
$comment2->setPost($post);
$comment2->save();
echo $post->getNbComments(); // 2
$comment2->delete();
echo $post->getNbComments(); // 1
```

The aggregate column is also kept up to date when related records get modified through a Query object:

```php
<?php
CommentQuery::create()
  ->filterByPost($post)
  ->delete():
echo $post->getNbComments(); // 0
```

## Customizing The Aggregate Calculation ##

Any aggregate function can be used on any of the foreign columns. For instance, you can use the `aggregate_column` behavior to keep the latest update date of the related comments, or the total votes on the comments. You can even keep several aggregate columns in a single table:

```xml
<table name="post">
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="aggregate_column">
    <parameter name="name" value="nb_comments" />
    <parameter name="foreign_table" value="comment" />
    <parameter name="expression" value="COUNT(id)" />
  </behavior>
  <behavior name="aggregate_column">
    <parameter name="name" value="last_comment" />
    <parameter name="foreign_table" value="comment" />
    <parameter name="expression" value="MAX(created_at)" />
  </behavior>
  <behavior name="aggregate_column">
    <parameter name="name" value="total_votes" />
    <parameter name="foreign_table" value="comment" />
    <parameter name="expression" value="SUM(vote)" />
  </behavior>
</table>
<table name="comment">
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="post_id" type="INTEGER" />
  <foreign-key foreignTable="post" onDelete="cascade">
    <reference local="post_id" foreign="id" />
  </foreign-key>
  <column name="created_at" type="TIMESTAMP" />
  <column name="vote" type="INTEGER" />
</table>
```

The behavior adds a `computeXXX()` method to the `Post` class to compute the value of the aggregate function. This method, called each time records are modified in the related `comment` table, is the translation of the behavior settings into a SQL query:

```php
<?php
// in om/BasePost.php
public function computeNbComments(PropelPDO $con)
{
  $stmt = $con->prepare('SELECT COUNT(id) FROM `comment` WHERE comment.POST_ID = :p1');
  $stmt->bindValue(':p1', $this->getId());
  $stmt->execute();
  return $stmt->fetchColumn();
}
```

You can override this method in the model class to customize the aggregate column calculation.

## Customizing The Aggregate Column ##

By default, the behavior adds one columns to the model. If this column is already described in the schema, the behavior detects it and doesn't add it a second time. This can be useful if you need to use a custom `type` or `phpName` for the aggregate column:

```xml
<table name="post">
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <column name="nb_comments" phpName="CommentCount" type="INTEGER" />
  <behavior name="aggregate_column">
    <parameter name="name" value="nb_comments" />
    <parameter name="foreign_table" value="comment" />
    <parameter name="expression" value="COUNT(id)" />
  </behavior>
</table>
```
