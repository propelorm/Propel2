---
layout: documentation
title: Propel Query Reference
---

# Propel Query Reference #

Propel's Query classes make it easy to write queries of any level of complexity in a simple and reusable way.

## Overview ##

Propel proposes an object-oriented API for writing database queries. That means that you don't need to write any SQL code to interact with the database. Object orientation also facilitates code reuse and readability. Here is how to query the database for records in the `book` table ordered by the `title` column and published in the last month:

```php
<?php
$books = BookQuery::create()
  ->filterByPublishedAt(array('min' => time() - 30 * 24 * 60 * 60))
  ->orderByTitle()
  ->find();
```

The first thing to notice here is the fluid interface. Propel queries are made of method calls that return the current query object - `filterByPublishedAt()` and `orderByTitle()` return the current query augmented with conditions. `find()`, on the other hand, is a ''termination method'' that doesn't return the query, but its result - in this case, a collection of `Book` objects.

Propel generates one `filterByXXX()` method for every column in the table. The column name used in the PHP method is not the actual column name in the database ('`published_at`'), but rather a CamelCase version of it ('`PublishedAt`'), called the column ''phpName''. Remember to always use the phpName in the PHP code ; the actual SQL name only appears in the SQL code.

When a termination method like `find()` is called, Propel builds the SQL code and executes it. The previous example generates the following code when `find()` is called:

```php
<?php
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.PUBLISHED_AT >= :p1
ORDER BY book.TITLE ASC';
```

Propel uses the column name in conjunction with the schema to determine the column type. In this case, `published_at` is defined in the schema as a `TIMESTAMP`. Then, Propel ''binds'' the value to the condition using the column type. This prevents SQL injection attacks that often plague web applications. Behind the curtain, Propel uses PDO to achieve this binding:

```php
<?php
// $con is a PDO instance
$stmt = $con->prepare($query);
$stmt->bind(':p1', time() - 30 * 24 * 60 * 60, PDO::PARAM_INT);
$res = $stmt->execute();
```

The final `find()` doesn't just execute the SQL query above, it also instantiates `Book` objects and populates them with the results of the query. Eventually, it returns a `PropelCollection` object with these `Book` objects inside. For the sake of clarity, you can consider this collection object as an array. In fact, you can use it as if it were a true PHP array and iterate over the result list the usual way:

```php
<?php
foreach ($books as $book) {
  echo $book->getTitle();
}
```

So Propel queries are a very powerful tool to write your queries in an object-oriented fashion. They are also very natural - if you know how to write an SQL query, chances are that you will write Propel queries in minutes.

## Generated Query Methods ##

For each object, Propel creates a few methods in the generated query object.

### Column Filter Methods ###

`filterByXXX()`, generated for each column, provides a different feature and a different functionality depending on the column type:

* For all columns, `filterByXXX()` translates to a simple SQL `WHERE` condition by default:

```php
<?php
$books = BookQuery::create()
  ->filterByTitle('War And Peace')
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.TITLE = :p1'; // :p1 => 'War And Peace'
```

* For string columns, `filterByXXX()` translates to a SQL `WHERE ... LIKE` if the value contains wildcards:

```php
<?php
$books = BookQuery::create()
  ->filterByTitle('War%')
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.TITLE LIKE :p1'; // :p1 => 'War%'
```

* For numeric and temporal columns, `filterByXXX()` translates into an interval condition if the value is an associative array using 'min' and/or 'max' as keys:

```php
<?php
$books = BookQuery::create()
  ->filterById(array('min' => 123, 'max' => 456))
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.ID >= :p1 AND book.ID <= :p2)'; // :p1 => 123, :p2 => 456
```

 * For integer columns, `filterByXXX()` translates into a SQL `WHERE ... IN` if the value is an array:

```php
<?php
$books = BookQuery::create()
  ->filterByAuthorId(array(123, 456))
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.AUTHOR_ID IN (:p1, :p2)'; // :p1 => 123, :p2 => 456
```

 * For Boolean columns, `filterByXXX()` translates the value to a boolean using smart casting:

```php
<?php
$books = BookQuery::create()
  ->filterByIsPublished('yes') // 'yes', 'on', 'true', true, and 1 all translate to boolean true
  ->filterByIsSoldOut('no')    // 'no', 'off', 'false', false, and 0 all translate to boolean false
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.IS_PUBLISHED = :p1
  AND book.IS_SOLD_OUT = :p2'; // :p1 => true, :p2 => false
```

 * for Temporal columns, `filterByXXX()` accepts a string, a timestamp, or a DateTime value:

```php
<?php
$books = BookQuery::create()
  ->filterByPublishedAt(array('max' => time())
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.PUBLISHED_AT < :p1; // :p1 => 1291065396
```

 * for ENUM columns, `filterByXXX()` accepts one of the values defined in the `valueSet` attribute in the schema.

```php
<?php
// Example for the book table:
// <column name="style" type="ENUM" valueSet="novel, essay, poetry" />
$books = BookQuery::create()
  ->filterByStyle('novel')
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.STYLE = :p1; // :p1 => 0
```

 * for Object columns, `filterByXXX()` accepts a PHP object

