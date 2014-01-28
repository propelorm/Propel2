<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;

/**
 * Tests for NestedSetBehaviorQueryBuilderModifier class with scope enabled
 *
 * @author François Zaninotto
 */
class NestedSetBehaviorQueryBuilderModifierWithScopeTest extends TestCase
{
    public function testTreeRoots()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->treeRoots()
            ->find();
        $this->assertEquals(array($t1, $t8), iterator_to_array($objs), 'treeRoots() filters by roots');
    }

    public function testInTree()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $tree = \NestedSetTable10Query::create()
            ->inTree(1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($tree), 'inTree() filters by node');
        $tree = \NestedSetTable10Query::create()
            ->inTree(2)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t8, $t9, $t10), iterator_to_array($tree), 'inTree() filters by node');
    }

    public function testDescendantsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->descendantsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'descendantsOf() filters by descendants of the same scope');
    }

    public function testBranchOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->branchOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'branchOf() filters by branch of the same scope');

    }

    public function testChildrenOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->childrenOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2, $t3), iterator_to_array($objs), 'childrenOf() filters by children of the same scope');
    }

    public function testSiblingsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $desc = \NestedSetTable10Query::create()
            ->siblingsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2), iterator_to_array($desc), 'siblingsOf() returns filters by siblings of the same scope');
    }

    /**
     * @todo, fix this test
     */
    public function testAncestorsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->ancestorsOf($t5)
            ->orderByBranch()
            ->find();
        $coll = $this->buildCollection(array($t1, $t3), 'ancestorsOf() filters by ancestors of the same scope');
    }

    /**
     * @todo, fix this test
     */
    public function testRootsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->rootsOf($t5)
            ->orderByBranch()
            ->find();
        $coll = $this->buildCollection(array($t1, $t3, $t5), 'rootsOf() filters by ancestors of the same scope');
    }

    public function testFindRoot()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'findRoot'), 'nested_set adds a findRoot() method');
        \NestedSetTable10Query::create()->deleteAll();
        $this->assertNull(\NestedSetTable10Query::create()->findRoot(1), 'findRoot() returns null as long as no root node is defined');
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $this->assertEquals($t1, \NestedSetTable10Query::create()->findRoot(1), 'findRoot() returns a tree root');
        $this->assertEquals($t8, \NestedSetTable10Query::create()->findRoot(2), 'findRoot() returns a tree root');
    }

    public function testFindRoots()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $objs = \NestedSetTable10Query::create()
            ->findRoots();
        $this->assertEquals(array($t1, $t8), iterator_to_array($objs), 'findRoots() returns all root objects');
    }

    public function testFindTree()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
        */
        $tree = \NestedSetTable10Query::create()->findTree(1);
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
        $tree = \NestedSetTable10Query::create()->findTree(2);
        $this->assertEquals(array($t8, $t9, $t10), iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
    }

    protected function buildCollection($arr)
    {
        $coll = new ObjectCollection();
        $coll->setData($arr);
        $coll->setModel('Table10');

        return $coll;
    }

    public function testRetrieveRoots()
    {
        $this->assertTrue(
            method_exists('NestedSetTable10Query', 'retrieveRoots'),
            'nested_set adds a retrieveRoots() method for trees that use scope'
        );
        $this->assertFalse(
            method_exists('NestedSetTable9Query', 'retrieveRoots'),
            "nested_set does not add a retrieveRoots() method for trees that don't use scope"
        );

        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
         */
        $this->assertEquals(array($t1, $t8), \NestedSetTable10Query::retrieveRoots()->getArrayCopy(), 'retrieveRoots() returns the tree roots');
        $c = new Criteria();
        $c->add(\Map\NestedSetTable10TableMap::COL_TITLE, 't1');
        $this->assertEquals(array($t1), \NestedSetTable10Query::retrieveRoots($c)->getArrayCopy(), 'retrieveRoots() accepts a Criteria as first parameter');
    }

    public function testRetrieveRoot()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'retrieveRoot'), 'nested_set adds a retrieveRoot() method');
        \Map\NestedSetTable10TableMap::doDeleteAll();

        $t1 = new \NestedSetTable10();
        $t1->setLeftValue(1);
        $t1->setRightValue(2);
        $t1->setScopeValue(2);
        $t1->save();

        $this->assertNull(\NestedSetTable10Query::retrieveRoot(1), 'retrieveRoot() returns null as long as no root node is defined in the required scope');

        $t2 = new \NestedSetTable10();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $t2->setScopeValue(1);
        $t2->save();

        $this->assertEquals(\NestedSetTable10Query::retrieveRoot(1), $t2, 'retrieveRoot() retrieves the root node in the required scope');
    }

    public function testRetrieveTree()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
         */
        $tree = \NestedSetTable10Query::retrieveTree(1);
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $tree = \NestedSetTable10Query::retrieveTree(2);
        $this->assertEquals(array($t8, $t9, $t10), $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $c = new Criteria();
        $c->add(\NestedSetTable10::LEFT_COL, 4, Criteria::GREATER_EQUAL);
        $tree = \NestedSetTable10Query::retrieveTree(1, $c);
        $this->assertEquals(array($t3, $t4, $t5, $t6, $t7), $tree->getArrayCopy(), 'retrieveTree() accepts a Criteria as first parameter');
    }

    public function testDeleteTree()
    {
        $this->initTreeWithScope();
        \NestedSetTable10Query::deleteTree(1);
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'deleteTree() does not delete anything out of the scope');
    }

    public function testShiftRLValues()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'shiftRLValues'), 'nested_set adds a shiftRLValues() method');
        $this->initTreeWithScope();
        \NestedSetTable10Query::shiftRLValues(1, 100, null, 1);
        \Map\NestedSetTable10TableMap::clearInstancePool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues does not shift anything when the first parameter is higher than the highest right value');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        \NestedSetTable10Query::shiftRLValues(1, 1, null, 1);
        \Map\NestedSetTable10TableMap::clearInstancePool();
        $expected = array(
            't1' => array(2, 15, 0),
            't2' => array(3, 4, 1),
            't3' => array(5, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues can shift all nodes to the right');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        \NestedSetTable10Query::shiftRLValues(-1, 1, null, 1);
        \Map\NestedSetTable10TableMap::clearInstancePool();
        $expected = array(
            't1' => array(0, 13, 0),
            't2' => array(1, 2, 1),
            't3' => array(3, 12, 1),
            't4' => array(4, 5, 2),
            't5' => array(6, 11, 2),
            't6' => array(7, 8, 3),
            't7' => array(9, 10, 3),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1),'shiftRLValues can shift all nodes to the left');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        \NestedSetTable10Query::shiftRLValues(1, 5, null, 1);
        \Map\NestedSetTable10TableMap::clearInstancePool();
        $expected = array(
            't1' => array(1, 15, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues can shift some nodes to the right');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
    }

    public function testShiftLevel()
    {
        $this->initTreeWithScope();
        \NestedSetTable10Query::shiftLevel($delta = 1, $first = 7, $last = 12, $scope = 1);
        \Map\NestedSetTable10TableMap::clearInstancePool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 3),
            't6' => array(8, 9, 4),
            't7' => array(10, 11, 4),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftLevel can shift level with a scope');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftLevel does not shift anything out of the scope');
    }

    public function testMakeRoomForLeaf()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'makeRoomForLeaf'), 'nested_set adds a makeRoomForLeaf() method');
        $fixtures = $this->initTreeWithScope();
        /* Tree used for tests
         Scope 1
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
         Scope 2
         t8
         | \
         t9 t10
         */
        $t = \NestedSetTable10Query::makeRoomForLeaf(5, 1); // first child of t3
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'makeRoomForLeaf() shifts the other nodes correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'makeRoomForLeaf() does not shift anything out of the scope');
    }
}
