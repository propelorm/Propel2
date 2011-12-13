<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorPeer;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookPeer;
use Propel\Tests\Bookstore\ContestView;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\ObjectFormatter;
use Propel\Runtime\Query\ModelCriteria;

/**
 * Test class for ObjectCollection.
 *
 * @author     Francois Zaninotto
 * @version    $Id: ObjectCollectionTest.php 1348 2009-12-03 21:49:00Z francois $
 */
class ObjectCollectionTest extends BookstoreTestBase
{

    public function testContains()
    {
        $col = new ObjectCollection();
        $book1 = new Book();
        $book1->setTitle('Foo');
        $book2 = new Book();
        $book2->setTitle('Bar');
        $col = new ObjectCollection();
        $this->assertFalse($col->contains($book1));
        $this->assertFalse($col->contains($book2));
        $col []= $book1;
        $this->assertTrue($col->contains($book1));
        $this->assertFalse($col->contains($book2));
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testSaveOnReadOnlyEntityThrowsException()
    {
        $col = new ObjectCollection();
        $col->setModel('\Propel\Tests\Bookstore\ContestView');
        $cv = new ContestView();
        $col []= $cv;
        $col->save();
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testDeleteOnReadOnlyEntityThrowsException()
    {
        $col = new ObjectCollection();
        $col->setModel('\Propel\Tests\Bookstore\ContestView');
        $cv = new ContestView();
        $cv->setNew(false);
        $col []= $cv;
        $col->delete();
    }

    public function testGetPrimaryKeys()
    {
        $books = new ObjectCollection();
        $books->setModel('\Propel\Tests\Bookstore\Book');
        for ($i=0; $i < 4; $i++) {
            $book = new Book();
            $book->setTitle('Title' . $i);
            $book->save($this->con);
            $books []= $book;
        }

        $pks = $books->getPrimaryKeys();
        $this->assertEquals(4, count($pks));

        $keys = array(
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3'
        );
        $this->assertEquals($keys, array_keys($pks));

        $pks = $books->getPrimaryKeys(false);
        $keys = array(0, 1, 2, 3);
        $this->assertEquals($keys, array_keys($pks));

        foreach ($pks as $key => $value) {
            $this->assertEquals($books[$key]->getPrimaryKey(), $value);
        }
    }

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

        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');
        $coll[]= $book;
        $expected = array(array(
            'Id' => 9012,
            'Title' => 'Don Juan',
            'ISBN' => '0140422161',
            'Price' => 12.99,
            'PublisherId' => null,
            'AuthorId' => 5678,
            'Author' => array(
                'Id' => 5678,
                'FirstName' => 'George',
                'LastName' => 'Byron',
                'Email' => null,
                'Age' => null,
                'Books' => array(
                    'Book_0' => '*RECURSION*',
                )
            ),
        ));
        $this->assertEquals($expected, $coll->toArray());
    }

    public function testPopulateRelationOneToManyWithEmptyCollection()
    {
        $author = new Author();
        $author->setLastName('I who never wrote');
        $author->save($this->con);
        AuthorPeer::clearInstancePool();
        BookPeer::clearInstancePool();
        $coll = new ObjectCollection();
        $coll->setFormatter(new ObjectFormatter(new ModelCriteria(null, 'Propel\Tests\Bookstore\Author')));
        $coll []= $author;
        $books = $coll->populateRelation('Book', null, $this->con);
        $this->assertEquals(0, $books->count());
        $count = $this->con->getQueryCount();
        $this->assertEquals(0, $author->countBooks());
        $this->assertEquals($count, $this->con->getQueryCount());
    }
}
