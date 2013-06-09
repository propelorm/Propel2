---
layout: documentation
title: Basic Relationships
---

# Basic Relationships #

The definition of foreign keys in your schema allows Propel to add smart methods to the generated model and query objects. In practice, these generated methods mean that you will never actually have to deal with primary and foreign keys yourself. It makes the task of dealing with relations extremely straightforward.

## Inserting A Related Row ##

Propel creates setters for related objects that simplify the foreign key handling. You don't actually have to define a foreign key value. Instead, just set a related object, as follows:

```php
<?php
$author = new Author();
$author->setFirstName("Leo");
$author->setLastName("Tolstoy");
$author->save();

$book = new Book();
$book->setTitle("War & Peace");
// associate the $author object with the current $book
$book->setAuthor($author);
$book->save();
```

Propel generates the `setAuthor()` method based on the `phpName` attribute of the `<foreign-key>` element in the schema. When the attribute is not set, Propel uses the `phpName` of the related table instead.

Internally, the call to `Book::setAuthor($author)` translates into `Book::setAuthorId($author->getId())`. But you don't actually have to save a Propel object before associating it to another. In fact, Propel automatically "cascades" `INSERT` statements when a new object has other related objects added to it.

For one-to-many relationships - meaning, from the other side of a many-to-one relationship - the process is a little different. In the previous example, one `Book` has one `Author`, but one `Author` has many `Books`. From the `Author` point of view, a one-to-many relationships relates it to `Book`. So Propel doesn't generate an `Author::setBook()`, but rather an `Author::addBook()`:

```php
<?php
$book = new Book();
$book->setTitle("War & Peace");
$book->save();

$author = new Author();
$author->setFirstName("Leo");
$author->setLastName("Tolstoy");
// associate the $book object with the current $author
$author->addBook($book);
$author->save();
```

The result is the same in the database - the `author_id` column of the `book` row is correctly set to the `id` of the `author` row.

## Save Cascade ##

As a matter of fact, you don't need to `save()` an object before relating it. Propel knows which objects are related to each other, and is capable of saving all the unsaved objects if they are related to each other.

The following example shows how to create new `Author` and `Publisher` objects, which are then added to a new `Book` object; all 3 objects are saved when the `Book::save()` method is eventually invoked.

```php
<?php
/* initialize Propel, etc. */

$author = new Author();
$author->setFirstName("Leo");
$author->setLastName("Tolstoy");
// no need to save the author yet

$publisher = new Publisher();
$publisher->setName("Viking Press");
// no need to save the publisher yet

$book = new Book();
$book->setTitle("War & Peace");
$book->setIsbn("0140444173");
$book->setPublisher($publisher);
$book->setAuthor($author);
$book->save(); // saves all 3 objects!
```

In practice, Propel _"cascades"_ the `save()` action to the related objects.

## Reading Related Object Properties ##

Just like the related object setters, Propel generates a getter for every relation:

```php
<?php
$book = BookQuery::create()->findPk(1);
$author = $book->getAuthor();
echo $author->getFirstName(); // 'Leo'
```

Since a relationship can also be seen from the other end, Propel allows the foreign table to retrieve the related objects as well:

```php
<?php
$author = AuthorQuery::create()->findPk(1);
$books = $author->getBooks();
foreach ($books as $book) {
  echo $book->getTitle();
}
```

Notice that Propel generated a `getBooks()` method returning an array of `Book` objects, rather than a `getBook()` method. This is because the definition of a foreign key defines a many-to-one relationship, seen from the other end as a one-to-many relationship.

>**Tip**<br />Propel also generates a `countBooks()` methods to get the number of related objects without hydrating all the `Book` objects. For performance reasons, you should prefer this method to `count($author->getBooks())`.

Getters for one-to-many relationship accept an optional query object. This allows you to hydrate related objects, or retrieve only a subset of the related objects, or to reorder the list of results:

```php
<?php
$query = BookQuery::create()
  ->orderByTitle()
  ->joinWith('Book.Publisher');
$books = $author->getBooks($query);
```

## Using Relationships In A Query ##

### Finding Records Related To Another One ###

If you need to find objects related to a model object that you already have, you can take advantage of the generated `filterByXXX()` methods in the query objects, where `XXX` is a relation name:

```php
<?php
$author = AuthorQuery::create()->findPk(1);
$books = BookQuery::create()
  ->filterByAuthor($author)
  ->orderByTitle()
  ->find();
```

You don't need to specify that the `author_id` column of the `Book` object should match the `id` column of the `Author` object. Since you already defined the foreign key mapping in your schema, Propel knows enough to figure it out.

### Embedding Queries ###

In SQL queries, relationships often translate to a `JOIN` statement. Propel abstracts this relational logic in the query objects, by allowing you to _embed_ a related query into another.

