<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Test class for ModelCriteria.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ModelCriteriaHooksTest extends BookstoreTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
    }

    public function testPreSelect()
    {
        $c = new ModelCriteriaWithPreSelectHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $this->assertEquals(1, count($books), 'preSelect() can modify the Criteria before find() fires the query');

        $c = new ModelCriteriaWithPreSelectHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->count();
        $this->assertEquals(1, $nbBooks, 'preSelect() can modify the Criteria before count() fires the query');
    }

    public function testPreDelete()
    {
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $count = count($books);
        $book = $books->shift();

        $c = new ModelCriteriaWithPreDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Id = ?', $book->getId());
        $nbBooks = $c->delete();
        $this->assertEquals(12, $nbBooks, 'preDelete() can change the return value of delete()');

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->count();
        $this->assertEquals($count, $nbBooks, 'preDelete() can bypass the row deletion');

        $c = new ModelCriteriaWithPreDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll();
        $this->assertEquals(12, $nbBooks, 'preDelete() can change the return value of deleteAll()');

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->count();
        $this->assertEquals($count, $nbBooks, 'preDelete() can bypass the row deletion');
    }

    public function testPostDelete()
    {
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $count = count($books);
        $book = $books->shift();

        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Id = ?', $book->getId());
        $nbBooks = $c->delete($this->con);
        $this->assertEquals(1, $this->con->lastAffectedRows, 'postDelete() is called after delete()');

        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll($this->con);
        $this->assertEquals(3, $this->con->lastAffectedRows, 'postDelete() is called after deleteAll()');
    }

    public function testPreAndPostDelete()
    {
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $count = count($books);
        $book = $books->shift();

        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPreAndPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Id = ?', $book->getId());
        $nbBooks = $c->delete($this->con);
        $this->assertEquals(12, $this->con->lastAffectedRows, 'postDelete() is called after delete() even if preDelete() returns not null');

        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPreAndPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll($this->con);
        $this->assertEquals(12, $this->con->lastAffectedRows, 'postDelete() is called after deleteAll() even if preDelete() returns not null');
    }

    public function testPreUpdate()
    {
        $c = new ModelCriteriaWithPreUpdateHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('Title' => 'foo'));

        $c = new ModelCriteriaWithPreUpdateHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $book = $c->findOne();

        $this->assertEquals('1234', $book->getISBN(), 'preUpdate() can modify the values');
    }

    public function testPostUpdate()
    {
        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPostUpdateHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('Title' => 'foo'), $this->con);
        $this->assertEquals(1, $this->con->lastAffectedRows, 'postUpdate() is called after update()');
    }

    public function testPreAndPostUpdate()
    {
        $this->con->lastAffectedRows = 0;

        $c = new ModelCriteriaWithPreAndPostUpdateHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('Title' => 'foo'), $this->con);
        $this->assertEquals(52, $this->con->lastAffectedRows, 'postUpdate() is called after update() even if preUpdate() returns not null');
    }
}

class ModelCriteriaWithPreSelectHook extends ModelCriteria
{
    public function preSelect(ConnectionInterface $con)
    {
        $this->where($this->getModelAliasOrName() . '.Title = ?', 'Don Juan');
    }
}

class ModelCriteriaWithPreDeleteHook extends ModelCriteria
{
    public function preDelete(ConnectionInterface $con)
    {
        return 12;
    }
}

class ModelCriteriaWithPostDeleteHook extends ModelCriteria
{
    public function postDelete($affectedRows, ConnectionInterface $con)
    {
        $con->lastAffectedRows = $affectedRows;
    }
}

class ModelCriteriaWithPreAndPostDeleteHook extends ModelCriteriaWithPostDeleteHook
{
    public function preDelete(ConnectionInterface $con)
    {
        return 12;
    }
}

class ModelCriteriaWithPreUpdateHook extends ModelCriteria
{
    public function preUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        $values['ISBN'] = '1234';
    }
}

class ModelCriteriaWithPostUpdateHook extends ModelCriteria
{
    public function postUpdate($affectedRows, ConnectionInterface $con)
    {
        $con->lastAffectedRows = $affectedRows;
    }
}

class ModelCriteriaWithPreAndPostUpdateHook extends ModelCriteriaWithPostUpdateHook
{
    public function preUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        return 52;
    }
}