```php
<?php
$houses = HouseQuery::create()
  ->filterByCoordinates(new GeographicCoordinates(48.8527, 2.3510))
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT house.* from `house`
WHERE house.COORDINATES = :p1'; // :p1 => 'O:21:"GeographicCoordinates":2:{s:8:"latitude";d:48.8527;s:9:"longitude";d:2.3510;}'
```

 * for Array columns, Propel stores a serialized version of the array that makes it searchable. Therefore, `filterByXXX()` accepts a PHP array. Use any of `Criteria::CONTAINS_ALL`, `Criteria::CONTAINS_SOME`, or `Criteria::CONTAINS_NONE` as second argument to the filter method.

```php
<?php
$books = BookQuery::create()
  ->filterByTags(array('novel', 'russian'), Criteria::CONTAINS_ALL)
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.TAGS LIKE :p1
AND   book.TAGS LIKE :p2'; // :p1 => '%| novel |%', :p2 => '%| russian |%'

$books = BookQuery::create()
  ->filterByTags(array('novel', 'russian'), Criteria::CONTAINS_NONE)
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.TAGS NOT LIKE :p1
AND   book.TAGS NOT LIKE :p2
OR    book.TAGS IS NULL'; // :p1 => '%| novel |%', :p2 => '%| russian |%'

// If the column name is plural, Propel also generates singular filter methods
// expecting a scalar parameter instead of an array
$books = BookQuery::create()
  ->filterByTag('russian')
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.TAGS LIKE :p1'; // :p1 => '%| russian |%'
```

>**Tip**<br />Filters on array columns translate to SQL as LIKE conditions. That means that the resulting query often requires an expensive table scan, and is not suited for large tables.

### Relation Filter Methods ###

Propel also generates a `filterByXXX()` method for every foreign key. The filter expects an object of the related class as parameter:

```php
<?php
$author = AuthorQuery::create()->findPk(123);
$books = BookQuery::create()
  ->filterByAuthor($author)
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from `book`
WHERE book.AUTHOR_ID = :p1'; // :p1 => 123
```

Check the generated BaseQuery classes for a complete view of the generated query methods. Every generated method comes with a detailed phpDoc comment, making code completion very easy on supported IDEs.

### Embedding a Related Query ###

In order to add conditions on related tables, a propel query can ''embed'' the query of the related table. The generated `useXXXQuery()` serve that purpose. For instance, here is how to query the database for books written by 'Leo Tolstoi':

```php
<?php
$books = BookQuery::create()
  ->useAuthorQuery()
    ->filterByName('Leo Tolstoi')
  ->endUse()
  ->find();
```

`useAuthorQuery()` returns a new instance of `AuthorQuery` already joined with the current `BookQuery` instance. The next method is therefore called on a different object - that's why the `filterByName()` call is further indented in the code example. Finally, `endUse()` merges the conditions applied on the `AuthorQuery` to the `BookQuery`, and returns the original `BookQuery` object.

Propel knows how to join the `Book` model to the `Author` model, since you already defined a foreign key between the two tables in the `schema.xml`. Propel takes advantage of this knowledge of your model relationships to help you write faster queries and omit the most obvious data.

```php
<?php
// example Query generated for a MySQL database
$query = 'SELECT book.* from book
INNER JOIN author ON book.AUTHOR_ID = author.ID
WHERE author.NAME = :p1'; // :p1 => 'Leo Tolstoi'
```

You can customize the related table alias and the join type by passing arguments to the `useXXXQuery()` method:

```php
<?php
$books = BookQuery::create()
  ->useAuthorQuery('a', 'left join')
    ->filterByName('Leo Tolstoi')
  ->endUse()
  ->find();
// example Query generated for a MySQL database
$query = 'SELECT book.* from book
LEFT JOIN author a ON book.AUTHOR_ID = a.ID
WHERE a.NAME = :p1'; // :p1 => 'Leo Tolstoi'
```

The `useXXXQuery()` methods allow for very complex queries. You can mix them, nest them, and reopen them to add more conditions.

## Inherited Methods ##

The generated Query classes extend a core Propel class named `ModelCriteria`, which provides even more methods for building your queries.

### Finding An Object From Its Primary Key ###

```php
<?php
// Finding the book having primary key 123
$book = BookQuery::create()->findPk(123);
// Finding the books having primary keys 123 and 456
$books = BookQuery::create()->findPks(array(123, 456));
// Also works for objects with composite primary keys
$bookOpinion = BookOpinionQuery::create()->findPk(array($bookId, $userId));
```

### Finding Objects ###

```php
<?php
// Finding all Books
$articles = BookQuery::create()
  ->find();
// Finding 3 Books
$articles = BookQuery::create()
  ->limit(3)
  ->find();
// Finding a single Book
$article = BookQuery::create()
  ->findOne();
```

### Using Magic Query Methods ###

```php
<?php
// The query recognizes method calls composed of `findOneBy` or `findBy`, and a column name.
$book = BookQuery::create()->findOneByTitle('War And Peace');
// same as
$book = BookQuery::create()
  ->filterByTitle('War And Peace')
  ->findOne();

$books = BookQuery::create()->findByTitle('War And Peace');
// same as
$books = BookQuery::create()
  ->filterByTitle('War And Peace')
  ->find();

// You can even combine several column conditions in a method name, if you separate them with 'And'
$book = BookQuery::create()->findOneByTitleAndAuthorId('War And Peace', 123);
// same as
$book = BookQuery::create()
  ->filterByTitle('War And Peace')
  ->filterById(123)
  ->findOne();
```

### Ordering Results ###

