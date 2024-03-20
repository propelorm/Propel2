<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Exception;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Essay;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\PublisherQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Test class for Exists.
 *
 * @author Moritz Ringler
 *
 * @group database
 */
class ExistsTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'ExistsTestClasses.php';
    }

    /**
     * @return void
     */
    public function testWhereExists()
    {
        [$author1, $author2, $author3] = $this->createTestData();
        // all authors with at least one good book
        $existsQueryCriteria = BookQuery::create()->filterByTitle('good')->where('Book.AuthorId = Author.Id');
        $authors = AuthorQuery::create()->whereExists($existsQueryCriteria)->orderById()->find($this->con)->getData();

        $this->assertEquals([$author1, $author3], $authors);
    }

    /**
     * @return void
     */
    public function testWhereNotExists()
    {
        [$author1, $author2, $author3, $author4] = $this->createTestData();
        // all authors with no bad book
        $existsQueryCriteria = BookQuery::create()->filterByTitle('bad')->where('Book.AuthorId = Author.Id');
        $authors = AuthorQuery::create()->whereNotExists($existsQueryCriteria)->orderById()->find($this->con)->getData();

        $this->assertEquals([$author3, $author4], $authors);
    }

    /**
     * @return void
     */
    public function testCustomExistsOnNonrelatedTables()
    {
        [$author1] = $this->createTestData();
        PublisherQuery::create()->deleteAll($this->con);
        $publisher = new Publisher();
        $publisher->setName($author1->getLastName())->save($this->con);
        (new Publisher())->setName('Random Horse')->save($this->con);
        // all publishers with name of an author
        $existsQueryCriteria = AuthorQuery::create()->where('Author.LastName = Publisher.Name');
        $publishers = PublisherQuery::create()->whereExists($existsQueryCriteria)->find($this->con)->getData();

        $this->assertEquals([$publisher], $publishers);
    }

    /**
     * @return void
     */
    public function testUseExistsQueryReturnsSecondaryQuery()
    {
        $query = AuthorQuery::create()->useExistsQuery('Book');
        $this->assertInstanceOf(BookQuery::class, $query);
    }
    
    /**
     * @return void
     */
    public function testUseExistsQueryWithSpecificClassReturnsCorrectClass()
    {
        new GoodBookQuery();
        $query = AuthorQuery::create()->useExistsQuery('Book', null, GoodBookQuery::class);
        $this->assertInstanceOf(GoodBookQuery::class, $query);
    }

    /**
     * @return void
     */
    public function testUseExistsQuery()
    {
        [$author1, $author2, $author3] = $this->createTestData();
        // all authors with at least one good book
        $authors = AuthorQuery::create()
        ->useExistsQuery('Book')
        ->filterByTitle('good')
        ->endUse()
        ->orderById()
        ->find($this->con)
        ->getData();

        $this->assertEquals([$author1, $author3], $authors);
    }

    /**
     * @return void
     */
    public function testUseNotExistsQuery()
    {
        [$author1, $author2, $author3, $author4] = $this->createTestData();
        // all authors with no bad book
        $authors = AuthorQuery::create()
        ->useNotExistsQuery('Book')
        ->filterByTitle('bad')
        ->endUse()
        ->orderById()
        ->find($this->con)
        ->getData();

        $this->assertEquals([$author3, $author4], $authors);
    }

    /**
     * @return void
     */
    public function testUseExistsQueryWithAlias()
    {
        [$author1, $author2] = $this->createTestData();
        // books of authors who have written more than one books (dummy query)
        $books = BookQuery::create('outerBook')
        ->useAuthorQuery()
        ->useExistsQuery('Book', 'innerBook')
        ->where('outerBook.id != innerBook.Id')
        ->endUse()
        ->endUse()
        ->find($this->con)
        ->getData();
        $this->assertCount($author1->countBooks(null, false, $this->con) + $author2->countBooks(null, false, $this->con), $books);
    }

    /**
     * @return void
     */
    public function testUseExistsAndWithQuery()
    {
        $this->createTestData();
        // all books of authors with at least one good book
        $query = BookQuery::create()
        ->joinWithAuthor()
        ->useAuthorQuery()
        ->useExistsQuery('Book')
        ->filterByTitle('good')
        ->endUse()
        ->endUse();

        $expectedSql =
        'SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, author.id, author.first_name, author.last_name, author.email, author.age '
            . 'FROM book LEFT JOIN author ON (book.author_id=author.id) '
                . 'WHERE EXISTS (SELECT 1 AS existsFlag FROM book WHERE author.id=book.author_id AND book.title=:p1)';
                $params = [];
                $this->assertEquals($expectedSql, $query->createSelectSql($params));

                $books = $query->find($this->con)->getData();
                $this->assertCount(4, $books);
    }

    /**
     * @return void
     */
    public function testUseExistsQueryWithSpecificClass()
    {
        [$author1, $author2, $author3] = $this->createTestData();
     // all authors with at least one good book according to GoodBookQuery
        $authors = AuthorQuery::create()
        ->useExistsQuery('Book', null, GoodBookQuery::class)
        ->filterByIsGood()
        ->endUse()
        ->orderById()
        ->find($this->con)
        ->getData();

        $this->assertEquals([$author1, $author3], $authors);
    }

    /**
     * @return void
     */
    public function testUseExistsQueryCanNestUseQuery()
    {
        [$author1, $author2, $author3] = $this->createTestData();
        (new Essay())->setFirstAuthor($author1)->setTitle('leEssay')->save($this->con);
        (new Essay())->setFirstAuthor($author1)->setTitle('leEssay')->save($this->con);
        (new Essay())->setFirstAuthor($author3)->setTitle('leEssay')->save($this->con);

        // all books of authors who are first author of an essay called 'leEssay'
        $books = BookQuery::create()
        ->useExistsQuery('Author')
        ->useEssayRelatedByFirstAuthorIdQuery()
        ->filterByTitle('leEssay')
        ->endUse()
        ->endUse()
        ->find($this->con)
        ->getData();

        $this->assertCount($author1->countBooks(null, false, $this->con) + $author3->countBooks(null, false, $this->con), $books);
    }

    /**
     * @return void
     */
    public function testUseExistsQueryDoesNotMergeWithOuterQuery()
    {
        $sawException = false;
        $innerBookQuery = ExceptionOnMergeAuthorQuery::create()->useExistsQuery('Book');
        try {
            $innerBookQuery->endUse();
        } catch (Exception $e) {
            $sawException = true;
        }
        $this->assertFalse($sawException, 'endUse() should not call mergeWidth');
    }

    /**
     * Creates and returns four authors, one and three have a good book, one and two have a bad book, four has no book
     *
     * @return \Propel\Tests\Bookstore\Author[]
     */
    private function createTestData()
    {
        BookQuery::create()->deleteAll($this->con);
        AuthorQuery::create()->deleteAll($this->con);

        $author1 = $this->createAuthor('LeAuthor1');
        $this->createBook($author1);
        $this->createBook($author1, 1);
        $this->createBook($author1, 1);

        $author2 = $this->createAuthor('LeAuthor2');
        $this->createBook($author2);
        $this->createBook($author2);

        $author3 = $this->createAuthor('LeAuthor3');
        $this->createBook($author3, 1);

        $author4 = $this->createAuthor('LeAuthor4');

        return [$author1, $author2, $author3, $author4];
    }

    private function createAuthor(string $lastName, string $firstname = 'LeDefaultFirstname')
    {
        $author = new Author();
        $author->setLastName($lastName)->setFirstName($firstname)->save($this->con);

        return $author;
    }

    private function createBook(Author $author, $isGood = false)
    {
        $title = ($isGood) ? 'good' : 'bad';
        $book = new Book();
        $book->setTitle($title)->setAuthor($author)->setISBN('123')->save($this->con);

        return $book;
    }
}
