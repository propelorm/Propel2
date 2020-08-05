<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\PolymorphicRelationLogTableMap;
use Propel\Tests\Bookstore\PolymorphicRelationLog;
use Propel\Tests\Bookstore\PolymorphicRelationLogQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Tests polymorphic relation primary with polymorphic_relation_log table.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 *
 * @group database
 */
class BookstoreLoggingTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        PolymorphicRelationLogQuery::create()->deleteAll();
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();
    }

    /**
     * @return void
     */
    public function testSetterAndGetter()
    {
        $book = new Book();
        $book->setTitle('Book 1');
        $book->setISBN('12313');
        $book->save();

        $author = new Author();
        $author->setFirstName('Steve');
        $author->setLastName('Bla');
        $author->save();

        $log = new PolymorphicRelationLog();
        $log->setMessage('Hi');
        $log->setBook($book);

        $this->assertEquals('book', $log->getTargetType());
        $this->assertEquals($book->getId(), $log->getTargetId());

        $log->setAuthor($author);

        $this->assertEquals('author', $log->getTargetType());
        $this->assertEquals($author->getId(), $log->getTargetId());

        $this->assertCount(1, $author->getPolymorphicRelationLogs());
        $this->assertCount(1, $book->getPolymorphicRelationLogs());
        $log->save();
    }

    /**
     * @return void
     */
    public function testQueryFilter()
    {
        $book = new Book();
        $book->setTitle('Book 1');
        $book->setISBN('12313');
        $book->save();

        $author = new Author();
        $author->setFirstName('Steve');
        $author->setLastName('Bla');
        $author->save();

        $bookLog = new PolymorphicRelationLog();
        $bookLog->setMessage('book added');
        $bookLog->setBook($book);
        $bookLog->save();

        $authorLog = new PolymorphicRelationLog();
        $authorLog->setMessage('author added');
        $authorLog->setAuthor($author);
        $authorLog->save();

        PolymorphicRelationLogTableMap::clearInstancePool();
        $foundLog = PolymorphicRelationLogQuery::create()
            ->filterByBook($book)
            ->findOne();

        $this->assertEquals($bookLog->getId(), $foundLog->getId());
        $this->assertEquals('book', $foundLog->getTargetType());
        $this->assertEquals($book, $foundLog->getBook());
        $this->assertNull($foundLog->getAuthor());

        PolymorphicRelationLogTableMap::clearInstancePool();
        $foundLog = PolymorphicRelationLogQuery::create()
            ->filterByAuthor($author)
            ->findOne();

        $this->assertEquals($authorLog->getId(), $foundLog->getId());
        $this->assertEquals('author', $foundLog->getTargetType());
        $this->assertEquals($author, $foundLog->getAuthor());
        $this->assertNull($foundLog->getBook());

        // ref methods
        BookTableMap::clearInstancePool();
        $foundBook = BookQuery::create()
            ->filterByPolymorphicRelationLog($bookLog)
            ->findOne();

        $this->assertEquals($book->getId(), $foundBook->getId());

        BookTableMap::clearInstancePool();
        $foundAuthor = AuthorQuery::create()
            ->filterByPolymorphicRelationLog($bookLog)
            ->findOne();
        $this->assertNull($foundAuthor);

        $foundAuthor = AuthorQuery::create()
            ->filterByPolymorphicRelationLog($authorLog)
            ->findOne();
        $this->assertEquals($author->getId(), $foundAuthor->getId());
    }

    /**
     * @group mysql
     * @group pgsql
     *
     * @return void
     */
    public function testQueryJoins()
    {
        if ($this->runningOnSQLite()) {
            $this->markTestSkipped('SQLite does not support right joins');
        }

        $author = new Author();
        $author->setFirstName('Steve');
        $author->setLastName('Bla');
        $author->save();

        $author2 = new Author();
        $author2->setFirstName('Blumen');
        $author2->setLastName('Hosen');
        $author2->save();

        $book = new Book();
        $book->setTitle('Book 1');
        $book->setISBN('12313');
        $book->save();

        $log = new PolymorphicRelationLog();
        $log->setMessage('author added');
        $log->setAuthor($author);
        $log->save();

        $log = new PolymorphicRelationLog();
        $log->setMessage('author added');
        $log->setAuthor($author2);
        $log->save();

        $log = new PolymorphicRelationLog();
        $log->setMessage('author changed');
        $log->setAuthor($author);
        $log->save();

        $log = new PolymorphicRelationLog();
        $log->setMessage('book added 1');
        $log->setBook($book);
        $log->save();

        $this->assertEquals(4, PolymorphicRelationLogQuery::create()->count());

        $logs = PolymorphicRelationLogQuery::create()
            ->rightJoinAuthor()
            ->with('Author')
            ->orderById()
            ->find();

        $this->assertCount(3, $logs);
        $this->assertEquals($author, $logs[0]->getAuthor());
        $this->assertEquals($author2, $logs[1]->getAuthor());
        $this->assertEquals($author, $logs[2]->getAuthor());
        $this->assertNull($logs[0]->getBook());
        $this->assertNull($logs[1]->getBook());

        $logs = PolymorphicRelationLogQuery::create()
            ->rightJoinBook()
            ->with('Book')
            ->find();

        $this->assertCount(1, $logs);
        $this->assertEquals($book, $logs[0]->getBook());
        $this->assertNull($logs[0]->getAuthor());

        $logs = PolymorphicRelationLogQuery::create()
            ->useAuthorQuery(null, Criteria::RIGHT_JOIN)
                ->filterByFirstName('Steve')
            ->endUse()
            ->with('Author')
            ->find();

        $this->assertCount(2, $logs);

        $logs = PolymorphicRelationLogQuery::create()
            ->useAuthorQuery(null, Criteria::RIGHT_JOIN)
                ->filterByFirstName('Blumen')
            ->endUse()
            ->with('Author')
            ->find();

        $this->assertCount(1, $logs);

        $this->assertEquals(2, PolymorphicRelationLogQuery::create()
                ->filterByTargetId($author->getId())
                ->filterByTargetType('author')
                ->count());

        $this->assertEquals(4, PolymorphicRelationLogQuery::create()->count());

        AuthorTableMap::clearInstancePool();

        $author3 = AuthorQuery::create()
            ->leftJoinPolymorphicRelationLog()
            ->with('PolymorphicRelationLog')
            ->filterById($author->getId())
            ->find()
            ->get(0);

        $this->assertCount(2, $author3->getPolymorphicRelationLogs());
    }
}