In practice, Propel generates one `useXXXQuery()` method for every relation in the Query objects. So the `BookQuery` class offers a `useAuthorQuery()` and a `usePublisherQuery()` method. These methods return a new Query instance of the related query class, that you can eventually merge into the main query by calling `endUse()`.

To illustrate this, let's see how to write the following SQL query with the Propel Query API:

```sql
SELECT book.*
FROM book INNER JOIN author ON book.AUTHOR_ID = author.ID
WHERE book.ISBN = '0140444173' AND author.FIRST_NAME = 'Leo'
ORDER BY book.TITLE ASC
LIMIT 10;
```

That would simply give:

```php
<?php
$books = BookQuery::create()
  ->filterByISBN('0140444173')
  ->useAuthorQuery() // returns a new AuthorQuery instance
    ->filterByFirstName('Leo') // this is an AuthorQuery method
  ->endUse() // merges the Authorquery in the main Bookquery and returns the BookQuery
  ->orderByTitle()
  ->limit(10)
  ->find();
```

Propel knows the columns to use in the `ON` clause from the definition of foreign keys in the schema. The ability to use methods of a related Query object allows you to keep your model logic where it belongs.

Of course, you can embed several queries to issue a query of any complexity level:

```php
<?php
// Find all authors of books published by Viking Press
$authors = AuthorQuery::create()
  ->useBookQuery()
    ->usePublisherQuery()
      ->filterByName('Viking Press')
    ->endUse()
  ->endUse()
  ->find();
```

You can see how the indentation of the method calls provide a clear explanation of the embedding logic. That's why it is a good practice to format your Propel queries with a single method call per line, and to add indentation every time a `useXXXQuery()` method is used.

## Many-to-Many Relationships ##

Databases typically use a cross-reference table, or junction table, to materialize the relationship. For instance, if the `user` and `group` tables are related by a many-to-many relationship, this happens through the rows of a `user_group` table. To inform Propel about the many-to-many relationship, set the `isCrossRef` attribute of the cross reference table to true:

```xml
<table name="user">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="name" type="VARCHAR" size="32"/>
</table>

<table name="group">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="name" type="VARCHAR" size="32"/>
</table>

<table name="user_group" isCrossRef="true">
  <column name="user_id" type="INTEGER" primaryKey="true"/>
  <column name="group_id" type="INTEGER" primaryKey="true"/>
  <foreign-key foreignTable="user">
    <reference local="user_id" foreign="id"/>
  </foreign-key>
  <foreign-key foreignTable="group">
    <reference local="group_id" foreign="id"/>
  </foreign-key>
</table>
```

Once you rebuild your model, the relationship is seen as a one-to-many relationship from both the `User` and the `Group` models. That means that you can deal with adding and reading relationships the same way as you usually do:

```php
<?php
$user = new User();
$user->setName('John Doe');
$group = new Group();
$group->setName('Anonymous');
// relate $user and $group
$user->addGroup($group);
// save the $user object, the $group object, and a new instance of the UserGroup class
$user->save();
```

The same happens for reading related objects ; Both ends see the relationship as a one-to-many relationship:

```php
<?php
$users = $group->getUsers();
$nbUsers = $group->countUsers();
$groups = $user->getGroups();
$nbGroups = $user->countGroups();
```

Just like regular related object getters, these generated methods accept an optional query object, to further filter the results.

To facilitate queries, Propel also adds new methods to the `UserQuery` and `GroupQuery` classes:

```php
<?php
$users = UserQuery::create()
  ->filterByGroup($group)
  ->find();
$groups = GroupQuery::create()
  ->filterByUser($user)
  ->find();
```

## One-to-One Relationships ##

Propel supports the special case of one-to-one relationships. These relationships are defined when the primary key is also a foreign key. For example:

```xml
<table name="bookstore_employee" description="Employees of a bookstore">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="name" type="VARCHAR" size="32"/>
</table>

<table name="bookstore_employee_account" description="Bookstore employees' login credentials">
  <column name="employee_id" type="INTEGER" primaryKey="true"/>
  <column name="login" type="VARCHAR" size="32"/>
  <column name="password" type="VARCHAR" size="100"/>
  <foreign-key foreignTable="bookstore_employee">
    <reference local="employee_id" foreign="id"/>
  </foreign-key>
</table>
```

Because the primary key of the `bookstore_employee_account` is also a foreign key to the `bookstore_employee` table, Propel interprets this as a one-to-one relationship and will generate singular methods for both sides of the relationship (`BookstoreEmployee::getBookstoreEmployeeAccount()`, and `BookstoreEmployeeAccount::getBookstoreEmployee()`).

## On-Update and On-Delete Triggers #

