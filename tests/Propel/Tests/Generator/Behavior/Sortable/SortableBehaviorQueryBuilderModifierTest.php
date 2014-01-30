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

use Propel\Tests\Bookstore\Behavior\SortableTable11Query;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable11TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable11 as Table11;

/**
 * Tests for SortableBehavior class query modifier
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class SortableBehaviorQueryBuilderModifierTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateTable11();
    }

    public function testFilterByRank()
    {
        $this->assertTrue(SortableTable11Query::create()->filterByRank(1) instanceof SortableTable11Query, 'filterByRank() returns the current query object');
        $this->assertEquals('row1', SortableTable11Query::create()->filterByRank(1)->findOne()->getTitle(), 'filterByRank() filters on the rank');
        $this->assertEquals('row4', SortableTable11Query::create()->filterByRank(4)->findOne()->getTitle(), 'filterByRank() filters on the rank');
        $this->assertNull(SortableTable11Query::create()->filterByRank(5)->findOne(), 'filterByRank() filters on the rank, which makes the query return no result on a non-existent rank');
    }

    public function testOrderByRank()
    {
        $this->assertTrue(SortableTable11Query::create()->orderByRank() instanceof SortableTable11Query, 'orderByRank() returns the current query object');
        // default order
        $query = SortableTable11Query::create()->orderByRank();
        $expectedQuery = SortableTable11Query::create()->addAscendingOrderByColumn(SortableTable11TableMap::COL_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank asc');
        // asc order
        $query = SortableTable11Query::create()->orderByRank(Criteria::ASC);
        $expectedQuery = SortableTable11Query::create()->addAscendingOrderByColumn(SortableTable11TableMap::COL_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
        // desc order
        $query = SortableTable11Query::create()->orderByRank(Criteria::DESC);
        $expectedQuery = SortableTable11Query::create()->addDescendingOrderByColumn(SortableTable11TableMap::COL_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testOrderByRankIncorrectDirection()
    {
        SortableTable11Query::create()->orderByRank('foo');
    }

    public function testFindList()
    {
        $ts = SortableTable11Query::create()->findList();
        $this->assertTrue($ts instanceof ObjectCollection, 'findList() returns a collection of objects');
        $this->assertEquals(4, count($ts), 'findList() does not filter the query');
        $this->assertEquals('row1', $ts[0]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row2', $ts[1]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row3', $ts[2]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row4', $ts[3]->getTitle(), 'findList() returns an ordered list');
    }

    public function testFindOneByRank()
    {
        $this->assertTrue(SortableTable11Query::create()->findOneByRank(1) instanceof Table11, 'findOneByRank() returns an instance of the model object');
        $this->assertEquals('row1', SortableTable11Query::create()->findOneByRank(1)->getTitle(), 'findOneByRank() returns a single item based on the rank');
        $this->assertEquals('row4', SortableTable11Query::create()->findOneByRank(4)->getTitle(), 'findOneByRank() returns a single item based on the rank');
        $this->assertNull(SortableTable11Query::create()->findOneByRank(5), 'findOneByRank() returns no result on a non-existent rank');
    }

    public function testGetMaxRank()
    {
        $this->assertEquals(4, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // delete one
        $t4 = SortableTable11Query::create()->findOneByRank(4);
        $t4->delete();
        $this->assertEquals(3, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // add one
        $t = new Table11();
        $t->save();
        $this->assertEquals(4, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // delete all
        SortableTable11Query::create()->deleteAll();
        $this->assertNull(SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns null for empty tables');
        // add one
        $t = new Table11();
        $t->save();
        $this->assertEquals(1, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
    }

    public function testReorder()
    {
        $objects = SortableTable11Query::create()->find();
        $ids = array();
        foreach ($objects as $object) {
            $ids[]= $object->getPrimaryKey();
        }
        $ranks = array(4, 3, 2, 1);
        $order = array_combine($ids, $ranks);
        SortableTable11Query::create()->reorder($order);
        $expected = array(1 => 'row3', 2 => 'row2', 3 => 'row4', 4 => 'row1');
        $this->assertEquals($expected, $this->getFixturesArray(), 'reorder() reorders the suite');
    }
}
