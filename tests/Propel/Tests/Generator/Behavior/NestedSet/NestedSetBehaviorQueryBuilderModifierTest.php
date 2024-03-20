<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Map\NestedSetTable9TableMap;
use NestedSetTable9;
use NestedSetTable9Query;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;

/**
 * Tests for NestedSetBehaviorQueryBuilderModifier class
 *
 * @author François Zaninotto
 */
class NestedSetBehaviorQueryBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function testDescendantsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->descendantsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals([], iterator_to_array($objs), 'descendantsOf() filters by descendants');
        $objs = NestedSetTable9Query::create()
            ->descendantsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t4, $t5, $t6, $t7], iterator_to_array($objs), 'descendantsOf() filters by descendants');
    }

    /**
     * @return void
     */
    public function testBranchOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->branchOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t7], iterator_to_array($objs), 'branchOf() filters by descendants and includes object passed as parameter');
        $objs = NestedSetTable9Query::create()
            ->branchOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t3, $t4, $t5, $t6, $t7], iterator_to_array($objs), 'branchOf() filters by descendants and includes object passed as parameter');
        $objs = NestedSetTable9Query::create()
            ->branchOf($t1)
            ->orderByBranch()
            ->find();
        $coll = $this->buildCollection([$t1, $t2, $t3, $t4, $t5, $t6, $t7]);
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($objs), 'branchOf() returns the whole tree for the root node');
    }

    /**
     * @return void
     */
    public function testChildrenOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->childrenOf($t6)
            ->orderByBranch()
            ->find();
        $this->assertEquals([], iterator_to_array($objs), 'childrenOf() returns empty collection for leaf nodes');
        $objs = NestedSetTable9Query::create()
            ->childrenOf($t5)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t6, $t7], iterator_to_array($objs), 'childrenOf() filters by children');
        $objs = NestedSetTable9Query::create()
            ->childrenOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t4, $t5], iterator_to_array($objs), 'childrenOf() filters by children and not by descendants');
    }

    /**
     * @return void
     */
    public function testSiblingsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $desc = NestedSetTable9Query::create()
            ->siblingsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([], iterator_to_array($desc), 'siblingsOf() returns empty collection for the root node');
        $desc = NestedSetTable9Query::create()
            ->siblingsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t2], iterator_to_array($desc), 'siblingsOf() filters by siblings');
    }

    /**
     * @return void
     */
    public function testAncestorsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->ancestorsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([], iterator_to_array($objs), 'ancestorsOf() returns empty collection for root node');
        $objs = NestedSetTable9Query::create()
            ->ancestorsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1], iterator_to_array($objs), 'ancestorsOf() filters by ancestors');
        $objs = NestedSetTable9Query::create()
            ->ancestorsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t3, $t5], iterator_to_array($objs), 'childrenOf() filters by ancestors');
    }

    /**
     * @return void
     */
    public function testRootsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->rootsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1], iterator_to_array($objs), 'rootsOf() returns the root node for root node');
        $objs = NestedSetTable9Query::create()
            ->rootsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t3], iterator_to_array($objs), 'rootsOf() filters by ancestors and includes the node passed as parameter');
        $objs = NestedSetTable9Query::create()
            ->rootsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t3, $t5, $t7], iterator_to_array($objs), 'rootsOf() filters by ancestors  and includes the node passed as parameter');
    }

    /**
     * @return void
     */
    public function testOrderByBranch()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToPrevSiblingOf($t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t2, $t3, $t5, $t6, $t7, $t4], iterator_to_array($objs), 'orderByBranch() orders by branch left to right');
        $objs = NestedSetTable9Query::create()
            ->orderByBranch(true)
            ->find();
        $this->assertEquals([$t4, $t7, $t6, $t5, $t3, $t2, $t1], iterator_to_array($objs), 'orderByBranch(true) orders by branch right to left');
    }

    /**
     * @return void
     */
    public function testOrderByLevel()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToPrevSiblingOf($t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $objs = NestedSetTable9Query::create()
            ->orderByLevel()
            ->find();

        $this->assertEquals([$t1, $t2, $t3, $t5, $t4, $t6, $t7], iterator_to_array($objs), 'orderByLevel() orders by level, from the root to the leaf');

        $objs = NestedSetTable9Query::create()
            ->orderByLevel(true)
            ->find();

        $this->assertEquals([$t7, $t6, $t4, $t5, $t3, $t2, $t1], iterator_to_array($objs), 'orderByLevel() orders by level, from the root to the leaf');
    }

    /**
     * @return void
     */
    public function testFindRoot()
    {
        $this->assertTrue(method_exists('NestedSetTable9Query', 'findRoot'), 'nested_set adds a findRoot() method');

        NestedSetTable9Query::create()->deleteAll();
        $this->assertNull(NestedSetTable9Query::create()->findRoot(), 'findRoot() returns null as long as no root node is defined');

        $t1 = new NestedSetTable9();
        $t1->setLeftValue(123);
        $t1->setRightValue(456);
        $t1->save();

        $this->assertNull(NestedSetTable9Query::create()->findRoot(), 'findRoot() returns null as long as no root node is defined');

        $t2 = new NestedSetTable9();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $t2->save();

        $this->assertEquals(NestedSetTable9Query::create()->findRoot(), $t2, 'findRoot() retrieves the root node');
    }

    /**
     * @return void
     */
    public function testfindTree()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $tree = NestedSetTable9Query::create()->findTree();
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($tree), 'findTree() retrieves the whole tree, ordered by branch');
    }

    protected function buildCollection($arr)
    {
        $coll = new ObjectCollection();
        $coll->setData($arr);
        $coll->setModel('NestedSetTable9');

        return $coll;
    }

    /**
     * @return void
     */
    public function testRetrieveRoot()
    {
        $this->assertTrue(
            method_exists('NestedSetTable9Query', 'retrieveRoot'),
            'nested_set adds a retrieveRoot() method'
        );

        NestedSetTable9TableMap::doDeleteAll();
        $this->assertNull(NestedSetTable9Query::retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');

        $t1 = new NestedSetTable9();
        $t1->setLeftValue(123);
        $t1->setRightValue(456);
        $t1->save();

        $this->assertNull(NestedSetTable9Query::retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');

        $t2 = new NestedSetTable9();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $t2->save();

        $this->assertEquals(NestedSetTable9Query::retrieveRoot(), $t2, 'retrieveRoot() retrieves the root node');
    }

    /**
     * @return void
     */
    public function testRetrieveTree()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $tree = NestedSetTable9Query::retrieveTree()->getArrayCopy();
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], $tree, 'retrieveTree() retrieves the whole tree');
        $c = new Criteria();
        $c->add(NestedSetTable9::LEFT_COL, 4, Criteria::GREATER_EQUAL);
        $tree = NestedSetTable9Query::retrieveTree($c)->getArrayCopy();
        $this->assertEquals([$t3, $t4, $t5, $t6, $t7], $tree, 'retrieveTree() accepts a Criteria as first parameter');
    }

    /**
     * @return void
     */
    public function testIsValid()
    {
        $this->assertTrue(method_exists('NestedSetTable9Query', 'isValid'), 'nested_set adds an isValid() method');
        $this->assertFalse(NestedSetTable9Query::isValid(null), 'isValid() returns false when passed null ');
        $t1 = new NestedSetTable9();
        $this->assertFalse(NestedSetTable9Query::isValid($t1), 'isValid() returns false when passed an empty node object');
        $t2 = new NestedSetTable9();
        $t2->setLeftValue(5)->setRightValue(2);
        $this->assertFalse(NestedSetTable9Query::isValid($t2), 'isValid() returns false when passed a node object with left > right');
        $t3 = new NestedSetTable9();
        $t3->setLeftValue(5)->setRightValue(5);
        $this->assertFalse(NestedSetTable9Query::isValid($t3), 'isValid() returns false when passed a node object with left = right');
        $t4 = new NestedSetTable9();
        $t4->setLeftValue(2)->setRightValue(5);
        $this->assertTrue(NestedSetTable9Query::isValid($t4), 'isValid() returns true when passed a node object with left < right');
    }

    /**
     * @return void
     */
    public function testDeleteTree()
    {
        $this->initTree();
        NestedSetTable9Query::deleteTree();
        $this->assertCount(0, NestedSetTable9Query::create()->find(), 'deleteTree() deletes the whole tree');
    }

    /**
     * @return void
     */
    public function testShiftRLValuesDelta()
    {
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 1);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [2, 15, 0],
            't2' => [3, 4, 1],
            't3' => [5, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes with a positive amount');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = -1, $left = 1);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [0, 13, 0],
            't2' => [1, 2, 1],
            't3' => [3, 12, 1],
            't4' => [4, 5, 2],
            't5' => [6, 11, 2],
            't6' => [7, 8, 3],
            't7' => [9, 10, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues can shift all nodes with a negative amount');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 3, $left = 1);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [4, 17, 0],
            't2' => [5, 6, 1],
            't3' => [7, 16, 1],
            't4' => [8, 9, 2],
            't5' => [10, 15, 2],
            't6' => [11, 12, 3],
            't7' => [13, 14, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes several units to the right');
        NestedSetTable9Query::shiftRLValues($delta = -3, $left = 1);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes several units to the left');
    }

    /**
     * @return void
     */
    public function testShiftRLValuesLeftLimit()
    {
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 15);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues does not shift anything when the left parameter is higher than the highest right value');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 5);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 15, 0],
            't2' => [2, 3, 1],
            't3' => [4, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts only the nodes having a LR value higher than the given left parameter');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 1);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [2, 15, 0],
            't2' => [3, 4, 1],
            't3' => [5, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes when the left parameter is 1');
    }

    /**
     * @return void
     */
    public function testShiftRLValuesRightLimit()
    {
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 1, $right = 0);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues does not shift anything when the right parameter is 0');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 1, $right = 5);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [2, 14, 0],
            't2' => [3, 4, 1],
            't3' => [5, 13, 1],
            't4' => [6, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shiftRLValues shifts only the nodes having a LR value lower than the given right parameter');
        $this->initTree();
        NestedSetTable9Query::shiftRLValues($delta = 1, $left = 1, $right = 15);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [2, 15, 0],
            't2' => [3, 4, 1],
            't3' => [5, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes when the right parameter is higher than the highest right value');
    }

    /**
     * @return void
     */
    public function testShiftLevel()
    {
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $this->initTree();
        NestedSetTable9Query::shiftLevel($delta = 1, $first = 7, $last = 12);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 3],
            't6' => [8, 9, 4],
            't7' => [10, 11, 4],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftLevel shifts all nodes with a left value between the first and last');
        $this->initTree();
        NestedSetTable9Query::shiftLevel($delta = -1, $first = 7, $last = 12);
        NestedSetTable9TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 1],
            't6' => [8, 9, 2],
            't7' => [10, 11, 2],
        ];
        $this->assertEquals($this->dumpTree(), $expected, 'shiftLevel shifts all nodes wit ha negative amount');
    }

    /**
     * @return void
     */
    public function testUpdateLoadedNodes()
    {
        $this->assertTrue(method_exists('NestedSetTable9Query', 'updateLoadedNodes'), 'nested_set adds a updateLoadedNodes() method');
        $fixtures = $this->initTree();
        NestedSetTable9Query::shiftRLValues(1, 5);
        $expected = [
            't1' => [1, 14],
            't2' => [2, 3],
            't3' => [4, 13],
            't4' => [5, 6],
            't5' => [7, 12],
            't6' => [8, 9],
            't7' => [10, 11],
        ];
        $actual = [];
        foreach ($fixtures as $t) {
            $actual[$t->getTitle()] = [$t->getLeftValue(), $t->getRightValue()];
        }
        $this->assertEquals($actual, $expected, 'Loaded nodes are not in sync before calling updateLoadedNodes()');
        NestedSetTable9Query::updateLoadedNodes();
        $expected = [
            't1' => [1, 15],
            't2' => [2, 3],
            't3' => [4, 14],
            't4' => [6, 7],
            't5' => [8, 13],
            't6' => [9, 10],
            't7' => [11, 12],
        ];
        $actual = [];
        foreach ($fixtures as $t) {
            $actual[$t->getTitle()] = [$t->getLeftValue(), $t->getRightValue()];
        }
        $this->assertEquals($actual, $expected, 'Loaded nodes are in sync after calling updateLoadedNodes()');
    }

    /**
     * @return void
     */
    public function testMakeRoomForLeaf()
    {
        $this->assertTrue(method_exists('NestedSetTable9Query', 'makeRoomForLeaf'), 'nested_set adds a makeRoomForLeaf() method');
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $t = NestedSetTable9Query::makeRoomForLeaf(5); // first child of t3
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [4, 15, 1],
            't4' => [7, 8, 2],
            't5' => [9, 14, 2],
            't6' => [10, 11, 3],
            't7' => [12, 13, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'makeRoomForLeaf() shifts the other nodes correctly');
        foreach ($expected as $key => $values) {
            $this->assertEquals($values, [$$key->getLeftValue(), $$key->getRightValue(), $$key->getLevel()], 'makeRoomForLeaf() updates nodes already in memory');
        }
    }

    /**
     * @return void
     */
    public function testFixLevels()
    {
        $fixtures = $this->initTree();
        // reset the levels
        foreach ($fixtures as $node) {
            $node->setLevel(0)->save();
        }
        // fix the levels
        NestedSetTable9Query::fixLevels();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'fixLevels() fixes the levels correctly');

        NestedSetTable9Query::fixLevels();
        $this->assertEquals($expected, $this->dumpTree(), 'fixLevels() can be called several times');
    }
}