Propel also supports the _ON UPDATE_ and _ON DELETE_ aspect of foreign keys. These properties can be specified in the `<foreign-key>` tag using the `onUpdate` and `onDelete` attributes. Propel supports values of `CASCADE`, `SETNULL`, and `RESTRICT` for these attributes. For databases that have native foreign key support, these trigger events will be specified at the database level when the foreign keys are created. For databases that do not support foreign keys, this functionality will be emulated by Propel.

```xml
<table name="review">
  <column name="review_id" type="INTEGER" primaryKey="true" required="true"/>
  <column name="reviewer" type="VARCHAR" size="50" required="true"/>
  <column name="book_id" required="true" type="INTEGER"/>
  <foreign-key foreignTable="book" onDelete="CASCADE">
    <reference local="book_id" foreign="id"/>
  </foreign-key>
</table>
```

In the example above, the `review` rows will be automatically removed if the related `book` row is deleted.

## Minimizing Queries ##

Even if you use a foreign query, Propel will issue new queries when you fetch related objects:

```php
<?php
$book = BookQuery::create()
  ->useAuthorQuery()
    ->filterByFirstName('Leo')
  ->endUse()
  ->findOne();
$author = $book->getAuthor();  // Needs another database query
```

Propel allows you to retrieve the main object together with related objects in a single query. You just use the `with()` method to specify which objects the main object should be hydrated with.

```php
<?php
$book = BookQuery::create()
  ->useAuthorQuery()
    ->filterByFirstName('Leo')
  ->endUse()
  ->with('Author')
  ->findOne();
$author = $book->getAuthor();  // Same result, with no supplementary query
```

Since the call to `with()` adds the columns of the related object to the SELECT part of the query, and uses these columns to populate the related object, that means that a query using `with()` is slower and consumes more memory. So use it only when you actually need the related objects afterwards.

If you don't want to add a filter on a related object but still need to hydrate it, calling `useXXXQuery()`, `endUse()`, and then `with()` can be a little cumbersome. For this case, Propel provides a proxy method called `joinWith()`. It expects a string made of the initial query name and the foreign query name. For instance:

```php
<?php
$book = BookQuery::create()
  ->joinWith('Book.Author')
  ->findOne();
$author = $book->getAuthor();  // Same result, with no supplementary query
```

`with()` and `joinWith()` are not limited to immediate relationships. As a matter of fact, just like you can nest `use()` calls, you can call `with()` several times to populate a chain of objects:

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

So `with()` is very useful to minimize the number of database queries. As soon as you see that the number of queries necessary to perform an action is proportional to the number of results, adding a `with()` call is the trick to get down to a more reasonable query count.

>**Tip**<br />`with()` also works for left joins on one-to-many relationships, but you mustn't use a `limit()` in the query in this case. This is because Propel has no way to determine the actual number of rows of the main object in such a case.

```php
<?php
// this works
$authors = AuthorQuery::create()
  ->leftJoinWith('Author.Book')
  ->find();
// this does not work
$authors = AuthorQuery::create()
  ->leftJoinWith('Author.Book')
  ->limit(5)
  ->find();
```

However, it is quite easy to achieve hydration of related objects with only one additional query:

```php
<?php
$authors = AuthorQuery::create()
  ->limit(5)
  ->find();
// $authors is a PropelObjectCollection
$authors->populateRelation('Book');
foreach ($authors as $author) {
  // now you can iterate over each author's book without further queries
  foreach ($author->getBooks() as $book) {  // no database query, the author already has a Books collection
    // do stuff with $book and $author
  }
}
```

## Model-Only Relationships ##

Propel models can share relationships even though the underlying tables aren't linked by a foreign key. This ability may be of great use when using Propel on top of a legacy database.

For example, a `review` table designed for a MyISAM database engine is linked to a `book` table by a simple `book_id` column:

```xml
<table name="review">
  <column name="review_id" type="INTEGER" primaryKey="true" required="true"/>
  <column name="reviewer" type="VARCHAR" size="50" required="true"/>
  <column name="book_id" required="true" type="INTEGER"/>
</table>
```

To enable a model-only relationship, add a `<foreign-key>` tag using the `skipSql` attribute, as follows:

```xml
<table name="review">
  <column name="review_id" type="INTEGER" primaryKey="true" required="true"/>
  <column name="reviewer" type="VARCHAR" size="50" required="true"/>
  <column name="book_id" required="true" type="INTEGER"/>
  <!-- Model-only relationship -->
  <foreign-key foreignTable="book" onDelete="CASCADE" skipSql="true">
    <reference local="book_id" foreign="id"/>
  </foreign-key>
</table>
```

Such a foreign key is not translated into SQL when Propel builds the table creation or table migration code. It can be seen as a "virtual foreign key". However, on the PHP side, the `Book` model actually has a one-to-many relationship with the `Review` model. The generated `ActiveRecord` and `ActiveQuery` classes take advantage of this relationship to offer smart getters and filters.