```php
<?php
// Finding all Books ordered by published_at (ascending order by default)
$books = BookQuery::create()
  ->orderByPublishedAt()
  ->find();
// Finding all Books ordered by published_at desc
$books = BookQuery::create()
  ->orderByPublishedAt('desc')
  ->find();
```

### Specifying A Connection ###

```php
<?php
// All the termination methods accept a Connection object
// So you can specify which connection to use
$con = Propel::getReadConnection(BookTableMap::DATABASE_NAME);
$nbBooks = BookQuery::create()
  ->findOne($con);
```

>**Tip**<br />In debug mode, the connection object provides a way to check the latest executed query, by calling `$con->getLastExecutedQuery()`. See the [Logging documentation](../documentation/08-logging.html) for more details.

### Counting Objects ###

```php
<?php
// Counting all Books
$nbBooks = BookQuery::create()
  ->count($con);
// This is much faster than counting the results of a find()
// since count() doesn't populate Model objects
```

### Deleting Objects ###

```php
<?php
// Deleting all Books
$nbDeletedBooks = BookQuery::create()
  ->deleteAll($con);
// Deleting a selection of Books
$nbDeletedBooks = BookQuery::create()
  ->filterByTitle('Pride And Prejudice')
  ->delete($con);
```

### Updating Objects ###

```php
<?php
// Test data
$author1 = new Author();
$author1->setName('Jane Austen');
$author1->save();
$author2 = new Author();
$author2->setName('Leo Tolstoy');
$author2->save();

// update() issues an UPDATE ... SET query based on an associative array column => value
$nbUpdatedRows = AuthorQuery::create()
  ->filterByName('Leo Tolstoy')
  ->update(array('Name' => 'Leo Tolstoi'), $con);

// update() returns the number of modified columns
echo $nbUpdatedRows; // 1

// Beware that update() updates all records found in a single row
// And bypasses any behavior registered on the save() hooks
// You can force a one-by-one update by setting the third parameter of update() to true
$nbUpdatedRows = AuthorQuery::create()
  ->filterByName('Leo Tolstoy')
  ->update(array('Name' => 'Leo Tolstoi'), $con, true);
// Beware that it may take a long time
```

### Getting Columns Instead Of Objects ###

```php
<?php
// When you only need a few columns, it is faster to skip object hydration
// In such cases, call select() before find()
$articles = ArticleQuery::create()
  ->join('Category')
  ->select(array('Id', 'Title', 'Content', 'Category.Name'))
  ->find();
// returns PropelArrayCollection(
//   array('Id' => 123, 'Title' => 'foo', 'Content' => 'This is foo', 'Category.Name' => 'Miscellaneous'),
//   array('Id' => 456, 'Title' => 'bar', 'Content' => 'This is bar', 'Category.Name' => 'Main')
// )

// When you need only one record, use select() with findOne()
$articles = ArticleQuery::create()
  ->join('Category')
  ->select(array('Id', 'Title', 'Content', 'Category.Name'))
  ->findOne();
// returns array('Id' => 123, 'Title' => 'foo', 'Content' => 'This is foo', 'Category.Name' => 'Miscellaneous')

// When you need only one column, use a column name as the select() argument
$articles = ArticleQuery::create()
  ->join('Category')
  ->select('Title')
  ->find();
// returns array('foo', 'bar')

// When you need only one column from one record, use select() and findOne()
$articles = ArticleQuery::create()
  ->join('Category')
  ->select('Title')
  ->findOne();
// returns 'foo'

// select() accepts calculated columns
// The calculated column MUST have an alias
$nbComments = ArticleQuery::create()
  ->join('Comment')
  ->withColumn('count(Comment.ID)', 'nbComments')
  ->groupBy('Article.Title')
  ->select(array('Article.Title', 'nbComments'))
  ->find();
// returns PropelArrayCollection(
//   array('Article.Title' => 'foo', 'nbComments' => 25),
//   array('Article.Title' => 'bar', 'nbComments' => 32)
// )

// When you want to select all the columns from the main class, use select('*')
$articles = ArticleQuery::create()
  ->select('*')
  ->find();
// returns PropelArrayCollection(
//   array('Id' => 123, 'Title' => 'foo', 'Content' => 'This is foo'),
//   array('Id' => 456, 'Title' => 'bar', 'Content' => 'This is bar')
// )
```

### Creating An Object Based on a Query ###

You may often create a new object based on values used in conditions if a query returns no result. This happens a lot when dealing with cross-reference tables in many-to-many relationships. To avoid repeating yourself, use `findOneOrCreate()` instead of `findOne()` in such cases:

```php
<?php
// The long way
$bookTag = BookTagQuery::create()
  ->filterByBook($book)
  ->filterByTag('crime')
  ->findOne();
if (!$bookTag) {
	$bookTag = new BookTag();
	$bookTag->setBook($book);
	$bookTag->setTag('crime');
}
// The short way
$bookTag = BookTagQuery::create()
  ->filterByBook($book)
  ->filterByTag('crime')
  ->findOneOrCreate();
```

### Reusing A Query ###

By default, termination methods like `findOne()`, `find()`, `count()`, `paginate()`, or `delete()` clone the original query. That means that you can reuse a query after a termination method:

