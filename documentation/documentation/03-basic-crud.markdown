---
layout: documentation
title: Basic C.R.U.D. Operations
---

# Basic C.R.U.D. Operations #

In this chapter, you will learn how to perform basic C.R.U.D. (Create, Retrieve, Update, Delete) operations on your database using Propel.

## Creating Rows ##

To add new data to the database, instantiate a Propel-generated object and then call the `save()` method. Propel will generate the appropriate INSERT SQL from the instantiated object.

But before saving it, you probably want to define the value for its columns. For that purpose, Propel has generated a `setXXX()` method for each of the columns of the table in the model object. So in its simplest form, inserting a new row looks like the following:

```php
<?php
/* initialize Propel, etc. */

$author = new Author();
$author->setFirstName('Jane');
$author->setLastName('Austen');
$author->save();
```

The column names used in the `setXXX()` methods correspond to the `phpName` attribute of the `<column>` tag in your schema, or to a CamelCase version of the column name if the `phpName` is not set.

In the background, the call to `save()` results in the following SQL being executed on the database:

```sql
INSERT INTO author (first_name, last_name) VALUES ('Jane', 'Austen');
```

## Reading Object Properties ##

Propel maps the columns of a table into properties of the generated objects. For each property, you can use a generated getter to access it.

```php
<?php
echo $author->getId();        // 1
echo $author->getFirstName(); // 'Jane'
echo $author->getLastName();  // 'Austen'
```

The `id` column was set automatically by the database, since the `schema.xml` defines it as an `autoIncrement` column. The value is very easy to retrieve once the object is saved: just call the getter on the column phpName.

These calls don't issue a database query, since the `Author` object is already loaded in memory.

You can also export all the properties of an object by calling one of the following methods: `toArray()`, `toXML()`, `toYAML()`, `toJSON()`, `toCSV()`, and `__toString()`:

```php
<?php
echo $author->toJSON();
// {"Id":1,"FirstName":"Jane","LastName":"Austen"}
```

>**Tip**<br />For each export method, Propel also provides an import method counterpart. So you can easily populate an object from an array using `fromArray()`, and from a string using any of `fromXML()`, `fromYAML()`, `fromJSON()`, and `fromCSV()`.

There are a lot more useful methods offered by the generated objects. You can find an extensive  list of these methods in the [Active Record reference](../reference/active-record).

## Retrieving Rows ##

Retrieving objects from the database, also referred to as _hydrating_ objects, is essentially the process of executing a SELECT query against the database and populating a new instance of the appropriate object with the contents of each returned row.

In Propel, you use the generated Query objects to retrieve existing rows from the database.

### Retrieving by Primary Key ###

The simplest way to retrieve a row from the database, is to use the generated `findPK()` method. It simply expects the value of the primary key of the row to be retrieved.

```php
<?php
$q = new AuthorQuery();
$firstAuthor = $q->findPK(1);
// now $firstBook is an Author object, or NULL if no match was found.
```

This issues a simple SELECT SQL query. For instance, for MySQL:

```sql
SELECT author.id, author.first_name, author.last_name
FROM `author`
WHERE author.id = 1
LIMIT 1;
```

When the primary key consists of more than one column, `findPK()` accepts multiple parameters, one for each primary key column.

>**Tip**<br />Every generated Query objects offers a factory method called `create()`. This methods creates a new instance of the query, and allows you to write queries in a single line:

```php
<?php
$firstAuthor = AuthorQuery::create()->findPK(1);
```

You can also select multiple objects based on their primary keys, by calling the generated `findPKs()` method. It takes an array of primary keys as a parameter:

```php
<?php
$selectedAuthors = AuthorQuery::create()->findPKs(array(1,2,3,4,5,6,7));
// $selectedAuthors is a collection of Author objects
```

### Querying the Database ###

To retrieve rows other than by the primary key, use the Query `find()` method.

An empty Query object carries no condition, and returns all the rows of the table
```php
<?php
$authors = AuthorQuery::create()->find();
// $authors contains a collection of Author objects
// one object for every row of the author table
foreach($authors as $author) {
  echo $author->getFirstName();
}
```

To add a simple condition on a given column, use one of the generated `filterByXXX()` methods of the Query object, where `XXX` is a column phpName. Since `filterByXXX()` methods return the current query object, you can continue to add conditions or end the query with the result of the method call. For instance, to filter by first name:

```php
<?php
$authors = AuthorQuery::create()
  ->filterByFirstName('Jane')
  ->find();
```

When you pass a value to a `filterByXXX()` method, Propel uses the column type to escape this value in PDO. This protects you from SQL injection risks.

