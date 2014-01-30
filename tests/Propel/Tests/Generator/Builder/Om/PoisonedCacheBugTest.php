<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class PoisonedCacheBugTest extends BookstoreTestBase
{
    /**
     * @var Author
     */
    private $author;

    /**
     * @var Book[]
     */
    private $books;

    public function setUp()
    {
        parent::setUp();

        $a = new Author();
        $a->setFirstName("Douglas");
        $a->setLastName("Adams");

        $b1 = new Book();
        $b1->setTitle("The Hitchhikers Guide To The Galaxy");
        $b1->setISBN('01234');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle("The Restaurant At The End Of The Universe");
        $b2->setISBN('5678');
        $a->addBook($b2);

        $a->save();

        $this->author = $a;
        $this->books = array($b1, $b2);

        Propel::enableInstancePooling();

        // Clear author instance pool so the object would be fetched from the database
        AuthorTableMap::clearInstancePool();
    }

    public function testSetUp()
    {
        $this->assertTrue(Propel::isInstancePoolingEnabled());

        $this->assertEquals(2, count($this->author->getBooks()));
        $this->assertEquals(2, $this->author->countBooks());
    }

    /**
     * Very common use case where fetching a book, and showing other books by the author
     */
    public function testPoisonedCacheWhenDoSelectJoinAuthor()
    {
        $c = new Criteria();
        $c->add(BookTableMap::COL_ID, $this->books[0]->getId());

        $books = BookQuery::create(null, $c)->joinWithAuthor()->find();

        $this->assertEquals(2, count($books[0]->getAuthor()->getBooks()));
        $this->assertEquals(2, $books[0]->getAuthor()->countBooks());
    }

    /**
     * To illustrate that instance pooling makes no difference
     */
    public function testPoisonedCacheWithJoinInstancePoolingDisabled()
    {
        Propel::disableInstancePooling();

        $this->testPoisonedCacheWhenDoSelectJoinAuthor();
    }

    public function testPoisonedCacheWhenSavingABook()
    {
        $b1 = BookQuery::create()->findPk($this->books[0]->getId());

        // if author is loaded then doSave will do addBook($b1) and poison the authors books cache
        $b1->getAuthor();

        // e.g. to update viewed count etc
        $b1->save();

        // ... later down the line fetch the author
        $author = AuthorQuery::create()->findPk($this->author->getId());

        $this->assertEquals(2, count($author->getBooks()));
        $this->assertEquals(2, $author->countBooks());
    }

    public function testAddingABook()
    {
        $author = AuthorQuery::create()->findPk($this->author->getId());

        $c1 = new Book();
        $c1->setTitle("ORM 101");
        $author->addBook($c1);

        $this->assertEquals(3, count($author->getBooks()));
        $this->assertEquals(3, $author->countBooks());
    }

    public function testModifiedObjectsRemainInTheCollection()
    {
        $c = new Criteria();
        $c->add(BookTableMap::COL_ID, $this->books[0]->getId());

        $books = BookQuery::create(null, $c)->joinWithAuthor()->find();
        $books[0]->setTitle('Modified');

        $books2 = $books[0]->getAuthor()->getBooks();
        $this->assertEquals(2, count($books2));
        $this->assertEquals(2, $books2[0]->getAuthor()->countBooks());

        $this->assertEquals('Modified', $books2[0]->getTitle());
    }
}
