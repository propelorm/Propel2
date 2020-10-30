<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable12TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable12;
use Propel\Tests\Bookstore\Behavior\SortableTable12Query;

/**
 * Tests for SortableBehavior class query modifier when the scope is enabled
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class SortableBehaviorQueryBuilderModifierWithScopeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->populateTable12();
    }

    /**
     * @return void
     */
    public function testInList()
    {
        /* List used for tests
         scope=1   scope=2
         row1      row5
         row2      row6
         row3
         row4
        */
        $query = SortableTable12Query::create()->inList(1);
        $expectedQuery = SortableTable12Query::create()->add(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, 1, Criteria::EQUAL);
        $this->assertEquals($expectedQuery, $query, 'inList() filters the query by scope');
        $this->assertEquals(4, $query->count(), 'inList() filters the query by scope');
        $query = SortableTable12Query::create()->inList(2);
        $expectedQuery = SortableTable12Query::create()->add(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, 2, Criteria::EQUAL);
        $this->assertEquals($expectedQuery, $query, 'inList() filters the query by scope');
        $this->assertEquals(2, $query->count(), 'inList() filters the query by scope');
    }

    /**
     * @return void
     */
    public function testFilterByRank()
    {
        /* List used for tests
         scope=1   scope=2
         row1      row5
         row2      row6
         row3
         row4
        */
        $this->assertEquals('row1', SortableTable12Query::create()->filterByRank(1, 1)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertEquals('row5', SortableTable12Query::create()->filterByRank(1, 2)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertEquals('row4', SortableTable12Query::create()->filterByRank(4, 1)->findOne()->getTitle(), 'filterByRank() filters on the rank and the scope');
        $this->assertNull(SortableTable12Query::create()->filterByRank(4, 2)->findOne(), 'filterByRank() filters on the rank and the scope, which makes the query return no result on a non-existent rank');
    }

    /**
     * @return void
     */
    public function testOrderByRank()
    {
        $this->assertTrue(SortableTable12Query::create()->orderByRank() instanceof SortableTable12Query, 'orderByRank() returns the current query object');
        // default order
        $query = SortableTable12Query::create()->orderByRank();
        $expectedQuery = SortableTable12Query::create()->addAscendingOrderByColumn(SortableTable12TableMap::COL_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank asc');
        // asc order
        $query = SortableTable12Query::create()->orderByRank(Criteria::ASC);
        $expectedQuery = SortableTable12Query::create()->addAscendingOrderByColumn(SortableTable12TableMap::COL_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
        // desc order
        $query = SortableTable12Query::create()->orderByRank(Criteria::DESC);
        $expectedQuery = SortableTable12Query::create()->addDescendingOrderByColumn(SortableTable12TableMap::COL_POSITION);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
    }

    /**
     * @return void
     */
    public function testFindList()
    {
        $ts = SortableTable12Query::create()->findList(1);
        $this->assertTrue($ts instanceof ObjectCollection, 'findList() returns a collection of objects');
        $this->assertEquals(4, count($ts), 'findList() filters the query by scope');
        $this->assertEquals('row1', $ts[0]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row2', $ts[1]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row3', $ts[2]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row4', $ts[3]->getTitle(), 'findList() returns an ordered scoped list');
        $ts = SortableTable12Query::create()->findList(2);
        $this->assertEquals(2, count($ts), 'findList() filters the query by scope');
        $this->assertEquals('row5', $ts[0]->getTitle(), 'findList() returns an ordered scoped list');
        $this->assertEquals('row6', $ts[1]->getTitle(), 'findList() returns an ordered scoped list');
    }

    /**
     * @return void
     */
    public function testFindOneByRank()
    {
        $this->assertTrue(SortableTable12Query::create()->findOneByRank(1, 1) instanceof SortableTable12, 'findOneByRank() returns an instance of the model object');
        $this->assertEquals('row1', SortableTable12Query::create()->findOneByRank(1, 1)->getTitle(), 'findOneByRank() returns a single item based on the rank and the scope');
        $this->assertEquals('row5', SortableTable12Query::create()->findOneByRank(1, 2)->getTitle(), 'findOneByRank() returns a single item based on the rank and the scope');
        $this->assertEquals('row4', SortableTable12Query::create()->findOneByRank(4, 1)->getTitle(), 'findOneByRank() returns a single item based on the rank a,d the scope');
        $this->assertNull(SortableTable12Query::create()->findOneByRank(4, 2), 'findOneByRank() returns no result on a non-existent rank and scope');
    }

    /**
     * @return void
     */
    public function testGetMaxRank()
    {
        $this->assertEquals(4, SortableTable12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank in the scope');
        $this->assertEquals(2, SortableTable12Query::create()->getMaxRank(2), 'getMaxRank() returns the maximum rank in the scope');
        // delete one
        $t4 = SortableTable12Query::create()->findOneByRank(4, 1);
        $t4->delete();
        $this->assertEquals(3, SortableTable12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
        // add one
        $t = new SortableTable12();
        $t->setMyScopeColumn(1);
        $t->save();
        $this->assertEquals(4, SortableTable12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
        // delete all
        SortableTable12Query::create()->deleteAll();
        $this->assertNull(SortableTable12Query::create()->getMaxRank(1), 'getMaxRank() returns null for empty tables');
        // add one
        $t = new SortableTable12();
        $t->setMyScopeColumn(1);
        $t->save();
        $this->assertEquals(1, SortableTable12Query::create()->getMaxRank(1), 'getMaxRank() returns the maximum rank');
    }

    /**
     * @return void
     */
    public function testReorder()
    {
        $objects = SortableTable12Query::create()->findList(1);
        $ids = [];
        foreach ($objects as $object) {
            $ids[] = $object->getPrimaryKey();
        }
        $ranks = [4, 3, 2, 1];
        $order = array_combine($ids, $ranks);
        SortableTable12Query::create()->reorder($order);
        $expected = [1 => 'row4', 2 => 'row3', 3 => 'row2', 4 => 'row1'];
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'reorder() reorders the suite');
        $expected = [1 => 'row5', 2 => 'row6'];
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'reorder() leaves other suites unchanged');
    }
}
