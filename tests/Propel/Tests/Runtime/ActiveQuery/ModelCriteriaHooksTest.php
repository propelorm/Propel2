<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionWrapper;
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
    /**
     * @var ConnectionWrapper
     */
    protected $con;
    
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::depopulate(Configuration::getCurrentConfiguration());
        BookstoreDataPopulator::populate();
        $this->con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
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
        $book = $books->shift();

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Id = ?', $book->getId());
        $nbBooks = $c->delete($this->con);
        $this->assertEquals(1, $nbBooks, 'postDelete() is called after delete()');

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll($this->con);
        $this->assertEquals(3, $nbBooks, 'postDelete() is called after deleteAll()');
    }

    public function testPreAndPostDelete()
    {
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $book = $books->shift();

        $c = new ModelCriteriaWithPreAndPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Id = ?', $book->getId());
        $nbBooks = $c->delete($this->con);
        $this->assertEquals(12, $nbBooks, 'postDelete() is called after delete() even if preDelete() returns not null');

        $c = new ModelCriteriaWithPreAndPostDeleteHook('bookstore', '\Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll($this->con);
        $this->assertEquals(12, $nbBooks, 'postDelete() is called after deleteAll() even if preDelete() returns not null');
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
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('Title' => 'foo'), $this->con);
        $this->assertEquals(1, $nbBooks, 'postUpdate() is called after update()');
    }

    public function testPreAndPostUpdate()
    {

        $c = new ModelCriteriaWithPreAndPostUpdateHook('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('Title' => 'foo'), $this->con);
        $this->assertEquals(52, $nbBooks, 'postUpdate() is called after update() even if preUpdate() returns not null');
    }
}

class ModelCriteriaWithPreSelectHook extends ModelCriteria
{
    public function preSelect()
    {
        $this->where($this->getModelAliasOrName() . '.Title = ?', 'Don Juan');
    }
}

class ModelCriteriaWithPreDeleteHook extends ModelCriteria
{
    public function preDelete($withEvents = false)
    {
        return 12;
    }
}

class ModelCriteriaWithPreAndPostDeleteHook extends ModelCriteria
{
    public function preDelete($withEvents = false)
    {
        return 12;
    }
}

class ModelCriteriaWithPreUpdateHook extends ModelCriteria
{
    public function preUpdate(&$values, $forceIndividualSaves = false)
    {
        $values['ISBN'] = '1234';
    }
}

class ModelCriteriaWithPreAndPostUpdateHook extends ModelCriteria
{
    public function preUpdate(&$values, $forceIndividualSaves = false)
    {
        return 52;
    }
}
