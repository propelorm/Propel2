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
 * Tests for SortableBehavior class query modifier
 *
 * @author Francois Zaninotto
 *
 * @group database
 * @group skip
 */
class SortableBehaviorQueryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateEntity11();
    }

    public function testFilterByRank()
    {
        $this->assertTrue(\SortableEntity11Query::create()->filterByRank(1) instanceof \SortableEntity11Query, 'filterByRank() returns the current query object');
        $this->assertEquals('row1', \SortableEntity11Query::create()->filterByRank(1)->findOne()->getTitle(), 'filterByRank() filters on the rank');
        $this->assertEquals('row4', \SortableEntity11Query::create()->filterByRank(4)->findOne()->getTitle(), 'filterByRank() filters on the rank');
        $this->assertNull(\SortableEntity11Query::create()->filterByRank(5)->findOne(), 'filterByRank() filters on the rank, which makes the query return no result on a non-existent rank');
    }

    public function testOrderByRank()
    {
        $this->assertTrue(\SortableEntity11Query::create()->orderByRank() instanceof \SortableEntity11Query, 'orderByRank() returns the current query object');
        // default order
        $query = \SortableEntity11Query::create()->orderByRank();
        $expectedQuery = \SortableEntity11Query::create()->addAscendingOrderByField(\Map\SortableEntity11EntityMap::FIELD_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank asc');
        // asc order
        $query = \SortableEntity11Query::create()->orderByRank(Criteria::ASC);
        $expectedQuery = \SortableEntity11Query::create()->addAscendingOrderByField(\Map\SortableEntity11EntityMap::FIELD_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
        // desc order
        $query = \SortableEntity11Query::create()->orderByRank(Criteria::DESC);
        $expectedQuery = \SortableEntity11Query::create()->addDescendingOrderByField(\Map\SortableEntity11EntityMap::FIELD_SORTABLE_RANK);
        $this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testOrderByRankIncorrectDirection()
    {
        \SortableEntity11Query::create()->orderByRank('foo');
    }

    public function testFindList()
    {
        $ts = \SortableEntity11Query::create()->findList();
        $this->assertTrue($ts instanceof ObjectCollection, 'findList() returns a collection of objects');
        $this->assertEquals(4, count($ts), 'findList() does not filter the query');
        $this->assertEquals('row1', $ts[0]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row2', $ts[1]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row3', $ts[2]->getTitle(), 'findList() returns an ordered list');
        $this->assertEquals('row4', $ts[3]->getTitle(), 'findList() returns an ordered list');
    }

    public function testFindOneByRank()
    {
        $this->assertTrue(\SortableEntity11Query::create()->findOneByRank(1) instanceof \SortableEntity11, 'findOneByRank() returns an instance of the model object');
        $this->assertEquals('row1', \SortableEntity11Query::create()->findOneByRank(1)->getTitle(), 'findOneByRank() returns a single item based on the rank');
        $this->assertEquals('row4', \SortableEntity11Query::create()->findOneByRank(4)->getTitle(), 'findOneByRank() returns a single item based on the rank');
        $this->assertNull(\SortableEntity11Query::create()->findOneByRank(5), 'findOneByRank() returns no result on a non-existent rank');
    }

    public function testGetMaxRank()
    {
        $repository = $this->getConfiguration()->getRepository('\SortableEntity11');
        $this->assertEquals(4, $repository->createQuery()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // delete one
        $t4 = $repository->createQuery()->findOneByRank(4);
        $repository->remove($t4);
        $this->assertEquals(3, $repository->createQuery()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // add one
        $t = new \SortableEntity11();
        $repository->save($t);
        $this->assertEquals(4, $repository->createQuery()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        // delete all
        $repository->deleteAll();
        $this->assertNull($repository->createQuery()->getMaxRank(), 'getMaxRank() returns null for empty tables');
        // add one
        $t = new \SortableEntity11();
        $repository->save($t);
        $this->assertEquals(1, $repository->createQuery()->getMaxRank(), 'getMaxRank() returns the maximum rank');
    }

    public function testSelectOrderByRank()
    {
        $objects = \SortableEntity11Query::create()->orderByRank()->find()->getArrayCopy();
        $oldRank = 0;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() > $oldRank);
            $oldRank = $object->getRank();
        }
        $objects = \SortableEntity11Query::create()->orderByRank(Criteria::DESC)->find()->getArrayCopy();
        $oldRank = 10;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() < $oldRank);
            $oldRank = $object->getRank();
        }
    }
}
