<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Collection;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ArrayCollection;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Country;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for ObjectCollection.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ArrayCollectionTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
    }

    /**
     * @return void
     */
    public function testSave()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ARRAY)->find();
        foreach ($books as $k => $book) {
            $books[$k]['Title'] = 'foo';
        }
        $books->save();
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($books as $book) {
            $this->assertEquals('foo', $book->getTitle('foo'));
        }
    }

    /**
     * @return void
     */
    public function testSaveOnReadOnlyEntityThrowsException()
    {
        $this->expectException(BadMethodCallException::class);

        $col = new ArrayCollection();
        $col->setModel('Country');
        $cv = new Country();
        $col[] = $cv;
        $col->save();
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ARRAY)->find();
        $books->delete();
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $this->assertEquals(0, count($books));
    }

    /**
     * @return void
     */
    public function testDeleteOnReadOnlyEntityThrowsException()
    {
        $this->expectException(BadMethodCallException::class);

        $col = new ArrayCollection();
        $col->setModel('Country');
        $cv = new Country();
        $cv->setNew(false);
        $col[] = $cv;
        $col->delete();
    }

    /**
     * @return void
     */
    public function testGetPrimaryKeys()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ARRAY)->find();
        $pks = $books->getPrimaryKeys();
        $this->assertEquals(4, count($pks));

        $keys = [
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3',
        ];
        $this->assertEquals($keys, array_keys($pks));

        $pks = $books->getPrimaryKeys(false);
        $keys = [0, 1, 2, 3];
        $this->assertEquals($keys, array_keys($pks));

        $bookObjects = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($pks as $key => $value) {
            $this->assertEquals($bookObjects[$key]->getPrimaryKey(), $value);
        }
    }

    /**
     * @return void
     */
    public function testFromArray()
    {
        $author = new Author();
        $author->setFirstName('Jane');
        $author->setLastName('Austen');
        $author->save();
        $books = [
            ['Title' => 'Mansfield Park', 'ISBN' => 'FA404-A', 'AuthorId' => $author->getId()],
            ['Title' => 'Pride And Prejudice', 'ISBN' => 'FA404-B', 'AuthorId' => $author->getId()],
        ];
        $col = new ArrayCollection();
        $col->setModel('Propel\Tests\Bookstore\Book');
        $col->fromArray($books);
        $col->save();

        $nbBooks = PropelQuery::from('Propel\Tests\Bookstore\Book')->count();
        $this->assertEquals(6, $nbBooks);

        $booksByJane = PropelQuery::from('Propel\Tests\Bookstore\Book b')
            ->join('b.Author a')
            ->where('a.LastName = ?', 'Austen')
            ->count();
        $this->assertEquals(2, $booksByJane);
    }

    /**
     * @return void
     */
    public function testToArray()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ARRAY)->find();
        $booksArray = $books->toArray();
        $this->assertEquals(4, count($booksArray));

        $bookObjects = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($booksArray as $key => $book) {
            $this->assertEquals($bookObjects[$key]->toArray(), $book);
        }

        $booksArray = $books->toArray();
        $keys = [0, 1, 2, 3];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray(null, true);
        $keys = [
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3',
        ];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title');
        $keys = ['Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum'];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title', true);
        $keys = [
            'Book_Harry Potter and the Order of the Phoenix',
            'Book_Quicksilver',
            'Book_Don Juan',
            'Book_The Tin Drum',
        ];
        $this->assertEquals($keys, array_keys($booksArray));
    }

    /**
     * @return void
     */
    public function testToArrayDeep()
    {
        $author = new Author();
        $author->setId(5678);
        $author->setFirstName('George');
        $author->setLastName('Byron');
        $book = new Book();
        $book->setId(9012);
        $book->setTitle('Don Juan');
        $book->setISBN('0140422161');
        $book->setPrice(12.99);
        $book->setAuthor($author);

        $coll = new ArrayCollection();
        $coll->setModel('Propel\Tests\Bookstore\Book');
        $coll[] = $book->toArray(TableMap::TYPE_PHPNAME, true, [], true);
        $expected = [[
            'Id' => 9012,
            'Title' => 'Don Juan',
            'ISBN' => '0140422161',
            'Price' => 12.99,
            'PublisherId' => null,
            'AuthorId' => 5678,
            'Author' => [
                'Id' => 5678,
                'FirstName' => 'George',
                'LastName' => 'Byron',
                'Email' => null,
                'Age' => null,
                'Books' => [
                    0 => ['*RECURSION*'],
                ],
            ],
        ]];
        $this->assertEquals($expected, $coll->toArray());
    }

    /**
     * @return void
     */
    public function getWorkerObject()
    {
        $col = new TestableArrayCollection();
        $col->setModel('Propel\Tests\Bookstore\Book');
        $book = $col->getWorkerObject();
        $this->assertTrue($book instanceof Book, 'getWorkerObject() returns an object of the collection model');
        $book->foo = 'bar';
        $this->assertEqual('bar', $col->getWorkerObject()->foo, 'getWorkerObject() returns always the same object');
    }

    /**
     * @return void
     */
    public function testGetWorkerObjectNoModel()
    {
        $this->expectException(PropelException::class);

        $col = new TestableArrayCollection();
        $col->getWorkerObject();
    }
}

class TestableArrayCollection extends ArrayCollection
{
    public function getWorkerObject(): ActiveRecordInterface
    {
        return parent::getWorkerObject();
    }
}