```php
<?php
$q = BookQuery::create()->filterByIsPublished(true);
$book = $q->findOneByTitle('War And Peace');
// findOneByXXX() adds a limit(1) to the query
// but further reuses of the query are not affected
echo $q->count(); // 34

// You can disable query reuse by calling keepQuery(false) before the termination method
// This will bring a small boost in performance
$q = BookQuery::create()->filterByIsPublished(true)->keepQuery(false);
$book = $q->findOneByTitle('War And Peace');
echo $q->count(); // 1
```

## Relational API ##

For more complex queries, you can use an alternative set of methods, closer to the relational logic of SQL, to make sure that Propel issues exactly the SQL query you need.

This alternative API uses methods like `where()`, `join()` and `orderBy()` that translate directly to their SQL equivalent - `WHERE`, `JOIN`, etc. Here is an example:

```php
<?php
$books = BookQuery::create()
  ->join('Book.Author')
  ->where('Author.Name = ?', 'Leo Tolstoi')
  ->orderBy('Book.Title', 'asc')
  ->find();

```

The names passed as parameters in these methods, like 'Book.Author', 'Author.Name', and 'Book.Title', are ''explicit column names''. These names are composed of the phpName of the model, and the phpName of the column, separated by a dot (e.g. 'Author.Name'). Manipulating object model names allows you to be detached from the actual data storage, and alter the database names without necessarily updating the PHP code. It also makes the use of table aliases much easier - more on that matter later.

Propel knows how to map the explicit column names to database column names in order to translate the Propel query into an actual database query:

```php
<?php
$query = 'SELECT book.* from book
INNER JOIN author ON book.AUTHOR_ID = author.ID
WHERE author.NAME = :p1
ORDER BY book.TITLE ASC';
```

In a `where()` call, the condition appears as a string. `'Author.Name = ?'` is such a condition. Propel uses the column name in conjunction with the schema to determine the column type. In this case, `author.name` is defined in the schema as a `VARCHAR`. Then, Propel binds the value to the condition using PDO and the correct column type, as when using a `filterByXXX()` method.

>**Tip**<br />Of course, you can mix the generated methods from your BaseQuery objects and the relational API methods in the same query object.

Let's dive in the alternative API.

### Adding A Simple Condition ###

```php
<?php
// Finding all Books where title = 'War And Peace'
$books = BookQuery::create()
  ->where('Book.Title = ?', 'War And Peace')
  ->find();
// Finding all Books where title is like 'War%'
$books = BookQuery::create()
  ->where('Book.Title LIKE ?', 'War%')
  ->find();
// Finding all Books published after $date
$books = BookQuery::create()
  ->where('Book.PublishedAt > ?', $date)
  ->find();
// Finding all Books with no author
$books = BookQuery::create()
  ->where('Book.AuthorId IS NULL')
  ->find();
// Finding all books from a list of authors
$books = BookQuery::create()
  ->where('Book.AuthorId IN ?', array(123, 542, 563))
  ->find();
// You can even use SQL functions inside conditions
$books = BookQuery::create()
  ->where('UPPER(Book.Title) = ?', 'WAR AND PEACE')
  ->find();
// When needed, you can specify the binding type as third parameter
$books = BookQuery::create()
  ->where("LOCATE('War', Book.Title) = ?", true, PDO::PARAM_BOOL)
  ->find();
```

### Combining Several Conditions ###

For speed reasons, `where()` only accepts simple conditions, with a single interrogation point for the value replacement. When you need to apply more than one condition, and combine them with a logical operator, you have to call `where()` multiple times.

```php
<?php
// Finding all books where title = 'War And Peace' and published after $date
$books = BookQuery::create()
  ->where('Book.Title = ?', 'War And Peace')
  ->where('Book.PublishedAt > ?', $date)
  ->find();
// For conditions chained with OR, use _or() before where()
$books = BookQuery::create()
  ->where('Book.Title = ?', 'War And Peace')
  ->_or()
  ->where('Book.Title LIKE ?', 'War%')
  ->find();
```

`_or()` can only combine one condition, therefore it's not suitable for logically complex conditions, that you would write in SQL with parenthesis. In such cases, you must create named conditions with `condition()`, and then combine them in an array that you can pass to `where()` instead of a single condition, as follows:

```php
<?php
// Finding all books where title = 'War And Peace' or like 'War%'
$books = BookQuery::create()
  ->condition('cond1', 'Book.Title = ?', 'War And Peace') // create a condition named 'cond1'
  ->condition('cond2', 'Book.Title LIKE ?', 'War%')       // create a condition named 'cond2'
  ->where(array('cond1', 'cond2'), 'or')                  // combine 'cond1' and 'cond2' with a logical OR
  ->find();
  // SELECT book.* from book WHERE (book.TITLE = 'War And Peace' OR book.TITLE LIKE 'War%');

// You can create a named condition from the combination of other named conditions by using `combine()`
// That allows for any level of complexity
$books = BookQuery::create()
  ->condition('cond1', 'Book.Title = ?', 'War And Peace') // create a condition named 'cond1'
  ->condition('cond2', 'Book.Title LIKE ?', 'War%')       // create a condition named 'cond2'
  ->combine(array('cond1', 'cond2'), 'or', 'cond12')      // create a condition named 'cond12' from 'cond1' and 'cond2'
  ->condition('cond3', 'Book.PublishedAt <= ?', $end)     // create a condition named 'cond3'
  ->condition('cond4', 'Book.PublishedAt >= ?', $begin)   // create a condition named 'cond4'
  ->combine(array('cond3', 'cond4'), 'and', 'cond34')     // create a condition named 'cond34' from 'cond3' and 'cond4'
  ->where(array('cond12', 'cond34'), 'and')               // combine the two conditions in a where
  ->find();
  // SELECT book.* FROM book WHERE (
  //  (book.TITLE = 'War And Peace' OR book.TITLE LIKE 'War%')
  //  AND
  //  (book.PUBLISHED_AT <= $end AND book.PUBLISHED_AT >= $begin)
  // );
```

