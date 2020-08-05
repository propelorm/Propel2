<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable11TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable11Query;

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 *
 * @group database
 */
class SortableBehaviorQueryUtilsBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->populateTable11();
    }

    /**
     * @return void
     */
    public function testStaticAttributes()
    {
        $this->assertEquals('sortable_table11.sortable_rank', SortableTable11TableMap::RANK_COL);
    }

    /**
     * @return void
     */
    public function testGetMaxRank()
    {
        $this->assertEquals(4, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        $t4 = SortableTable11Query::retrieveByRank(4);
        $t4->delete();
        $this->assertEquals(3, SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
        SortableTable11TableMap::doDeleteAll();
        $this->assertNull(SortableTable11Query::create()->getMaxRank(), 'getMaxRank() returns null for empty tables');
    }

    /**
     * @return void
     */
    public function testRetrieveByRank()
    {
        $t = SortableTable11Query::retrieveByRank(5);
        $this->assertNull($t, 'retrieveByRank() returns null for an unknown rank');
        $t3 = SortableTable11Query::retrieveByRank(3);
        $this->assertEquals(3, $t3->getRank(), 'retrieveByRank() returns the object with the required rank');
        $this->assertEquals('row3', $t3->getTitle(), 'retrieveByRank() returns the object with the required rank');
    }

    /**
     * @return void
     */
    public function testReorder()
    {
        $objects = SortableTable11Query::create()->find();
        $ids = [];
        foreach ($objects as $object) {
            $ids[] = $object->getPrimaryKey();
        }
        $ranks = [4, 3, 2, 1];
        $order = array_combine($ids, $ranks);
        SortableTable11Query::create()->reorder($order);
        $expected = [1 => 'row3', 2 => 'row2', 3 => 'row4', 4 => 'row1'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'reorder() reorders the suite');
    }

    /**
     * @return void
     */
    public function testDoSelectOrderByRank()
    {
        $objects = SortableTable11Query::doSelectOrderByRank()->getArrayCopy();
        $oldRank = 0;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() > $oldRank);
            $oldRank = $object->getRank();
        }
        $objects = SortableTable11Query::doSelectOrderByRank(null, Criteria::DESC)->getArrayCopy();
        $oldRank = 10;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() < $oldRank);
            $oldRank = $object->getRank();
        }
    }
}
