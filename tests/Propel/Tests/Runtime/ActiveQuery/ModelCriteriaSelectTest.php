<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Test class for ModelCriteria select() method.
 *
 * @author Francois Zaninotto
 * @version    $Id: ModelCriteriaTest.php 1842 2010-07-22 22:39:40Z KRavEN $
 */
class ModelCriteriaSelectTest extends BookstoreTestBase
{
    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSelectThrowsExceptionWhenCalledWithAnEmptyString()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSelectThrowsExceptionWhenCalledWithAnEmptyArray()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select(array());
    }

    public function testSelectStringNoResult()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'kdjfhlkdsh');
        $c->select('Title');
        $titles = $c->find($this->con);

        $expectedSQL = $this->getSql('SELECT book.TITLE AS "Title" FROM `book` WHERE book.TITLE = \'kdjfhlkdsh\'');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(string) selects a single column');
        $this->assertInstanceOf('Propel\Runtime\Collection\ArrayCollection', $titles, 'find() called after select(string) returns a PropelArrayCollection object');
        $this->assertTrue(is_array($titles->getData()), 'find() called after select(string) returns an empty ArrayCollection object');
        $this->assertEquals(0, count($titles), 'find() called after select(string) returns an empty array if no record is found');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'kdjfhlkdsh');
        $c->select('Title');
        $title = $c->findOne();
        $this->assertTrue(is_null($title), 'findOne() called after select(string) returns null when no record is found');
    }

    public function testSelectStringAcceptsColumnNames()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Title');
        $titles = $c->find();
        $expectedSQL = $this->getSql('SELECT book.TITLE AS "Title" FROM `book`');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select() accepts short column names');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Propel\Tests\Bookstore\Book.Title');
        $titles = $c->find();
        $expectedSQL = $this->getSql('SELECT book.TITLE AS "Propel\Tests\Bookstore\Book.Title" FROM `book`');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select() accepts complete column names');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->select('b.Title');
        $titles = $c->find();
        $expectedSQL = $this->getSql('SELECT book.TITLE AS "b.Title" FROM `book`');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select() accepts complete column names with table aliases');
    }

    public function testSelectStringFind()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Title');
        $titles = $c->find($this->con);
        $this->assertEquals($titles->count(), 4, 'find() called after select(string) returns an array with one row for each record');
        $this->assertEquals($titles->shift(), 'Harry Potter and the Order of the Phoenix', 'find() called after select(string) returns an array of column values');
        $this->assertEquals($titles->shift(), 'Quicksilver', 'find() called after select(string) returns an array of column values');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->where('Propel\Tests\Bookstore\Author.FirstName = ?', 'Neal');
        $c->select('FirstName');
        $authors = $c->find($this->con);
        $this->assertEquals($authors->count(), 1, 'find() called after select(string) allows for where() statements');
        $expectedSQL = $this->getSql("SELECT author.FIRST_NAME AS \"FirstName\" FROM `author` WHERE author.FIRST_NAME = 'Neal'");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(string) allows for where() statements');
    }

    public function testSelectStringFindOne()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Title');
        $title = $c->findOne($this->con);
        $expectedSQL = $this->getSql('SELECT book.TITLE AS "Title" FROM `book` LIMIT 1');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'findOne() called after select(string) selects a single column and requests a single row');
        $this->assertTrue(is_string($title),'findOne() called after select(string) returns a string');
        $this->assertEquals($title, 'Harry Potter and the Order of the Phoenix', 'findOne() called after select(string) returns the column value of the first row matching the query');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->where('Propel\Tests\Bookstore\Author.FirstName = ?', 'Neal');
        $c->select('FirstName');
        $author = $c->findOne($this->con);
        $this->assertEquals(count($author), 1, 'findOne() called after select(string) allows for where() statements');
        $expectedSQL = $this->getSql("SELECT author.FIRST_NAME AS \"FirstName\" FROM `author` WHERE author.FIRST_NAME = 'Neal' LIMIT 1");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'findOne() called after select(string) allows for where() statements');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->select(AuthorTableMap::FIRST_NAME);
        $author = $c->find($this->con);
        $expectedSQL = $this->getSql("SELECT author.FIRST_NAME AS \"author.FIRST_NAME\" FROM `author`");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select(string) accepts model TableMap Constants');
    }

    /**
     * As $failSilently is `true` by default, it doesn't throw any exception, just returns null.
     * So, we check the query fails here.
     *
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSelectStringFindCalledWithNonExistingColumn()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->select('author.NOT_EXISTING_COLUMN');
        $author = $c->find($this->con);
    }

    public function testSelectStringJoin()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select('Title');
        $titles = $c->find($this->con);
        $this->assertEquals($titles->count(), 1, 'find() called after select(string) allows for join() statements');
        $expectedSQL = $this->getSql("SELECT book.TITLE AS \"Title\" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal'");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(string) allows for join() statements');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select('Author.FirstName');
        $titles = $c->find($this->con);
        $this->assertEquals($titles->shift(), 'Neal', 'find() called after select(string) will return values from the joined table using complete column names');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select('Title');
        $title = $c->findOne($this->con);
        $this->assertEquals(count($title), 1, 'findOne() called after select(string) allows for join() statements');
        $expectedSQL = $this->getSql("SELECT book.TITLE AS \"Title\" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal' LIMIT 1");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'findOne() called after select(string) allows for where() statements');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select('Author.FirstName');
        $title = $c->findOne($this->con);
        $this->assertEquals($title, 'Neal', 'findOne() called after select(string) will return values from the joined table using complete column names');
    }

    public function testSelectStringWildcard()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('*');
        $book = $c->findOne($this->con);
        $expectedSQL = $this->getSql('SELECT book.ID AS "Propel\Tests\Bookstore\Book.Id", book.TITLE AS "Propel\Tests\Bookstore\Book.Title", book.ISBN AS "Propel\Tests\Bookstore\Book.ISBN", book.PRICE AS "Propel\Tests\Bookstore\Book.Price", book.PUBLISHER_ID AS "Propel\Tests\Bookstore\Book.PublisherId", book.AUTHOR_ID AS "Propel\Tests\Bookstore\Book.AuthorId" FROM `book` LIMIT 1');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select(\'*\') selects all the columns from the main object');
        $this->assertTrue(is_array($book), 'findOne() called after select(\'*\') returns an array');
        $this->assertEquals(array('Propel\Tests\Bookstore\Book.Id', 'Propel\Tests\Bookstore\Book.Title', 'Propel\Tests\Bookstore\Book.ISBN', 'Propel\Tests\Bookstore\Book.Price', 'Propel\Tests\Bookstore\Book.PublisherId', 'Propel\Tests\Bookstore\Book.AuthorId'), array_keys($book), 'select(\'*\') returns all the columns from the main object, in complete form');
    }

    public function testSelectArrayFind()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        // fix for a bug/limitation in pdo_dblib where it truncates columnnames to a maximum of 31 characters when doing PDO::FETCH_ASSOC
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $c->select(array('Propel\Tests\Bookstore\BookstoreEmployeeAccount.Authenticator', 'Propel\Tests\Bookstore\BookstoreEmployeeAccount.Password'));
        $account = $c->findOne($this->con);
        $this->assertEquals($account, array('Propel\Tests\Bookstore\BookstoreEmployeeAccount.Authenticator' => 'Password', 'Propel\Tests\Bookstore\BookstoreEmployeeAccount.Password' => 'johnp4ss'), 'select() does not mind long column names');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->where('Propel\Tests\Bookstore\Author.FirstName = ?', 'Neal');
        $c->select(array('FirstName', 'LastName'));
        $authors = $c->find($this->con);
        $this->assertEquals($authors->count(), 1, 'find() called after select(array) allows for where() statements');
        $expectedSQL = $this->getSql("SELECT author.FIRST_NAME AS \"FirstName\", author.LAST_NAME AS \"LastName\" FROM `author` WHERE author.FIRST_NAME = 'Neal'");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(array) allows for where() statements');
    }

    public function testSelectArrayFindOne()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->where('Propel\Tests\Bookstore\Author.FirstName = ?', 'Neal');
        $c->select(array('FirstName', 'LastName'));
        $author = $c->findOne($this->con);
        $this->assertEquals(count($author), 2, 'findOne() called after select(array) allows for where() statements');
        $expectedSQL = $this->getSql("SELECT author.FIRST_NAME AS \"FirstName\", author.LAST_NAME AS \"LastName\" FROM `author` WHERE author.FIRST_NAME = 'Neal' LIMIT 1");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'findOne() called after select(array) allows for where() statements');
    }

    public function testSelectArrayJoin()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select(array('Title', 'ISBN'));
        $titles = $c->find($this->con);
        $this->assertEquals($titles->count(), 1, 'find() called after select(array) allows for join() statements');
        $expectedSQL = $this->getSql("SELECT book.TITLE AS \"Title\", book.ISBN AS \"ISBN\" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal'");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(array) allows for join() statements');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select(array('Author.FirstName', 'Author.LastName'));
        $titles = $c->find($this->con);
        $this->assertEquals(array_values($titles->shift()), array('Neal', 'Stephenson'), 'find() called after select(array) will return values from the joined table using complete column names');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select(array('Title', 'ISBN'));
        $title = $c->findOne($this->con);
        $this->assertEquals(count($title), 2, 'findOne() called after select(array) allows for join() statements');
        $expectedSQL = $this->getSql("SELECT book.TITLE AS \"Title\", book.ISBN AS \"ISBN\" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal' LIMIT 1");
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'findOne() called after select(array) allows for join() statements');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->where('Author.FirstName = ?', 'Neal');
        $c->select(array('Author.FirstName', 'Author.LastName'));
        $title = $c->findOne($this->con);
        $this->assertEquals(array_values($title), array('Neal', 'Stephenson'), 'findOne() called after select(array) will return values from the joined table using complete column names');
    }

    public function testSelectArrayRelation()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->select(array('Propel\Tests\Bookstore\Author.LastName', 'Propel\Tests\Bookstore\Book.Title'));
        $rows = $c->find($this->con);
        $expectedSQL = $this->getSql('SELECT author.LAST_NAME AS "Propel\Tests\Bookstore\Author.LastName", book.TITLE AS "Propel\Tests\Bookstore\Book.Title" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) ORDER BY book.TITLE ASC');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select(array) can select columns from several tables (many-to-one)');

        $expectedRows = array(
            array(
                'Propel\Tests\Bookstore\Author.LastName' => 'Byron',
                'Propel\Tests\Bookstore\Book.Title' => 'Don Juan',
            ),
            array(
                'Propel\Tests\Bookstore\Author.LastName' => 'Rowling',
                'Propel\Tests\Bookstore\Book.Title' => 'Harry Potter and the Order of the Phoenix',
            ),
            array(
                'Propel\Tests\Bookstore\Author.LastName' => 'Stephenson',
                'Propel\Tests\Bookstore\Book.Title' => 'Quicksilver',
            ),
            array(
                'Propel\Tests\Bookstore\Author.LastName' => 'Grass',
                'Propel\Tests\Bookstore\Book.Title' => 'The Tin Drum',
            ),
        );
        $this->assertEquals(serialize($rows->getData()), serialize($expectedRows), 'find() called after select(array) returns columns from several tables (many-to-one');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->select(array('Author.LastName', 'Book.Title'));
        $c->orderBy('Book.Id');
        $c->orderBy('Author.Id');
        $rows = $c->find($this->con);
        $expectedSQL = $this->getSql('SELECT author.LAST_NAME AS "Author.LastName", book.TITLE AS "Book.Title" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) ORDER BY book.ID ASC,author.ID ASC');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'select(array) can select columns from several tables (many-to-one)');

        $expectedRows = array (
            array (
                'Author.LastName' => 'Rowling',
                'Book.Title' => 'Harry Potter and the Order of the Phoenix',
            ),
            array (
                'Author.LastName' => 'Stephenson',
                'Book.Title' => 'Quicksilver',
            ),
            array (
                'Author.LastName' => 'Byron',
                'Book.Title' => 'Don Juan',
            ),
            array (
                'Author.LastName' => 'Grass',
                'Book.Title' => 'The Tin Drum',
            )
        );
        $this->assertEquals(serialize($rows->getData()), serialize($expectedRows), 'find() called after select(array) returns columns from several tables (many-to-one');
    }

    public function testSelectArrayWithColumn()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('LOWER(Propel\Tests\Bookstore\Book.Title)', 'LowercaseTitle');
        $c->select(array('LowercaseTitle', 'Propel\Tests\Bookstore\Book.Title'));
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $rows = $c->find($this->con);
        $expectedSQL = $this->getSql('SELECT LOWER(book.TITLE) AS LowercaseTitle, book.TITLE AS "Propel\Tests\Bookstore\Book.Title" FROM `book` INNER JOIN `author` ON (book.AUTHOR_ID=author.ID) ORDER BY book.TITLE ASC');
        $this->assertEquals($expectedSQL, $this->con->getLastExecutedQuery(), 'find() called after select(array) can cope with a column added with withColumn()');

        $expectedRows = array (
            array (
                'LowercaseTitle' => 'don juan',
                'Propel\Tests\Bookstore\Book.Title' => 'Don Juan',
            ),
            array (
                'LowercaseTitle' => 'harry potter and the order of the phoenix',
                'Propel\Tests\Bookstore\Book.Title' => 'Harry Potter and the Order of the Phoenix',
            ),
            array (
                'LowercaseTitle' => 'quicksilver',
                'Propel\Tests\Bookstore\Book.Title' => 'Quicksilver',
            ),
            array (
                'LowercaseTitle' => 'the tin drum',
                'Propel\Tests\Bookstore\Book.Title' => 'The Tin Drum',
            ),
        );
        $this->assertEquals(serialize($rows->getData()), serialize($expectedRows), 'find() called after select(array) can cope with a column added with withColumn()');
    }

    public function testSelectArrayPaginate()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $pager = BookQuery::create()
            ->select(array('Id', 'Title', 'ISBN', 'Price'))
            ->paginate(1, 10, $this->con);

        $this->assertInstanceOf('Propel\Runtime\Util\PropelModelPager', $pager);
        foreach ($pager as $result) {
            $this->assertEquals(array('Id', 'Title', 'ISBN', 'Price'), array_keys($result));
        }
    }

    public function testGetSelectReturnsNullByDefault()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertNull($c->getSelect());
    }

    public function testGetSelectReturnsStringWhenSelectingASingleColumn()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Title');
        $this->assertEquals('Title', $c->getSelect());
    }

    public function testGetSelectReturnsArrayWhenSelectingSeveralColumns()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select(array('Id', 'Title'));
        $this->assertEquals(array('Id', 'Title'), $c->getSelect());
    }

    public function testGetSelectReturnsArrayWhenSelectingASingleColumnAsArray()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select(array('Title'));
        $this->assertEquals(array('Title'), $c->getSelect());
    }

    public function testGetSelectReturnsArrayWhenSelectingAllColumns()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('*');
        $this->assertEquals(array(
            'Propel\Tests\Bookstore\Book.Id',
            'Propel\Tests\Bookstore\Book.Title',
            'Propel\Tests\Bookstore\Book.ISBN',
            'Propel\Tests\Bookstore\Book.Price',
            'Propel\Tests\Bookstore\Book.PublisherId',
            'Propel\Tests\Bookstore\Book.AuthorId'
        ), $c->getSelect());
    }

    public function testFormatterWithSelect()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->keepQuery(false); // just for this test's purpose
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->select(array('Id', 'Title'));
        $rows = $c->find($this->con);

        $this->assertTrue($c->getFormatter() instanceof \Propel\Runtime\Formatter\OnDemandFormatter, 'The formatter is preserved');
    }
}