>**Tip**<br />`_or()` also works for embedded queries, called before a `useXXXQuery()`:

```php
<?php
$books = BookQuery::create()
  ->filterByTitle('War and Peace')
  ->_or()
  ->useAuthorQuery()
    ->filterByName('Leo Tolstoi')
  ->endUse()
  ->find();

// example Query generated for a MySQL database
$query = 'SELECT book.* from book
INNER JOIN author ON book.AUTHOR_ID = author.ID
WHERE book.TITLE = :p1'   // :p1 => 'War and Peace'
   OR author.NAME = :p2'; // :p2 => 'Leo Tolstoi'
```

### Joining Tables ###

```php
<?php
// Test data
$author1 = new Book();
$author1->setName('Jane Austen');
$author1->save();
$book1 = new Book();
$book1->setTitle('Pride And Prejudice');
$book1->setAuthor($author1);
$book1->save();

// Add a join statement
// No need to tell the query which columns to use for the join, just the related Class
// After all, the columns of the FK are already defined in the schema.
$book = BookQuery::create()
  ->join('Book.Author')
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();
  // SELECT book.* FROM book
  // INNER JOIN author ON book.AUTHOR_ID = author.ID
  // WHERE author.NAME = 'Jane Austin'
  // LIMIT 1;

// The default join() call results in a SQL INNER JOIN clause
// For LEFT JOIN or RIGHT JOIN clauses, use leftJoin() or rightJoin() instead of join()
$book = BookQuery::create()
  ->leftJoin('Book.Author')
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();

// You can chain joins if you want to make more complex queries
$review = new Review();
$review->setBook($book1);
$review->setRecommended(true);
$review->save();

$author = BookQuery::create()
  ->join('Author.Book')
  ->join('Book.Review')
  ->where('Review.Recommended = ?', true)
  ->findOne();

// Alternatively, you can use the generated joinXXX() methods
// Which are a bit faster than join(), but limited to the current model's relationships
$book = BookQuery::create()
  ->joinAuthor()
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();
// The join type depends on the required attribute of the foreign key column
// If the column is required, then the default join type is an INNER JOIN
// Otherwise, the default join type is a LEFT JOIN
// You can override the default join type for a given relationship
// By setting the joinType attribute of the foreign key element in the schema.xml

// Add more conditions to an existing join
// by calling addJoinCondition($joinName, $clause, $value)
$book = BookQuery::create()
  ->joinAuthor()
  ->addJoinCondition('Author', 'Author.LastName <> ?', 'Austen')
  ->findOne();
  // SELECT book.* FROM book
  // INNER JOIN author ON (book.AUTHOR_ID = author.ID AND author.LAST_NAME <> 'Austen')
  // LIMIT 1;
```

### Table Aliases ###

```php
<?php
// The first argument of BookQuery::create() defines a table alias
$books = BookQuery::create('b')
  ->where('b.Title = ?', 'Pride And Prejudice')
  ->find();

// join(), leftJoin() and rightJoin() also allow table aliases
$author = AuthorQuery::create('a')
  ->join('a.Book b')
  ->join('b.Review r')
  ->where('r.Recommended = ?', true)
  ->findOne();

// Table aliases can be used in all query methods (where, groupBy, orderBy, etc.)
$books = BookQuery::create('b')
  ->where('b.Title = ?', 'Pride And Prejudice')
  ->orderBy('b.Title')
  ->find();

// Table aliases are mostly useful to join the current table,
// or to handle multiple foreign keys on the same column
$employee = EmployeeQuery::create('e')
  ->innerJoin('e.Supervisor s')
  ->where('s.Name = ?', 'John')
  ->find();
```

### Minimizing Queries ###

Even if you do a join, Propel will issue new queries when you fetch related objects:

```php
<?php
$book = BookQuery::create()
  ->join('Book.Author')
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();
$author = $book->getAuthor();  // Needs another database query
```

Propel allows you to retrieve the main object together with related objects in a single query. You just have to call the `with()` method to specify which objects the main object should be hydrated with.

```php
<?php
$book = BookQuery::create()
  ->join('Book.Author')
  ->with('Author')
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();
$author = $book->getAuthor();  // Same result, with no supplementary query
```

`with()` expects a relation name, as declared previously by `join()`. In practice, that means that `with()` and `join()` should always come one after the other. To avoid repetition, use `joinWith()` to both add a `join()` and a `with()` on a relation. So the shorter way to write the previous query is:

```php
<?php
$book = BookQuery::create()
  ->joinWith('Book.Author')
  ->where('Author.Name = ?', 'Jane Austen')
  ->findOne();
$author = $book->getAuthor();  // Same result, with no supplementary query
```

Since the call to `with()` adds the columns of the related object to the SELECT part of the query, and uses these columns to populate the related object, that means that `joinWith()` is slower and consumes more memory that `join()`. So use it only when you actually need the related objects afterwards.

