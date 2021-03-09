<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Map\NestedSetTable10TableMap;
use NestedSetTable10;
use NestedSetTable10Query;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;

/**
 * Tests for NestedSetBehaviorQueryBuilderModifier class with scope enabled
 *
 * @author François Zaninotto
 */
class NestedSetBehaviorQueryBuilderModifierWithScopeTest extends TestCase
{
    /**
     * @return void
     */
    public function testTreeRoots()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->treeRoots()
            ->find();
        $this->assertEquals([$t1, $t8], iterator_to_array($objs), 'treeRoots() filters by roots');
    }

    /**
     * @return void
     */
    public function testInTree()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $tree = NestedSetTable10Query::create()
            ->inTree(1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($tree), 'inTree() filters by node');
        $tree = NestedSetTable10Query::create()
            ->inTree(2)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t8, $t9, $t10], iterator_to_array($tree), 'inTree() filters by node');
    }

    /**
     * @return void
     */
    public function testDescendantsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->descendantsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($objs), 'descendantsOf() filters by descendants of the same scope');
    }

    /**
     * @return void
     */
    public function testBranchOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->branchOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($objs), 'branchOf() filters by branch of the same scope');
    }

    /**
     * @return void
     */
    public function testChildrenOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->childrenOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t2, $t3], iterator_to_array($objs), 'childrenOf() filters by children of the same scope');
    }

    /**
     * @return void
     */
    public function testSiblingsOf()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $desc = NestedSetTable10Query::create()
            ->siblingsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals([$t2], iterator_to_array($desc), 'siblingsOf() returns filters by siblings of the same scope');
    }

    /**
     * @todo, fix this test
     *
     * @return void
     */
    public function testAncestorsOf()
    {
        $this->markTestIncomplete();
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->ancestorsOf($t5)
            ->orderByBranch()
            ->find();
        $coll = $this->buildCollection([$t1, $t3]);
        /*
         * FIXME
         * -    'model' => 'Table10'
         * -    'fullyQualifiedModel' => '\Table10'
         * -    'formatter' => null
         * +    'model' => 'NestedSetTable10'
         * +    'fullyQualifiedModel' => '\NestedSetTable10'
         * +    'formatter' => Propel\Runtime\Formatter\ObjectFormatter Object (...)
         */
        //$this->assertEquals($coll, $objs, 'ancestorsOf() filters by ancestors of the same scope');
    }

    /**
     * @todo, fix this test
     *
     * @return void
     */
    public function testRootsOf()
    {
        $this->markTestIncomplete();
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->rootsOf($t5)
            ->orderByBranch()
            ->find();
        $coll = $this->buildCollection([$t1, $t3, $t5]);
        /*
         * FIXME
         * -    'model' => 'Table10'
         * -    'fullyQualifiedModel' => '\Table10'
         * -    'formatter' => null
         * +    'model' => 'NestedSetTable10'
         * +    'fullyQualifiedModel' => '\NestedSetTable10'
         * +    'formatter' => Propel\Runtime\Formatter\ObjectFormatter Object (...)
         */
        //$this->assertEquals($coll, $objs, 'rootsOf() filters by ancestors of the same scope');
    }

    /**
     * @return void
     */
    public function testFindRoot()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'findRoot'), 'nested_set adds a findRoot() method');
        NestedSetTable10Query::create()->deleteAll();
        $this->assertNull(NestedSetTable10Query::create()->findRoot(1), 'findRoot() returns null as long as no root node is defined');
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $this->assertEquals($t1, NestedSetTable10Query::create()->findRoot(1), 'findRoot() returns a tree root');
        $this->assertEquals($t8, NestedSetTable10Query::create()->findRoot(2), 'findRoot() returns a tree root');
    }

    /**
     * @return void
     */
    public function testFindRoots()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $objs = NestedSetTable10Query::create()
            ->findRoots();
        $this->assertEquals([$t1, $t8], iterator_to_array($objs), 'findRoots() returns all root objects');
    }

    /**
     * @return void
     */
    public function testFindTree()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $tree = NestedSetTable10Query::create()->findTree(1);
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
        $tree = NestedSetTable10Query::create()->findTree(2);
        $this->assertEquals([$t8, $t9, $t10], iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
    }

    /**
     * @param array $arr
     *
     * @return \Propel\Runtime\Collection\ObjectCollection
     */
    protected function buildCollection($arr)
    {
        $coll = new ObjectCollection();
        $coll->setData($arr);
        $coll->setModel('Table10');

        return $coll;
    }

    /**
     * @return void
     */
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

        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $this->assertEquals([$t1, $t8], NestedSetTable10Query::retrieveRoots()->getArrayCopy(), 'retrieveRoots() returns the tree roots');
        $c = new Criteria();
        $c->add(NestedSetTable10TableMap::COL_TITLE, 't1');
        $this->assertEquals([$t1], NestedSetTable10Query::retrieveRoots($c)->getArrayCopy(), 'retrieveRoots() accepts a Criteria as first parameter');
    }

    /**
     * @return void
     */
    public function testRetrieveRoot()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'retrieveRoot'), 'nested_set adds a retrieveRoot() method');
        NestedSetTable10TableMap::doDeleteAll();

        $t1 = new NestedSetTable10();
        $t1->setLeftValue(1);
        $t1->setRightValue(2);
        $t1->setScopeValue(2);
        $t1->save();

        $this->assertNull(NestedSetTable10Query::retrieveRoot(1), 'retrieveRoot() returns null as long as no root node is defined in the required scope');

        $t2 = new NestedSetTable10();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $t2->setScopeValue(1);
        $t2->save();

        $this->assertEquals(NestedSetTable10Query::retrieveRoot(1), $t2, 'retrieveRoot() retrieves the root node in the required scope');
    }

    /**
     * @return void
     */
    public function testRetrieveTree()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10] = $this->initTreeWithScope();
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
        $tree = NestedSetTable10Query::retrieveTree(1);
        $this->assertEquals([$t1, $t2, $t3, $t4, $t5, $t6, $t7], $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $tree = NestedSetTable10Query::retrieveTree(2);
        $this->assertEquals([$t8, $t9, $t10], $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $c = new Criteria();
        $c->add(NestedSetTable10::LEFT_COL, 4, Criteria::GREATER_EQUAL);
        $tree = NestedSetTable10Query::retrieveTree(1, $c);
        $this->assertEquals([$t3, $t4, $t5, $t6, $t7], $tree->getArrayCopy(), 'retrieveTree() accepts a Criteria as first parameter');
    }

    /**
     * @return void
     */
    public function testDeleteTree()
    {
        $this->initTreeWithScope();
        NestedSetTable10Query::deleteTree(1);
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'deleteTree() does not delete anything out of the scope');
    }

    /**
     * @return void
     */
    public function testShiftRLValues()
    {
        $this->assertTrue(method_exists('NestedSetTable10Query', 'shiftRLValues'), 'nested_set adds a shiftRLValues() method');
        $this->initTreeWithScope();
        NestedSetTable10Query::shiftRLValues(1, 100, null, 1);
        NestedSetTable10TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues does not shift anything when the first parameter is higher than the highest right value');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        NestedSetTable10Query::shiftRLValues(1, 1, null, 1);
        NestedSetTable10TableMap::clearInstancePool();
        $expected = [
            't1' => [2, 15, 0],
            't2' => [3, 4, 1],
            't3' => [5, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues can shift all nodes to the right');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        NestedSetTable10Query::shiftRLValues(-1, 1, null, 1);
        NestedSetTable10TableMap::clearInstancePool();
        $expected = [
            't1' => [0, 13, 0],
            't2' => [1, 2, 1],
            't3' => [3, 12, 1],
            't4' => [4, 5, 2],
            't5' => [6, 11, 2],
            't6' => [7, 8, 3],
            't7' => [9, 10, 3],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues can shift all nodes to the left');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
        $this->initTreeWithScope();
        NestedSetTable10Query::shiftRLValues(1, 5, null, 1);
        NestedSetTable10TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 15, 0],
            't2' => [2, 3, 1],
            't3' => [4, 14, 1],
            't4' => [6, 7, 2],
            't5' => [8, 13, 2],
            't6' => [9, 10, 3],
            't7' => [11, 12, 3],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftRLValues can shift some nodes to the right');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftRLValues does not shift anything out of the scope');
    }

    /**
     * @return void
     */
    public function testShiftLevel()
    {
        $this->initTreeWithScope();
        NestedSetTable10Query::shiftLevel($delta = 1, $first = 7, $last = 12, $scope = 1);
        NestedSetTable10TableMap::clearInstancePool();
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 3],
            't6' => [8, 9, 4],
            't7' => [10, 11, 4],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'shiftLevel can shift level with a scope');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'shiftLevel does not shift anything out of the scope');
    }

    /**
     * @return void
     */
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
        $t = NestedSetTable10Query::makeRoomForLeaf(5, 1); // first child of t3
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [4, 15, 1],
            't4' => [7, 8, 2],
            't5' => [9, 14, 2],
            't6' => [10, 11, 3],
            't7' => [12, 13, 3],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'makeRoomForLeaf() shifts the other nodes correctly');
        $expected = [
            't8' => [1, 6, 0],
            't9' => [2, 3, 1],
            't10' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'makeRoomForLeaf() does not shift anything out of the scope');
    }
}
