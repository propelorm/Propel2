<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;

/**
 * Tests for SortableBehavior class query modifier when the scope is enabled
 *
 * @author Francois Zaninotto
 *
 */
class SortableBehaviorQueryWithScopeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateEntity12();
    }

    public function testInList()
    {
        /* List used for tests
         scope=1   scope=2
         row1      row5
         row2      row6
         row3
         row4
        */
        $query = \SortableEntity12Query::create()->inList(1);
        $expectedQuery = \SortableEntity12Query::create()->add(\Map\SortableEntity12EntityMap::FIELD_MY_SCOPE_FIELD, 1, Criteria::EQUAL);
        $this->assertEquals($expectedQuery, $query, 'inList() filters the query by scope');
        $this->assertEquals(4, $query->count(), 'inList() filters the query by scope');
        $query = \SortableEntity12Query::create()->inList(2);
        $expectedQuery = \SortableEntity12Query::create()->add(\Map\SortableEntity12EntityMap::FIELD_MY_SCOPE_FIELD, 2, Criteria::EQUAL);
        $this->assertEquals($expectedQuery, $query, 'inList() filters the query by scope');
        $this->assertEquals(2, $query->count(), 'inList() filters the query by scope');
    }

    public function testFilterByRank()
    {
        /* List used for tests
         scope=1   scope=2
         row1      row5
         row2      row6
         row3
         row4
        */
        $this->assertEquals('row1', \SortableEntity12Query::create()->filterByRank(1, 1)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertEquals('row5', \SortableEntity12Query::create()->filterByRank(1, 2)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertEquals('row4', \SortableEntity12Query::create()->filterByRank(4, 1)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertNull(\SortableEntity12Query::create()->filterByRank(4, 2)->findOne(), 'filterByRank() filters on the rank and the scope, which makes the query return no result on a non-existent rank');
    }

    public function testOrderByRank()
    {
        $this->assertTrue(\SortableEntity12Query::create()->orderByRank() instanceof \SortableEntity12Query, 'orderByRank() returns the current query object');
        // default order
        $query = \SortableEntity12Query::create()->orderByRank();
        $expectedQuery = \SortableEntity12Query::create()->addAscendingOrderByField(\Map\SortableEntity12EntityMap::FIELD_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank asc');
        // asc order
        $query = \SortableEntity12Query::create()->orderByRank(Criteria::ASC);
        $expectedQuery = \SortableEntity12Query::create()->addAscendingOrderByField(\Map\SortableEntity12EntityMap::FIELD_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
        // desc order
        $query = \SortableEntity12Query::create()->orderByRank(Criteria::DESC);
        $expectedQuery = \SortableEntity12Query::create()->addDescendingOrderByField(\Map\SortableEntity12EntityMap::FIELD_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
    }

    public function testFindList()
    {
        $ts = \SortableEntity12Query::create()->findList(1);
        $this->assertTrue($ts instanceof ObjectCollection, 'findList() returns a collection of objects');
        $this->assertEquals(4, count($ts), 'findList() filters the query by scope');
        $this->assertEquals('row1', $ts[0]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row2', $ts[1]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row3', $ts[2]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row4', $ts[3]->getTitle(), 'findList() returns an ordered scoped list');
        $ts = \SortableEntity12Query::create()->findList(2);
        $this->assertEquals(2, count($ts), 'findList() filters the query by scope');
        $this->assertEquals('row5', $ts[0]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row6', $ts[1]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals(4, count(\SortableEntity12Query::create()->findList(null)), 'findlist() returns the list of objects in the scope');
        $this->assertEquals(4, count(\SortableEntity12Query::create()->findList(1)), 'findlist() returns the list of objects in the scope');
        $this->assertEquals(2, count(\SortableEntity12Query::create()->findList(2)), 'findlist() returns the list of objects in the scope');
    }

    public function testFindOneByRank()
    {
        $this->assertTrue(\SortableEntity12Query::create()->findOneByRank(1, 1) instanceof \SortableEntity12, 'findOneByRank() returns an instance of the model object');
        $this->assertEquals('row1', \SortableEntity12Query::create()->findOneByRank(1, 1)->getTitle(), 'findOneByRank() returns a single item based on the rank and the scope');
        $this->assertEquals('row5', \SortableEntity12Query::create()->findOneByRank(1, 2)->getTitle(), 'findOneByRank() returns a single item based on the rank and the scope');
        $this->assertEquals('row4', \SortableEntity12Query::create()->findOneByRank(4, 1)->getTitle(), 'findOneByRank() returns a single item based on the rank a,d the scope');
        $this->assertNull(\SortableEntity12Query::create()->findOneByRank(4, 2), 'findOneByRank() returns no result on a non-existent rank and scope');
    }

    public function testGetMaxRank()
    {
        $repository = $this->getConfiguration()->getRepository('\SortableEntity12');
        $this->assertEquals(4, \SortableEntity12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank in the scope');
        $this->assertEquals(2, \SortableEntity12Query::create()->getMaxRank(2), 'getMaxRank() returns the maximum rank in the scope');
        // delete one
        $t4 = \SortableEntity12Query::create()->findOneByRank(4, 1);
        $repository->remove($t4);
        $this->assertEquals(3, \SortableEntity12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
        // add one
        $t = new \SortableEntity12();
        $t->setMyScopeField(1);
        $repository->save($t);
        $this->assertEquals(4, \SortableEntity12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
        // delete all
        $repository->deleteAll();
        $this->assertNull(\SortableEntity12Query::create()->getMaxRank(1), 'getMaxRank() returns null for empty tables');
        // add one
        $t = new \SortableEntity12();
        $t->setMyScopeField(1);
        $repository->save($t);
        $this->assertEquals(1, \SortableEntity12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
    }

    public function testDoSelectOrderByRank()
    {
        $c = new Criteria(\Map\SortableEntity12EntityMap::DATABASE_NAME);
        $c->add(\Map\SortableEntity12EntityMap::SCOPE_COL, 1);
        $objects = \SortableEntity12Query::create(null, $c)->orderByRank()->find()->getArrayCopy();
        $oldRank = 0;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() > $oldRank);
            $oldRank = $object->getRank();
        }
        $c = new Criteria(\Map\SortableEntity12EntityMap::DATABASE_NAME);
        $c->add(\Map\SortableEntity12EntityMap::SCOPE_COL, 1);
        $objects = \SortableEntity12Query::create(null, $c)->orderByRank(Criteria::DESC)->find()->getArrayCopy();
        $oldRank = 10;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() < $oldRank);
            $oldRank = $object->getRank();
        }
    }

    public function testCountList()
    {
        $this->assertEquals(4, \SortableEntity12Query::create()->inList(1)->count(), 'countList() returns the list of objects in the scope');
        $this->assertEquals(2, \SortableEntity12Query::create()->inList(2)->count(), 'countList() returns the list of objects in the scope');

        $this->assertEquals(4, \SortableEntity12Query::create()->inList(null)->count(), 'countList() returns the list of objects in the scope');
    }

    public function testDeleteList()
    {
        $this->assertEquals(4, \SortableEntity12Query::create()->inList(null)->delete(), 'deleteList() returns the list of deleted objects in the scope');
        $this->assertEquals(0, \SortableEntity12Query::create()->inList()->count(), 'deleteList() deletes the objects in the scope');
        $this->assertEquals(4, \SortableEntity12Query::create()->inList(1)->delete(), 'deleteList() returns the list of deleted objects in the scope');
        $this->assertEquals(0, \SortableEntity12Query::create()->inList(1)->count(), 'deleteList() deletes the objects in the scope');
        $this->assertEquals(2, \SortableEntity12Query::create()->inList(2)->delete(), 'deleteList() returns the list of deleted objects in the scope');
        $this->assertEquals(0, \SortableEntity12Query::create()->inList(2)->count(), 'deleteList() deletes the objects in the scope');
    }
}