`with()` and `joinWith()` are not limited to immediate relationships. As a matter of fact, just like you can chain `join()` calls, you can chain `joinWith()` calls to populate a chain of objects:

```php
<?php
$review = ReviewQuery::create()
  ->joinWith('Review.Book')
  ->joinWith('Book.Author')
  ->joinWith('Book.Publisher')
  ->findOne();
$book = $review->getBook()          // No additional query needed
$author = $book->getAuthor();       // No additional query needed
$publisher = $book->getPublisher(); // No additional query needed
```

So `joinWith()` is very useful to minimize the number of database queries. As soon as you see that the number of queries necessary to perform an action is proportional to the number of results, adding `With` after `join()` calls is the trick to get down to a more reasonable query count.

### Adding Columns ###

Sometimes you don't need to hydrate a full object in addition to the main object. If you only need one additional column, the `withColumn()` method is a good alternative to `joinWith()`, and it speeds up the query:

```php
<?php
$book = BookQuery::create()
  ->join('Book.Author')
  ->withColumn('Author.Name', 'AuthorName')
  ->findOne();
$authorName = $book->getAuthorName();
```

Propel adds the 'with' column to the SELECT clause of the query, and uses the second argument of the `withColumn()` call as a column alias. This additional column is later available as a 'virtual' column, i.e. using a getter that does not correspond to a real column. You don't actually need to write the `getAuthorName()` method ; Propel uses the magic `__call()` method of the generated `Book` class to catch the call to a virtual column.

>**Tip**<br />You can call `withColumn()` multiple times to add more than one virtual column to the resulting objects.

`withColumn()` is also of great use to add calculated columns, using aggregate functions and a GROUP BY statement:

```php
<?php
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->withColumn('COUNT(Book.Id)', 'NbBooks')
  ->groupBy('Author.Id')
  ->find();
foreach ($authors as $author) {
	echo $author->getName() . ': ' . $author->getNbBooks() . " books\n";
}
```

With a single SQL query, you can have both a list of objects and an additional column for each object. That makes of `withColumn()` a great query saver.

>**Tip**<br />Users of PostgreSQL will need to use the alternative method `groupByClass($class)` to force the grouping on all the columns of a given model whenever they use an aggregate function:

```php
<?php
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->withColumn('COUNT(Book.Id)', 'NbBooks')
  ->groupByClass('Author')
  ->find();
```

### Adding A Comment ###

```php
<?php
// You can add a comment to the query object
// For easier SQL log parsing
AuthorQuery::create()
  ->setComment('Author Deletion')
  ->filterByName('Leo Tolstoy')
  ->delete($con);
// The comment ends up in the generated SQL query
// DELETE /* Author Deletion */ FROM `author` WHERE author.NAME = 'Leo Tolstoy'
```

### Using Methods From Another Query Class ###

After writing custom methods to query objects, developers often meet the need to use the method from another query. For instance, in order to select the authors of the most recent books, you may want to write:

```php
<?php
// This doesn't work
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->recent()
  ->find();
```

The problem is that `recent()` is a method of `BookQuery`, not of the `AuthorQuery` class that the `create()` factory returns.

Does that mean that you must repeat the `BookQuery::recent()` code into a new `AuthorQuery::recentBooks()` method? That would imply repeating the same code in two classes, which is not a good practice. Instead, use the `useQuery()` and `endUse()` combination to use the methods of `BookQuery` inside `AuthorQuery`:

```php
<?php
// This works
$authors = AuthorQuery::create()
  ->join('Author.Book')
  ->useQuery('Book')
    ->recent()
  ->endUse()
  ->find();
```

This is exactly what the generated `useBookQuery()` does, except that you have more control over the join type and alias when you use the relational API. Behind the scene, `useQuery('Book')` creates a `BookQuery` instance and returns it. So the `recent()` call is actually called on `BookQuery`, not on `ArticleQuery`. Upon calling `endUse()`, the `BookQuery` merges into the original `ArticleQuery` and returns it. So the final `find()` is indeed called on the `AuthorQuery` instance.

You can nest queries in as many levels as you like, in order to avoid the repetition of code in your model.

>**Tip**<br />If you define an alias for the relation in `join()`, you must pass this alias instead of the model name in `useQuery()`.

```php
<?php
$authors = AuthorQuery::create('a')
  ->join('a.Book b')
  ->useQuery('b')
    ->recent()
  ->endUse()
  ->find();
```

### Using A Query As Input For A Second Query (Table Subqueries) ###

SQL supports table subqueries (a.k.a "inline view" in Oracle) to solve complex cases that a single query can't solve, or to optimize slow queries with several joins. For instance, to find the latest book written by every author in SQL, it usually takes a query like the following:

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

To achieve this query using Propel, call `addSelectQuery()` to use a first query as the source for the SELECT part of a second query:

```php
<?php

$latestBooks = BookQuery::create()
  ->withColumn('MAX(Book.CreatedAt)')
  ->groupBy('Book.AuthorId');
$latestCheapBooks = BookQuery::create()
  ->addSelectQuery($latestBooks, 'lastBook')
  ->where('lastBook.Price < ?', 20)
  ->find();
```

You could use two queries or a WHERE IN to achieve the same result, but it wouldn't be as effective.

### Fluid Conditions ###

Thanks to the query factories and the fluid interface, developers can query the database without creating a variable for the Query object. This helps a lot to reduce the amount of code necessary to write a query, and it also makes the code more readable.

