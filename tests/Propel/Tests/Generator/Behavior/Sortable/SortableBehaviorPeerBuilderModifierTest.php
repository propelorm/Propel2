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

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 */
class SortableBehaviorPeerBuilderModifierTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateTable11();
    }

    public function testStaticAttributes()
    {
        $this->assertEquals('sortable_table11.SORTABLE_RANK', \Map\SortableTable11TableMap::RANK_COL);
    }

    public function testGetMaxRank()
    {
        $this->assertEquals(4, \SortableTable11Peer::getMaxRank(), 'getMaxRank() returns the maximum rank');
        $t4 = \SortableTable11Peer::retrieveByRank(4);
        $t4->delete();
        $this->assertEquals(3, \SortableTable11Peer::getMaxRank(), 'getMaxRank() returns the maximum rank');
        \SortableTable11Peer::doDeleteAll();
        $this->assertNull(\SortableTable11Peer::getMaxRank(), 'getMaxRank() returns null for empty tables');
    }
    public function testRetrieveByRank()
    {
        $t = \SortableTable11Peer::retrieveByRank(5);
        $this->assertNull($t, 'retrieveByRank() returns null for an unknown rank');
        $t3 = \SortableTable11Peer::retrieveByRank(3);
        $this->assertEquals(3, $t3->getRank(), 'retrieveByRank() returns the object with the required rank');
        $this->assertEquals('row3', $t3->getTitle(), 'retrieveByRank() returns the object with the required rank');
    }

    public function testReorder()
    {
        $objects = \SortableTable11Query::create()->find();
        $ids = array();
        foreach ($objects as $object) {
            $ids[]= $object->getPrimaryKey();
        }
        $ranks = array(4, 3, 2, 1);
        $order = array_combine($ids, $ranks);
        \SortableTable11Peer::reorder($order);
        $expected = array(1 => 'row3', 2 => 'row2', 3 => 'row4', 4 => 'row1');
        $this->assertEquals($expected, $this->getFixturesArray(), 'reorder() reorders the suite');
    }

    public function testDoSelectOrderByRank()
    {
        $objects = \SortableTable11Peer::doSelectOrderByRank()->getArrayCopy();
        $oldRank = 0;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() > $oldRank);
            $oldRank = $object->getRank();
        }
        $objects = \SortableTable11Peer::doSelectOrderByRank(null, Criteria::DESC)->getArrayCopy();
        $oldRank = 10;
        while ($object = array_shift($objects)) {
            $this->assertTrue($object->getRank() < $oldRank);
            $oldRank = $object->getRank();
        }
    }
}
