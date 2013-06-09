---
layout: documentation
title: Transactions
---

# Transactions #

Database transactions are the key to assure the data integrity and the performance of database queries. Propel uses transactions internally, and provides a simple API to use them in your own code.

>**Tip**<br />If the [ACID](http://en.wikipedia.org/wiki/ACID) acronym doesn't ring a bell, you should probably learn some [fundamentals about database transactions](http://en.wikipedia.org/wiki/Database_transaction) before reading further.

## Wrapping Queries Inside a Transaction ##

Propel uses PDO as database abstraction layer, and therefore uses [PDO's built-in support for database transactions](http://www.php.net/manual/en/pdo.transactions.php). The syntax is the same, as you can see in the classical "money transfer" example:

```php
<?php
use Propel\Runtime\Propel;
// ...
public function transferMoney($fromAccountNumber, $toAccountNumber, $amount)
{
  // get the PDO connection object from Propel
  $con = Propel::getWriteConnection(AccountTableMap::DATABASE_NAME);

  $fromAccount = AccountQuery::create()->findPk($fromAccountNumber, $con);
  $toAccount   = AccountQuery::create()->findPk($toAccountNumber, $con);

  $con->beginTransaction();

  try {
    // remove the amount from $fromAccount
    $fromAccount->setValue($fromAccount->getValue() - $amount);
    $fromAccount->save($con);
    // add the amount to $toAccount
    $toAccount->setValue($toAccount->getValue() + $amount);
    $toAccount->save($con);

    $con->commit();
  } catch (Exception $e) {
    $con->rollback();
    throw $e;
  }
}
```

The transaction statements are `beginTransaction()`, `commit()` and `rollback()`, which are methods of the PDO connection object. Transaction methods are typically used inside a `try/catch` block. The exception is rethrown after rolling back the transaction: That ensures that the user knows that something wrong happened.

In this example, if something wrong happens while saving either one of the two accounts, an `Exception` is thrown, and the whole operation is rolled back. That means that the transfer is cancelled, with an insurance that the money hasn't vanished (that's the A in ACID, which stands for "Atomicity"). If both account modifications work as expected, the whole transaction is committed, meaning that the data changes enclosed in the transaction are persisted in the database.

>**Tip**<br/>: In order to build a transaction, you need a connection object. The connection object for a Propel model is always available through `Propel::getReadConnection([ModelName]TableMap::DATABASE_NAME)` (for READ queries) and `Propel::getWriteConnection([ModelName]TableMap::DATABASE_NAME)` (for WRITE queries).

## Denormalization And Transactions ##

Another example of the use of transactions is for [denormalized schemas](http://en.wikipedia.org/wiki/Denormalization).

For instance, suppose that you have an `Author` model with a one to many relationship to a `Book` model. every time you need to display the number of books written by an author, you call `countBooks()` on the author object, which issues a new query to the database:

```php
<ul>
<?php foreach ($authors as $author): ?>
  <li><?php echo $author->getName() ?> (<?php echo $author->countBooks() ?> books)</li>
<?php endforeach; ?>
</ul>
```

If you have a large number of authors and books, this simple code snippet can be a real performance blow to your application. The usual way to optimize it is to _denormalize_ your schema by storing the number of books by each author in a new `nb_books` column, in the `author` table.

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" />
  <column name="nb_books" type="INTEGER" default="0" />
</table>
```

You must update this new column every time you save or delete a `Book` object; this will make write queries a little slower, but read queries much faster. Fortunately, Propel model objects support pre- and post- hooks for the `save()` and `delete()` methods, so this is quite easy to implement:

```php
<?php
class Book extends BaseBook
{
  public function postSave(ConnectionInterface $con)
  {
    $this->updateNbBooks($con);
  }

  public function postDelete(ConnectionInterface $con)
  {
    $this->updateNbBooks($con);
  }

  public function updateNbBooks(ConnectionInterface $con)
  {
    $author = $this->getAuthor();
    $nbBooks = $author->countBooks($con);
    $author->setNbBooks($nbBooks);
    $author->save($con);
  }
}
```

The `BaseBook::save()` method wraps the actual database INSERT/UPDATE query inside a transaction, together with any other query registered in a pre- or post- save hook. That means that when you save a book, the `postSave()` code is executed in the same transaction as the actual `$book->save()` method. Everything happens as is the code was the following:

```php
<?php
class Book extends BaseBook
{
  public function save(ConnectionInterface $con)
  {
    $con->beginTransaction();

    try {
      // insert/update query for the current object
      $this->doSave($con);

      // postSave hook
      $author = $this->getAuthor();
      $nbBooks = $author->countBooks($con);
      $author->setNbBooks($nbBooks);
      $author->save($con);

      $con->commit();
    } catch (Exception $e) {
      $con->rollback();
      throw $e;
    }
  }
}
```

In this example, the `nb_books` column of the `author` table will always we synchronized with the number of books. If anything happens during the transaction, the saving of the book is rolled back, as well as the `nb_books` column update. The transaction serves to preserve data consistency in a denormalized schema ("Consistency" stands for the C in ACID).

>**Tip**<br />Check the [behaviors documentation](07-behaviors.html#pre-and-post-hooks-for-save-and-delete-methods) for details about the pre- and post- hooks in Propel model objects.

## Nested Transactions ##

Some RDBMS offer the ability to nest transactions, to allow partial rollback of a set of transactions. PDO does not provide this ability at the PHP level; nevertheless, Propel emulates nested transactions for all supported database engines:

```php
<?php
function deleteBooksWithNoPrice(ConnectionInterface $con)
{
  $con->beginTransaction();
  try {
    $c = new Criteria();
    $c->add(BookTableMap::PRICE, null, Criteria::ISNULL);
    BookTableMap::doDelete($c, $con);
    $con->commit();
  } catch (Exception $e) {
    $con->rollback();
    throw $e;
  }
}

function deleteAuthorsWithNoEmail(ConnectionInterface $con)
{
  $con->beginTransaction();
  try {
    $c = new Criteria();
    $c->add(AuthorTableMap::EMAIL, null, Criteria::ISNULL);
    AuthorTableMap::doDelete($c, $con);
    $con->commit();
  } catch (Exception $e) {
    $con->rollback();
    throw $e;
  }
}

function cleanup(ConnectionInterface $con)
{
  $con->beginTransaction();
  try {
    deleteBooksWithNoPrice($con);
    deleteAuthorsWithNoEmail($con);
    $con->commit();
  } catch (Exception $e) {
     $con->rollback();
     throw $e;
  }
}
```

All three functions alter data in a transaction, ensuring data integrity for each. In addition, the `cleanup()` function actually executes two nested transactions inside one main transaction.

Propel deals with this case by seeing only the outermost transaction, and ignoring the `beginTransaction()`, `commit()` and `rollback()` statements of nested transactions. If nothing wrong happens, then the last `commit()` call (after both `deleteBooksWithNoPrice()` and `deleteAuthorsWithNoEmail()` end) triggers the actual database commit. However, if an exception is thrown in either one of these nested transactions, it is escalated to the main `catch` statement in `cleanup()` so that the entire transaction (starting at the main `beginTransaction()`) is rolled back.

So you can use transactions everywhere it's necessary in your code, without worrying about nesting them. Propel will always commit or rollback everything altogether, whether the RDBMS supports nested transactions or not.

>**Tip**<br />This allows you to wrap all your application code inside one big transaction for a better integrity.

## Using Transactions To Boost Performance ##

A database transaction has a cost in terms of performance. In fact, for simple data manipulation, the cost of the transaction is more important than the cost of the query itself. Take the following example:

```php
<?php
$con = Propel::getConnection(BookTableMap::DATABASE_NAME);
for ($i=0; $i<2002; $i++)
{
  $book = new Book();
  $book->setTitle($i . ': A Space Odyssey');
  $book->save($con);
}
```

As explained earlier, Propel wraps every save operation inside a transaction. In terms of execution time, this is very expensive. Here is how the above code would translate to MySQL in an InnodDB table:

```sql
BEGIN;
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'0: A Space Odyssey');
COMMIT;
BEGIN;
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'1: A Space Odyssey');
COMMIT;
BEGIN;
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'2: A Space Odyssey');
COMMIT;
...
```

You can take advantage of Propel's nested transaction capabilities to encapsulate the whole loop inside one single transaction. This will reduce the execution time drastically:

```php
<?php
$con = Propel::getConnection(BookTableMap::DATABASE_NAME);
$con->beginTransaction();
for ($i=0; $i<2002; $i++)
{
  $book = new Book();
  $book->setTitle($i . ': A Space Odyssey');
  $book->save($con);
}
$con->commit();
```

The transactions inside each `save()` will become nested, and therefore not translated into actual database transactions. Only the outmost transaction will become a database transaction. So this will translate to MySQL as:

```sql
BEGIN;
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'0: A Space Odyssey');
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'1: A Space Odyssey');
INSERT INTO book (`ID`,`TITLE`) VALUES (NULL,'2: A Space Odyssey');
...
COMMIT;
```

In practice, encapsulating a large amount of simple queries inside a single transaction significantly improves performance.

>**Tip**<br/>: Until the final `commit()` is called, most database engines lock updated rows, or even tables, to prevent any query outside the transaction from seeing the partially committed data (this is how transactions preserve "Isolation", which is the I in ACID). That means that large transactions will queue every other queries for potentially a long time. Consequently, use large transactions only when concurrency is not a requirement.

## Why Is The Connection Always Passed As Parameter? ##

All the code examples in this chapter show the connection object passed as a parameter to Propel methods that trigger a database query:

```php
<?php
$con = Propel::getConnection(AccountTableMap::DATABASE_NAME);
$fromAccount = AccountQuery::create()->findPk($fromAccountNumber, $con);
$fromAccount->setValue($fromAccount->getValue() - $amount);
$fromAccount->save($con);
```

The same code works without explicitly passing the connection object, because Propel knows how to get the right connection from a Model:

```php
<?php
$fromAccount = AccountQuery::create()->findPk($fromAccountNumber);
$fromAccount->setValue($fromAccount->getValue() - $amount);
$fromAccount->save();
```

However, it's a good practice to pass the connection explicitly, and for three reasons:

* Propel doesn't need to look for a connection object, and this results in a tiny boost in performance.
* You can use a specific connection, which is required in distributed (master/slave) environments, in order to distinguish read and write operations.
* Most importantly, transactions are tied to a single connection. You can't enclose two queries using different connections in a single transaction. So it's very useful to identify the connection you want to use for every query, as Propel will throw an exception if you use the wrong connection.

## Limitations ##

* Currently there is no support for row locking (e.g. `SELECT blah FOR UPDATE`).
* You must rethrow the exception caught in the `catch` statement of nested transactions, otherwise there is a risk that the global rollback doesn't occur.
* True nested transactions, with partial rollback, are only possible in MSSQL, and can be emulated in other RDBMS through savepoints. This feature may be added to Propel in the future, but for the moment, only the outermost PHP transaction triggers a database transaction.
* If you rollback a partially executed transaction and ignore the exception thrown, there are good chances that some of your objects are out of sync with the database. The good practice is to always let a transaction exception escalate until it stops the script execution.