But when you need to call a method on a Query object only if a certain condition is satisfied, it becomes compulsory to use a variable for the Query object:

```php
<?php
// find all the published books, except if the user is an editor,
// in which case also include non-published books
$query = BookQuery::create();
if (!$user->isEditor()) {
  $query->where('Book.IsPublished = ?', true);
}
$books = $query
  ->orderByTitle()
  ->find();
```

The `ModelCriteria` class offers a neat way to keep your code to a minimum in such occasions. It provides `_if()` and `_endif()` methods allowing for inline conditions. Using theses methods, the previous query can be written as follows:

```php
<?php
// find all the published books, except if the user is an editor
$books = BookQuery::create()
  ->_if(!$user->isEditor())
    ->where('Book.IsPublished = ?', true)
  ->_endif()
  ->orderByTitle()
  ->find();
```

The method calls enclosed between `_if($cond)` and `_endif()` will only be executed if the condition is true. To complete the list of tools available for fluid conditions, you can also use `_else()` and `_elseif($cond)`.

### More Complex Queries ###

The Propel Query objects have even more methods that allow you to write queries of any level of complexity. Check the API documentation for the `ModelCriteria` class to see all methods.

```php
<?php
// Query Filters (return a query object)
distinct()
limit($limit)
offset($offset)
where($clause, $value)
where($clause, $value, $bindingType = PDO::PARAM_STR)
where($conditions, $operator)
_or()
filterBy($column, $value, $comparison)
filterByArray($conditions)
condition($name, $clause, $value)
condition($name, $clause, $value, $bindingType = PDO::PARAM_STR)
combine($conditions, $operator = 'and', $name)
having($clause, $value)
having($clause, $value, $bindingType = PDO::PARAM_STR)
having($conditions, $operator)
orderBy($columnName, $order = 'asc')
groupBy($columnName)
join($class, $joinType = 'inner join')
with($relation, $joinType = 'inner join')
withColumn($clause, $alias)
prune($object)
comment($comment)

// termination methods (return model objects)
count($con = null)
find($con = null)
findOne($con = null)
finOneOrCreate($con = null)
findBy($columnName, $value, $con = null)
findByArray($conditions, $con = null)
findOneBy($columnName, $value, $con = null)
findByOneArray($conditions, $con = null)
findPk($pk, $con = null)
findPks($pks, $con = null)
delete($con = null)
update($values, $con = null, $forceIndividualSaves = false)
```

## Collections, Pager, and Formatters ##

### PropelCollection Methods ###

```php
<?php
// find() returns a PropelCollection, which you can use just like an array
$books = BookQuery::create()->find(); // $books behaves like an array
?>
There are <?php echo count($books) ?> books:
<ul>
  <?php foreach ($books as $book): ?>
  <li>
    <?php echo $book->getTitle() ?>
  </li>
  <?php endforeach; ?>
</ul>

<?php
// But a PropelCollection is more than just an array.
// That means you can call some special methods on it.
$books = BookQuery::create()->find(); // $books is an object
?>

<?php if($books->isEmpty()): ?>
There are no books.
<?php else: ?>
There are <?php echo $books->count() ?> books:
<ul>
  <?php foreach ($books as $book): ?>
  <li class="<?php echo $books->isOdd() ? 'odd' : 'even' ?>">
    <?php echo $book->getTitle() ?>
  </li>
  <?php if($books->isLast()): ?>
  <li>Do you want more books?</li>
  <?php endif; ?>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
```

Here is the list of methods you can call on a PropelCollection:

```php
<?php
// introspection methods
array getData()  // return a copy of the result array
// information methods on the current element in the method
bool  isFirst()
bool  isLast()
bool  isEmpty()
bool  isOdd()
bool  isEven()
bool  contains($value)
// access methods
mixed getFirst()
mixed getPrevious()
mixed getCurrent()
mixed getKey()
mixed getNext()
mixed getLast()
mixed get($position)
mixed search($value)
// manipulation methods
mixed pop()
mixed shift()
void  append($value)
int   prepend($value)
mixed set($position, $value)
mixed remove($position)
mixed clear()
// Model methods
void  save() // save all the objects in the collection
void  delete() // delete all the objects in the collection
array getPrimaryKeys() // get an array of the primary keys of all the objects in the collection
coll  populateRelation($name) // makes an additional query to populate the objects related to the current collection objects
// Import/Export methods
array toArray() // exports all the objects as array
array toKeyValue($keyColumn, $valueColumn) // exports all the objects as a hash
string toXML() // exports all the objects as an XML string
string toYAML() // exports all the objects as a YAML string
string toJSON() // exports all the objects as a JSON string
string toCSV() // exports all the objects as a CSV string
string __toString() // exports to a string using the default string representation (YAML)
void  fromArray($array) // imports a collection from an array
void toXML($xml) // imports a collection from an XML string
void toYAML($yaml) // imports a collection from a YAML string
void toJSON($json) // imports a collection from a JSON string
void toCSV($csv) // imports a collection from a CSV string
```

>**Tip**<br />`PropelCollection` extends `ArrayObject`, so you can also call all the methods of this SPL class on a collection (including `count()`, `append()`, `ksort()`, etc.).

### Paginating Results ###