>**Tip**<br />`filterByXXX()` is the preferred method for creating queries. It is very flexible and accepts values with wildcards as well as arrays for more complex use cases. See [Column Filter Methods](../reference/model-criteria.html#column_filter_methods) for details.

You can also easily limit and order the results on a query. Once again, the Query methods return the current Query object, so you can easily chain them:

```php
<?php
$authors = AuthorQuery::create()
  ->orderByLastName()
  ->limit(10)
  ->find();
```

`find()` always returns a collection of objects, even if there is only one result. If you know that you need a single result, use `findOne()` instead of `find()`. It will add the limit and return a single object instead of an array:

```php
<?php
$author = AuthorQuery::create()
  ->filterByFirstName('Jane')
  ->findOne();
```

>**Tip**<br />Propel provides magic methods for this simple use case. So you can write the above query as:

```php
<?php
$author = AuthorQuery::create()->findOneByFirstName('Jane');
```

The Propel Query API is very powerful. The next chapter will teach you to use it to add conditions on related objects. If you can't wait, jump to the [Query API reference](../reference/model-criteria).

### Using Custom SQL ###

The `Query` class provides a relatively simple approach to constructing a query. Its database neutrality and logical simplicity make it a good choice for expressing many common queries. However, for a very complex query, it may prove more effective (and less painful) to simply use a custom SQL query to hydrate your Propel objects.

As Propel uses PDO to query the underlying database, you can always write custom queries using the PDO syntax. For instance, if you have to use a sub-select:

```php
<?php
use Propel\Runtime\Propel;
$con = Propel::getConnection(BookTableMap::DATABASE_NAME);
$sql = "SELECT * FROM book WHERE id NOT IN "
        ."(SELECT book_review.book_id FROM book_review"
        ." INNER JOIN author ON (book_review.author_id=author.ID)"
        ." WHERE author.last_name = :name)";
$stmt = $con->prepare($sql);
$stmt->execute(array(':name' => 'Austen'));
```

With only a little bit more work, you can also populate `Book` objects from the resulting statement. Create a new `PropelObjectCollection` for the `Book` model, and call the `format()` method using the statement:

```php
<?php
$formatter = new PropelObjectFormatter();
$formatter->setClass('Book');
$books = $formatter->format($stmt);
// $books contains a collection of Book objects
```

There are a few important things to remember when using custom SQL to populate Propel:

* The resultset columns must be numerically indexed
* The resultset must contain all the columns of the table (except lazy-load columns)
* The resultset must have columns _in the same order_ as they are defined in the `schema.xml` file

## Updating Objects ##

Updating database rows basically involves retrieving objects, modifying the contents, and then saving them. In practice, for Propel, this is a combination of what you've already seen in the previous sections:

```php
<?php
$author = AuthorQuery::create()->findOneByFirstName('Jane');
$author->setLastName('Austen');
$author->save();
```

Alternatively, you can update several rows based on a Query using the query object's `update()` method:

```php
<?php
AuthorQuery::create()
  ->filterByFirstName('Jane')
  ->update(array('LastName' => 'Austen'));
```

This last method is better for updating several rows at once, or if you didn't retrieve the objects before.

## Deleting Objects ##

Deleting objects works the same as updating them. You can either delete an existing object:

```php
<?php
$author = AuthorQuery::create()->findOneByFirstName('Jane');
$author->delete();
```

Or use the `delete()` method in the query:

```php
<?php
AuthorQuery::create()
  ->filterByFirstName('Jane')
  ->delete();
```

>**Tip**<br />A deleted object still lives in the PHP code. It is marked as deleted and cannot be saved anymore, but you can still read its properties:

```php
<?php
echo $author->isDeleted();    // true
echo $author->getFirstName(); // 'Jane'
```

## Query Termination Methods ##

The Query methods that don't return the current query object are called "Termination Methods". You've already seen come of them: `find()`, `findOne()`, `update()`, `delete()`. There are two more termination methods that you should know about:

`count()` returns the number of results of the query.

```php
<?php
$nbAuthors = AuthorQuery::create()->count();
```
You could also count the number of results from a find(), but that would be less effective, since it implies hydrating objects just to count them

`paginate()` returns a paginated list of results:

```php
$authorPager = AuthorQuery::create()->paginate($page = 1, $maxPerPage = 10);
// This method will compute an offset and a limit
// based on the number of the page and the max number of results per page.
// The result is a PropelModelPager object, over which you can iterate:
foreach ($authorPager as $author) {
  echo $author->getFirstName();
}
```

A pager object gives more information:

```php
<?php
echo $pager->getNbResults();   // total number of results if not paginated
echo $pager->haveToPaginate(); // return true if the total number of results exceeds the maximum per page
echo $pager->getFirstIndex();  // index of the first result in the page
echo $pager->getLastIndex();   // index of the last result in the page
$links = $pager->getLinks(5);  // array of page numbers around the current page; useful to display pagination controls
```

## Collections And On-Demand Hydration ##

The `find()` method of generated Model Query objects returns a `PropelCollection` object. You can use this object just like an array of model objects, iterate over it using `foreach`, access the objects by key, etc.

```php
<?php
$authors = AuthorQuery::create()
  ->limit(5)
  ->find();
foreach ($authors as $author) {
  echo $authors->getFirstName();
}
```

The advantage of using a collection instead of an array is that Propel can hydrate model objects on demand. Using this feature, you'll never fall short of memory when retrieving a large number of results. Available through the `setFormatter()` method of Model Queries, on-demand hydration is very easy to trigger:

```php
<?php
$authors = AuthorQuery::create()
  ->limit(50000)
  ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND) // just add this line
  ->find();
foreach ($authors as $author) {
  echo $author->getFirstName();
}
```

In this example, Propel will hydrate the `Author` objects row by row, after the `foreach` call, and reuse the memory between each iteration. The consequence is that the above code won't use more memory when the query returns 50,000 results than when it returns 5.

`ModelCriteria::FORMAT_ON_DEMAND` is one of the many formatters provided by the Query objects. You can also get a collection of associative arrays instead of objects, if you don't need any of the logic stored in your model object, by using `ModelCriteria::FORMAT_ARRAY`.

The [ModelCriteria Query API reference](../reference/model-criteria) describes each formatter, and how to use it.

## Propel Instance Pool ##

Propel keeps a list of the objects that you already retrieved in memory to avoid calling the same request twice in a PHP script. This list is called the instance pool, and is automatically populated from your past requests:

```php
<?php
// first call
$author1 = AuthorQuery::create()->findPk(1);
// Issues a SELECT query
...
// second call
$author2 = AuthorQuery::create()->findPk(1);
// Skips the SQL query and returns the existing $author1 object
```
