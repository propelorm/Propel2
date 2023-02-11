<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class Ticket520Test extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testNewObjectsAvailableWhenSaveNotCalled()
    {
        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $a->addBook($b2);

        // Passing no Criteria means "use the internal collection or query the database"
        // in that case two objects are added, so it should return 2
        $books = $a->getBooks();
        $this->assertEquals(2, count($books));
    }

    /**
     * @return void
     */
    public function testNewObjectsNotAvailableWithCriteria()
    {
        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $a->addBook($b2);

        $c = new Criteria();
        $c->add(BookTableMap::COL_TITLE, '%Hitchhiker%', Criteria::LIKE);

        $guides = $a->getBooks($c);
        $this->assertEquals(0, count($guides), 'Passing a Criteria means "force a database query"');
    }

    /**
     * @return void
     */
    public function testNewObjectsAvailableAfterCriteria()
    {
        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $a->addBook($b2);

        $c = new Criteria();
        $c->add(BookTableMap::COL_TITLE, '%Hitchhiker%', Criteria::LIKE);

        $guides = $a->getBooks($c);

        $books = $a->getBooks();
        $this->assertEquals(2, count($books), 'A previous query with a Criteria does not erase the internal collection');
    }

    /**
     * @return void
     */
    public function testSavedObjectsWithCriteria()
    {
        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $b2->setISBN('FA404-2');
        $a->addBook($b2);

        $c = new Criteria();
        $c->add(BookTableMap::COL_TITLE, '%Hitchhiker%', Criteria::LIKE);

        $guides = $a->getBooks($c);

        $a->save();
        $booksAfterSave = $a->getBooks($c);
        $this->assertEquals(1, count($booksAfterSave), 'A previous query with a Criteria is not cached');
    }

    /**
     * @return void
     */
    public function testAddNewObjectAfterSave()
    {
        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $a->save();

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $a->addBook($b1);

        $books = $a->getBooks();
        $this->assertEquals(1, count($books));
        $this->assertTrue($books->contains($b1));

        /* Now this is the initial ticket 520: If we have a saved author,
        add a new book but happen to call getBooks() before we call save() again,
        the book used to be lost. */
        $a->save();
        $this->assertFalse($b1->isNew(), 'related objects are also saved after fetching them');
    }

    /**
     * @return void
     */
    public function testAddNewObjectAfterSaveWithPoisonedCache()
    {
        /* This is like testAddNewObjectAfterSave(),
        but this time we "poison" the author's $colBooks cache
        before adding the book by calling getBooks(). */

        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $a->save();
        $a->getBooks();

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $a->addBook($b1);

        $books = $a->getBooks();
        $this->assertEquals(1, count($books));
        $this->assertTrue($books->contains($b1), 'new related objects not deleted after fetching them');
    }

    /**
     * @return void
     */
    public function testCachePoisoning()
    {
        /* Like testAddNewObjectAfterSaveWithPoisonedCache, emphasizing
        cache poisoning. */

        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $a->save();

        $c = new Criteria();
        $c->add(BookTableMap::COL_TITLE, '%Restaurant%', Criteria::LIKE);

        $this->assertEquals(0, count($a->getBooks($c)));

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $a->addBook($b1);

        /* Like testAddNewObjectAfterSaveWithPoisonedCache, but this time
        with a real criteria.  */
        $this->assertEquals(0, count($a->getBooks($c)));

        $a->save();
        $this->assertFalse($b1->isNew());
        $this->assertEquals(0, count($a->getBooks($c)));
    }

    /**
     * @return void
     */
    public function testDeletedBookDisappears()
    {
        $this->markTestSkipped();

        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $b1->setISBN('FA404-2');
        $a->addBook($b2);

        /* As you cannot write $a->remove($b2), you have to delete $b2
        directly. */

        /* All objects unsaved. As of revision 851, this circumvents the
        $colBooks cache. Anyway, fails because getBooks() never checks if
        a colBooks entry has been deleted. */
        $this->assertEquals(2, count($a->getBooks()));
        $b2->delete();
        $this->assertEquals(1, count($a->getBooks()));

        /* Even if we had saved everything before and the delete() had
        actually updated the DB, the $b2 would still be a "zombie" in
        $a's $colBooks field. */
    }

    /**
     * @return void
     */
    public function testNewObjectsGetLostOnJoin()
    {
        /* While testNewObjectsAvailableWhenSaveNotCalled passed as of
        revision 851, in this case we call getBooksJoinPublisher() instead
        of just getBooks(). get...Join...() does not contain the check whether
        the current object is new, it will always consult the DB and lose the
        new objects entirely. Thus the test fails. (At least for Propel 1.2 ?!?) */
        $this->markTestSkipped();

        $a = new Author();
        $a->setFirstName('Douglas');
        $a->setLastName('Adams');

        $p = new Publisher();
        $p->setName('Pan Books Ltd.');

        $b1 = new Book();
        $b1->setTitle('The Hitchhikers Guide To The Galaxy');
        $b1->setISBN('FA404-1');
        $b1->setPublisher($p); // uh... did not check that :^)
        $a->addBook($b1);

        $b2 = new Book();
        $b2->setTitle('The Restaurant At The End Of The Universe');
        $b1->setISBN('FA404-2');
        $b2->setPublisher($p);
        $a->addBook($b2);

        $books = $a->getBooksJoinPublisher();
        $this->assertEquals(2, count($books));
        $this->assertStringContainsString($b1, $books);
        $this->assertStringContainsString($b2, $books);

        $a->save();
        $this->assertFalse($b1->isNew());
        $this->assertFalse($b2->isNew());
    }
}