```php
<?php
// use paginate() instead of find() as termination method to paginate results
$bookPager = BookQuery::create()
  ->paginate($page = 1, $maxPerPage = 10, $con);
// paginate() returns a PropelModelPager object, which behaves just like a collection
?>

<?php if($bookPager->isEmpty()): ?>
There are no books.
<?php else: ?>
There are <?php echo $bookPager->count() ?> books:
<ul>
  <?php foreach ($bookPager as $book): ?>
  <li class="<?php echo $bookPager->isOdd() ? 'odd' : 'even' ?>">
    <?php echo $book->getTitle() ?>
  </li>
  <?php if($bookPager->isLast()): ?>
  <li>Do you want more books?</li>
  <?php endif; ?>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php // PropelModelPager offers a convenient API to display pagination controls ?>
<?php if($bookPager->haveToPaginate()): ?>
  <p>Page <?php echo $bookPager->getPage() ?> of <?php echo $bookPager->getLastPage() ?></p>
  <p>
  	Displaying results <?php echo $bookPager->getFirstIndex() ?> to <?php echo $bookPager->getLastIndex() ?>
  	on a total of <?php echo $bookPager->getNbResults() ?>
	</p>
<?php endif; ?>
```

### Using An Alternative Formatter ###

By default, `find()` calls return a `PropelObjectCollection` of model objects. For performance reasons, you may want to get a collection of arrays instead. Use the `setFormatter()` to specify a custom result formatter.

```php
<?php
$book = BookQuery::create()
  ->setFormatter('PropelArrayFormatter')
  ->findOne();
print_r($book);
  => array('Id' => 123, 'Title' => 'War And Peace', 'ISBN' => '3245234535', 'AuthorId' => 456, 'PublisherId' => 567)
```

Of course, the formatters take the calls to `with()` into account, so you can end up with a precise array representation of a model object:

```php
<?php
$book = BookQuery::create()
  ->setFormatter('PropelArrayFormatter')
  ->with('Book.Author')
  ->with('Book.Publisher')
  ->findOne();
print_r($book);
  => array(
       'Id'          => 123,
       'Title'       => 'War And Peace',
       'ISBN'        => '3245234535',
       'AuthorId'    => 456,
       'PublisherId' => 567
       'Author'      => array(
         'Id'          => 456,
         'FirstName'   => 'Leo',
         'LastName'    => 'Tolstoi'
       ),
       'Publisher'   => array(
         'Id'          => 567,
         'Name'        => 'Penguin'
       )
     )
```

Propel provides four formatters:

 * `PropelObjectFormatter`: The default formatter, returning a model object for `findOne()`, and a `PropelObjectCollection` of model objects for `find()`
 * `PropelOnDemandFormatter`: To save memory for large resultsets, prefer this formatter ; it hydrates rows one by one as they are iterated on, and doesn't create a new Propel Model object at each row. Note that this formatter doesn't use the Instance Pool.
 * `PropelArrayFormatter`: The array formatter, returning an associative array for `findOne()`, and a `PropelArrayCollection` of arrays for `find()`
 * `PropelSimpleArrayFormatter`: An array formatter for `select()` queries, returning a string, an associative array for `findOne()`, or a `PropelArrayCollection` of arrays for `find()`
 * `PropelStatementFormatter`: The 'raw' formatter, returning a `PDOStatement` in any case.

You can easily write your own formatter to format the results the way you want. A formatter is basically a subclass of `PropelFormatter` providing a `format()` and a `formatOne()` method expecting a PDO statement.

## Writing Your Own business Logic Into A Query ##

### Custom Filters ###

You can add custom methods to the query objects to make your queries smarter, more reusable, and more readable. Don't forget to return the current object (`$this`) in the new methods.

```php
<?php
class BookQuery extends BaseBookQuery
{
	public function recent($nbDays = 5)
	{
		return $this->filterByPublishedAt(array('min' => time() - $nbDays * 24 * 60 * 60));
	}

	public function mostRecentFirst()
	{
		return $this->orderByPublishedAt('desc');
	}
}

// You can now use your custom query and its methods together with the usual ones
$books = BookQuery::create()
  ->recent()
  ->mostRecentFirst()
  ->find();
```

### Custom Hooks ###

The query objects also allow you to add code to be executed before each query, by implementing one of the following methods: `preSelect()`, `preUpdate()`, and `preDelete()`. It makes the implementation of a 'soft delete' behavior very straightforward:

```php
<?php
class BookQuery extends BaseBookQuery
{
	public function preSelect(PropelPDO $con)
	{
		// filter out the rows with a deletion date
		$this->filterByDeletedAt(null);
	}

	public function preDelete($con)
	{
		// mark the records as deleted instead of deleting them
		return $this->update(array('DeletedAt' => time()));
	}
}
```

>**Tip**<br />You can create several custom queries for a given model, in order to separate the methods into logical classes.

```php
<?php
class frontendBookQuery extends BookQuery
{
	public function preSelect()
	{
		return $this->where($this->getModelAliasOrName() . '.PublishedAt IS NOT NULL');
	}
}
// Use 'frontendBook' instead of 'Book' in the frontend to retrieve only published articles
$q = new frontendBookQuery();
$books = $q->find();
```

>**Tip**<br />Due to late static binding issues in PHP 5.2, you cannot use the `create()` factory on an inherited query - unless you override it yourself in the descendant class. Alternatively, Propel offers a global query factory named `PropelQuery`:

```php
<?php
// Use 'frontendBook' instead of 'Book' in the frontend to retrieve only published articles
$books = PropelQuery::from('frontendBook')->find();
```
