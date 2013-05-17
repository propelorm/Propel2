<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Test class for ObjectCollection.
 *
 * @author Francois Zaninotto
 */
class ObjectCollectionWithFixturesTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
    }

    public function testSave()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($books as $book) {
            $book->setTitle('foo');
        }
        $books->save();
        // check that all the books are saved
        foreach ($books as $book) {
            $this->assertFalse($book->isModified());
        }
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($books as $book) {
            $this->assertEquals('foo', $book->getTitle('foo'));
        }
    }

    public function testDelete()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $books->delete();
        // check that all the books are deleted
        foreach ($books as $book) {
            $this->assertTrue($book->isDeleted());
        }
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $this->assertEquals(0, count($books));
    }

    public function testFromArray()
    {
        $author = new Author();
        $author->setFirstName('Jane');
        $author->setLastName('Austen');
        $author->save();
        $books = array(
            array('Title' => 'Mansfield Park', 'ISBN' => 'FA404', 'AuthorId' => $author->getId()),
            array('Title' => 'Pride And Prejudice', 'ISBN' => 'FA404', 'AuthorId' => $author->getId())
        );
        $col = new ObjectCollection();
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

    public function testToArray()
    {
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $booksArray = $books->toArray();
        $this->assertEquals(4, count($booksArray));

        foreach ($booksArray as $key => $book) {
            $this->assertEquals($books[$key]->toArray(), $book);
        }

        $booksArray = $books->toArray();
        $keys = array(0, 1, 2, 3);
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray(null, true);
        $keys = array(
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3'
        );
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title');
        $keys = array('Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum');
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title', true);
        $keys = array(
            'Book_Harry Potter and the Order of the Phoenix',
            'Book_Quicksilver',
            'Book_Don Juan',
            'Book_The Tin Drum'
        );
        $this->assertEquals($keys, array_keys($booksArray));
    }

    public function testGetArrayCopy()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $booksArray = $books->getArrayCopy();
        $this->assertEquals(4, count($booksArray));

        foreach ($booksArray as $key => $book) {
            $this->assertEquals($books[$key], $book);
        }

        $booksArray = $books->getArrayCopy();
        $keys = array(0, 1, 2, 3);
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy(null, true);
        $keys = array(
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3'
        );
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy('Title');
        $keys = array('Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum');
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy('Title', true);
        $keys = array(
            'Book_Harry Potter and the Order of the Phoenix',
            'Book_Quicksilver',
            'Book_Don Juan',
            'Book_The Tin Drum'
        );
        $this->assertEquals($keys, array_keys($booksArray));
    }

    public function testToKeyValue()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getTitle()] = $book->getISBN();
        }
        $booksArray = $books->toKeyValue('Title', 'ISBN');
        $this->assertEquals(4, count($booksArray));
        $this->assertEquals($expected, $booksArray, 'toKeyValue() turns the collection to an associative array');

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getISBN()] = $book->getTitle();
        }
        $booksArray = $books->toKeyValue('ISBN');
        $this->assertEquals($expected, $booksArray, 'toKeyValue() uses __toString() for the value if no second field name is passed');

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getId()] = $book->getTitle();
        }
        $booksArray = $books->toKeyValue();
        $this->assertEquals($expected, $booksArray, 'toKeyValue() uses primary key for the key and __toString() for the value if no field name is passed');
    }

    public function testToKeyIndex()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getTitle()] = $book;
        }
        $booksArray = $books->toKeyIndex('Title');
        $this->assertEquals(4, count($booksArray));
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() turns the collection to `Title` indexed array');

        $this->assertEquals($booksArray, $books->toKeyIndex('title'));

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getISBN()] = $book;
        }
        $this->assertEquals(4, count($booksArray));
        $booksArray = $books->toKeyIndex('ISBN');
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() uses `ISBN` for the key');

        $expected = array();
        foreach ($books as $book) {
            $expected[$book->getId()] = $book;
        }
        $this->assertEquals(4, count($booksArray));
        $booksArray = $books->toKeyIndex();
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() uses primary key for the key');
    }

    public function testPopulateRelation()
    {
        AuthorTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();
        $authors = AuthorQuery::create()->find();
        $books = $authors->populateRelation('Book');
        $this->assertTrue($books instanceof ObjectCollection, 'populateRelation() returns a Collection instance');
        $this->assertEquals('Book', $books->getModel(), 'populateRelation() returns a collection of the related objects');
        $this->assertEquals('\Propel\Tests\Bookstore\Book', $books->getFullyQualifiedModel(), 'populateRelation() returns a collection of the related objects');
        $this->assertEquals(4, count($books), 'populateRelation() the list of related objects');
    }

    public function testPopulateRelationCriteria()
    {
        AuthorTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();
        $authors = AuthorQuery::create()->find();
        $c = new Criteria();
        $c->setLimit(3);
        $books = $authors->populateRelation('Book', $c);
        $this->assertEquals(3, count($books), 'populateRelation() accepts an optional criteria object to filter the query');
    }

    public function testPopulateRelationEmpty()
    {
        AuthorTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();
        $authors = AuthorQuery::create()
            ->add(null, '1<>1', Criteria::CUSTOM)
            ->find($this->con);
        $count = $this->con->getQueryCount();
        $books = $authors->populateRelation('Book', null, $this->con);
        $this->assertTrue($books instanceof ObjectCollection, 'populateRelation() returns a Collection instance');
        $this->assertEquals('Book', $books->getModel(), 'populateRelation() returns a collection of the related objects');
        $this->assertEquals('\Propel\Tests\Bookstore\Book', $books->getFullyQualifiedModel(), 'populateRelation() returns a collection of the related objects');
        $this->assertEquals(0, count($books), 'populateRelation() the list of related objects');
        $this->assertEquals($count, $this->con->getQueryCount(), 'populateRelation() doesn\'t issue a new query on empty collections');
    }

    public function testPopulateRelationOneToMany()
    {
        AuthorTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();
        $authors = AuthorQuery::create()->find($this->con);
        $count = $this->con->getQueryCount();
        $books = $authors->populateRelation('Book', null, $this->con);
        foreach ($authors as $author) {
            foreach ($author->getBooks() as $book) {
                $this->assertEquals($author, $book->getAuthor());
            }
        }
        $this->assertEquals($count + 1, $this->con->getQueryCount(), 'populateRelation() populates a one-to-many relationship with a single supplementary query');
    }

    public function testPopulateRelationManyToOne()
    {
        $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
        AuthorTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();
        $books = BookQuery::create()->find($con);
        $count = $con->getQueryCount();
        $books->populateRelation('Author', null, $con);
        foreach ($books as $book) {
            $author = $book->getAuthor();
        }
        $this->assertEquals($count + 1, $con->getQueryCount(), 'populateRelation() populates a many-to-one relationship with a single supplementary query');
    }
}
