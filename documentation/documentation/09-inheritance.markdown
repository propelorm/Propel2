---
layout: documentation
title: Inheritance
---

# Inheritance #

Developers often need one model table to extend another model table. Inheritance being an object-oriented notion, it doesn't have a true equivalent in the database world, so this is something an ORM must emulate. Propel offers three types of table inheritance:

* [Single Table Inheritance](http://www.martinfowler.com/eaaCatalog/singleTableInheritance.html), which is the most efficient implementations from a SQL and query performance perspective, but is limited to a small number of inherited fields
* [Class Table Inheritance](http://martinfowler.com/eaaCatalog/classTableInheritance.html), which separates data into several tables and uses joins to fetch complete children entities
* [Concrete Table Inheritance](http://www.martinfowler.com/eaaCatalog/concreteTableInheritance.html), which provides the most features but adds a small overhead on write queries.


## Single Table Inheritance ##

In this implementation, one table is used for all subclasses. This has the implication that your table must have all columns needed by the main class and subclasses. Propel will create stub subclasses.

Let's illustrate this idea with an example. Consider an object model with three classes, `Book`, `Essay`, and `Comic` - the first class being parent of the other two. With single table inheritance, the data of all three classes is stored in one table, named `book`.

### Schema Definition ###

A table using Single Table Inheritance requires a column to identify which class should be used to represent the _table_ row. Classically, this column is named `class_key` - but you can choose whatever name fits your taste. The column needs the `inheritance="single"` attribute to make Propel understand that it's the class key column. Note that this 'key' column must be a real column in the table.

```xml
<table name="book">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="class_key" type="INTEGER" inheritance="single">
    <inheritance key="1" class="Book"/>
    <inheritance key="2" class="Essay" extends="Book"/>
    <inheritance key="3" class="Comic" extends="Book"/>
  </column>
</table>
```

Once you rebuild your model, Propel generated all three model classes (`Book`, `Essay`, and `Comic`) and three query classes (`BookQuery`, `EssayQuery`, and `ComicQuery`). The `Essay` and `Comic` classes extend the `Book` class, the `EssayQuery` and `ComicQuery` classes extend `BookQuery`.

>**Tip**<br />An inherited class can extend another inherited class. That mean that you can add a `Manga` kind of book that extends `Comic` instead of `Book`.

<br />

>**Tip**<br />If you define an `<inheritance>` tag with no `extends` attribute, as in the example above, it must use the table `phpName` as its `class` attribute.

### Using Inherited Objects ###

Use inherited objects just like you use regular Propel model objects:

```php
<?php
$book = new Book();
$book->setTitle('War And Peace');
$book->save();
$essay = new Essay();
$essay->setTitle('On the Duty of Civil Disobedience');
$essay->save();
$comic = new Comic();
$comic->setTitle('Little Nemo In Slumberland');
$comic->save();
```

Inherited objects share the same properties and methods by default, but you can add your own logic to each of the generated classes.

Behind the curtain, Propel sets the `class_key` column based on the model class. So the previous code stores the following rows in the database:

```
id | title                             | class_key
---|-----------------------------------|----------
1  | War And Peace                     | Book
2  | On the Duty of Civil Disobedience | Essay
3  | Little Nemo In Slumberland        | Comic
```

Incidentally, that means that you can add new classes manually, even if they are not defined as `<inheritance>` tags in the `schema.xml`:

```php
<?php
class Novel extends Book
{
  public function __construct()
  {
    parent::__construct();
    $this->setClassKey('Novel');
  }
}
$novel = new Novel();
$novel->setTitle('Harry Potter');
$novel->save();
```

### Retrieving Inherited objects ###

In order to retrieve books, use the Query object of the main class, as you would usually do. Propel will hydrate children objects instead of the parent object when necessary:

```php
<?php
$books = BookQuery::create()->find();
foreach ($books as $book) {
  echo get_class($book) . ': ' . $book->getTitle() . "\n";
}
// Book: War And Peace
// Essay: On the Duty of Civil Disobedience
// Comic: Little Nemo In Slumberland
// Novel: Harry Potter
```

If you want to retrieve only objects of a certain class, use the inherited query classes:

```php
<?php
$comic = ComicQuery::create()
  ->findOne();
echo get_class($comic) . ': ' . $comic->getTitle() . "\n";
// Comic: Little Nemo In Slumberland
```

>**Tip**<br />You can override the base TableMap's `getOMClass()` to return the classname to use based on more complex logic (or query).

### Abstract Entities ###

If you wish to enforce using subclasses of an entity, you may declare a table "abstract" in your XML data model:

```xml
<table name="book" abstract="true">
  ...
```

That way users will only be able to instantiate `Essay` or `Comic` books, but not `Book`.

## Class Table Inheritance ##

Class Table Inheritance uses one table per class in the inheritance structure ; each table stores only the columns it doesn't inherits from its parent.
Propel doesn't offer class table inheritance per se, however it provides a behavior called `delegate`, which offers the same functionality.

### Schema Definition ###

A sports news website displays stats about various sports player. The object oriented design therefore produces a `Player` model with an identity, and two children classes, `Footballer` and `Basketballer`, with distinct stats columns.

Instead of having the children table inherit from the parent table, Propel lets you _delegate_ column handling from the child to the parent table. Here is how it looks like:

```xml
<table name="player">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="first_name" type="VARCHAR" size="100"/>
  <column name="last_name" type="VARCHAR" size="100"/>
</table>
<table name="footballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="goals_scored" type="INTEGER" />
  <column name="fouls_committed" type="INTEGER" />
  <column name="player_id" type="INTEGER" />
  <foreign-key foreignTable="player">
    <reference local="player_id" foreign="id" />
  </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="player" />
  </behavior>
</table>
<table name="basketballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="points" type="INTEGER" />
  <column name="field_goals" type="INTEGER" />
  <column name="three_points_field_goals" type="INTEGER" />
  <column name="player_id" type="INTEGER" />
  <foreign-key foreignTable="player">
    <reference local="player_id" foreign="id" />
  </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="player" />
  </behavior>
</table>
```

The `footballer` table doesn't own the identity columns, which are in the `player` table. However, the `Footballer` class can _delegate_ these columns to the `Player` class. In this case, the `delegate` behavior doesn't alter the schema, it just allows the `Footballer` and `Basketballer` classes to proxy method calls to their "parent" `Player` class.

>**Tip**<br />Delegation only works for a single level. If you have a deep inheritance hierarchy, you will need to delegate to each ancestors in the hierarchy by simulating multiple inheritance (see below).

### Delegation in Practice ###

Using the previous schema, here is how you create a `Basketballer` and set his stats and identity:

```php
<?php
basketballer = new Basketballer();
$basketballer->setPoints(101);
$basketballer->setFieldGoals(47);
$basketballer->setThreePointsFieldGoals(7);
// set player identity via delegation
$basketballer->setFirstName('Michael');
$basketballer->setLastName('Giordano');
// same as
$player = new Player();
$player->setFirstName('Michael');
$player->setLastName('Giordano');
$basketballer->setPlayer($player);

// save basketballer and player
$basketballer->save();

// retrieve delegated data directly from the main object
echo $basketballer->getFirstName(); // Michael
```

The `Basketballer` object delegates `Player` methods to its related `Player` object. If no `Player` object exists, the `delegate` behavior creates one and relates it to the current `basketballer`, so that both objects can be persisted together.

The same happens for `Footballer` class, which also delegates to `Player`. And since a `Basketballer` and a `Footballer` instance can delegate to the same `Player` instance, a player can play both soccer and basketball.

>**Tip**<br />Again, delegation isn't really inheritance. The `Basketballer` class doesn't inherit from `Player`, but _proxies_ method calls to `Player`. And since Propel uses `__call()` magic to achieve this proxying, an IDE won't see the `Player` methods accessible to a `Basketballer` instance.

### Multiple Inheritance ###

The `delegate` behavior allows to delegate to more than one class, effectively simulating multiple inheritance. Here is an example:

```xml
<table name="person">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="first_name" type="VARCHAR" size="100"/>
  <column name="last_name" type="VARCHAR" size="100"/>
</table>
<table name="team">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="name" type="VARCHAR" size="100"/>
</table>
<table name="footballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="goals_scored" type="INTEGER" />
  <column name="fouls_committed" type="INTEGER" />
  <column name="person_id" type="INTEGER" />
  <foreign-key foreignTable="person">
    <reference local="person_id" foreign="id" />
  </foreign-key>
  <column name="team_id" type="INTEGER" />
  <foreign-key foreignTable="team">
    <reference local="team_id" foreign="id" />
  </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="person, team" />
  </behavior>
</table>
```

Now you can access the `Person` and `Team` properties directly from the `Footballer` class:

```php
<?php
$footballer = new Footballer();
$footballer->setGoalsScored(43);
$footballer->setFoulsCommitted(4);
$footballer->setThreePointsFieldGoals(7);
// set player identity via delegation
$footballer->setFirstName('Michael');
$footballer->setLastName('Platino');
// set team name via delegation
$footballer->setName('Saint Etienne');

// save footballer and player and team
$footballer->save();

// retrieve delegated data directly from the main object
echo $footballer->getFirstName(); // Michael
```

Multiple delegation also allows to implement a deep inheritance hierarchy. For instance, if your object model contains a `ProBasketballer` inheriting from `Basketballer` inheriting from `Player`, the  `ProBasketballer` should delegate to both `Basketballer` and `Player`; delegating to `Basketballer` only isn't enough.

```xml
<table name="player">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="first_name" type="VARCHAR" size="100"/>
  <column name="last_name" type="VARCHAR" size="100"/>
</table>
<table name="basketballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="points" type="INTEGER" />
  <column name="field_goals" type="INTEGER" />
  <column name="three_points_field_goals" type="INTEGER" />
  <column name="player_id" type="INTEGER" />
  <foreign-key foreignTable="player">
    <reference local="player_id" foreign="id" />
  </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="player" />
  </behavior>
</table>
<table name="pro_basketballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="salary" type="INTEGER" />
  <column name="basketballer_id" type="INTEGER" />
  <foreign-key foreignTable="basketballer">
    <reference local="basketballer_id" foreign="id" />
  </foreign-key>
  <column name="player_id" type="INTEGER" />
  <foreign-key foreignTable="player">
    <reference local="player_id" foreign="id" />
  </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="basketballer, player" />
  </behavior>
</table>
```

That way, you can call `setPoints()` as well as `setFirstName()` directly on `ProBasketballer`.

The `delegate` behavior offers much more than just simulating Class Table Inheritance. Discover other use cases and complete reference in the [delegate behavior documentation](../behaviors/delegate.html).

## Concrete Table Inheritance ##

Concrete Table Inheritance uses one table for each class in the hierarchy. Each table contains columns for the class and all its ancestors, so any fields in a superclass are duplicated across the tables of the subclasses.

Propel implements Concrete Table Inheritance through a behavior.

### Schema Definition ###

Once again, this is easier to understand through an example. In a Content Management System, content types are often organized in a hierarchy, each subclass adding more fields to the superclass. So let's consider the following schema, where the `article` and `video` tables use the same fields as the main `content` tables, plus additional fields:

```xml
<table name="content">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="category_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="category" onDelete="cascade">
    <reference local="category_id" foreign="id" />
  </foreign-key>
</table>
<table name="category">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="name" type="VARCHAR" size="100" primaryString="true" />
</table>
<table name="article">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="body" type="VARCHAR" size="100"/>
  <column name="category_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="category" onDelete="cascade">
    <reference local="category_id" foreign="id" />
  </foreign-key>
</table>
<table name="video">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="resource_link" type="VARCHAR" size="100"/>
  <column name="category_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="category" onDelete="cascade">
    <reference local="category_id" foreign="id" />
  </foreign-key>
</table>
```

Since the columns of the main table are copied to the child tables, this schema is a simple implementation of Concrete Table Inheritance. This is something that you can write by hand, but the repetition makes it tedious. Instead, you should let the `concrete_inheritance` behavior do it for you:

```xml
<table name="content">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="category_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="category" onDelete="cascade">
    <reference local="category_id" foreign="id" />
  </foreign-key>
</table>
<table name="category">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="name" type="VARCHAR" size="100" primaryString="true" />
</table>
<table name="article">
  <behavior name="concrete_inheritance">
    <parameter name="extends" value="content" />
  </behavior>
  <column name="body" type="VARCHAR" size="100"/>
</table>
<table name="video">
  <behavior name="concrete_inheritance">
    <parameter name="extends" value="content" />
  </behavior>
  <column name="resource_link" type="VARCHAR" size="100"/>
</table>
```

>**Tip**<br />The `concrete_inheritance` behavior copies columns, foreign keys and indices. If you've configured the `validate` behavior, it copies also validators.

### Using Inherited Model Classes ###

For each of the tables in the schema above, Propel generates a Model class:

```php
<?php
// create a new Category
$cat = new Category();
$cat->setName('Movie');
$cat->save();
// create a new Article
$art = new Article();
$art->setTitle('Avatar Makes Best Opening Weekend in the History');
$art->setCategory($cat);
$art->setContent('With $232.2 million worldwide total, Avatar had one of the best-opening weekends in the history of cinema.');
$art->save();
// create a new Video
$vid = new Video();
$vid->setTitle('Avatar Trailer');
$vid->setCategory($cat);
$vid->setResourceLink('http://www.avatarmovie.com/index.html')
$vid->save();
```

And since the `concrete_inheritance` behavior tag defines a parent table, the `Article` and `Video` classes extend the `Content` class (same for the generated Query classes):

```php
<?php
// methods of the parent model are accessible to the child models
class Content extends BaseContent
{
  public function getCategoryName()
  {
    return $this->getCategory()->getName();
  }
}
echo $art->getCategoryName(); // 'Movie'
echo $vid->getCategoryName(); // 'Movie'

// methods of the parent query are accessible to the child query
class ContentQuery extends BaseContentQuery
{
  public function filterByCategoryName($name)
  {
    return $this
      ->useCategoryQuery()
        ->filterByName($name)
      ->endUse();
  }
}
$articles = ArticleQuery::create()
  ->filterByCategoryName('Movie')
  ->find();
```

That makes of Concrete Table Inheritance a powerful way to organize your model logic and to avoid repetition, both in the schema and in the model code.

### Data Replication ###

By default, every time you save an `Article` or a `Video` object, Propel saves a copy of the `title` and `category_id` columns in a `Content` object. Consequently, retrieving objects regardless of their child type becomes very easy:

```php
<?php
$conts = ContentQuery::create()->find();
foreach ($conts as $content) {
  echo $content->getTitle() . "(". $content->getCategoryName() ")/n";
}
// Avatar Makes Best Opening Weekend in the History (Movie)
// Avatar Trailer (Movie)
```

Propel also creates a one-to-one relationship between a object and its parent copy. That's why the schema definition above doesn't define any primary key for the `article` and `video` tables: the `concrete_inheritance` behavior creates the `id` primary key which is also a foreign key to the parent `id` column. So once you have a parent object, getting the child object is just one method call away:

```php
<?php
class Article extends BaseArticle
{
  public function getPreview()
  {
    return $this->getContent();
  }
}
class Movie extends BaseMovie
{
  public function getPreview()
  {
    return $this->getResourceLink();
  }
}
$conts = ContentQuery::create()->find();
foreach ($conts as $content) {
  echo $content->getTitle() . "(". $content->getCategoryName() ")/n"
  if ($content->hasChildObject()) {
    echo '  ' . $content->getChildObject()->getPreview(), "\n";
}
// Avatar Makes Best Opening Weekend in the History (Movie)
//   With $232.2 million worldwide total, Avatar had one of the best-opening
//   weekends in the history of cinema.
// Avatar Trailer (Movie)
//   http://www.avatarmovie.com/index.html
```

The `hasChildObject()` and `getChildObject()` methods are automatically added by the behavior to the parent class. Behind the curtain, the saved `content` row has an additional `descendant_column` field allowing it to use the right model for the job.

>**Tip**<br />You can disable the data replication by setting the `copy_data_to_parent` parameter to "false". In that case, the `concrete_inheritance` behavior simply modifies the table at buildtime and does nothing at runtime. Also, with `copy_data_to_parent` disabled, any primary key copied from the parent table is not turned into a foreign key:

```xml
<table name="article">
  <behavior name="concrete_inheritance">
    <parameter name="extends" value="content" />
    <parameter name="copy_data_to_parent" value="false" />
  </behavior>
  <column name="body" type="VARCHAR" size="100"/>
</table>
// results in
<table name="article">
  <column name="body" type="VARCHAR" size="100"/>
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="title" type="VARCHAR" size="100"/>
  <column name="category_id" required="false" type="INTEGER" />
  <foreign-key foreignTable="category" onDelete="cascade">
    <reference local="category_id" foreign="id" />
  </foreign-key>
</table>
```
