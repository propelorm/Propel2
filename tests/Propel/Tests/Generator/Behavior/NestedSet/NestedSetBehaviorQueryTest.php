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
use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity10EntityMap;
use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10Query;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9Query;

/**
 * Tests for NestedSetBehaviorQuery class
 *
 * @author François Zaninotto
 * @author Cristiano Cinotti
 * @group database
 */
class NestedSetBehaviorQueryTest extends TestCase
{
    public function testDescendantsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->descendantsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array(), iterator_to_array($objs), 'descendantsOf() filters by descendants');
        $objs = NestedSetEntity9Query::create()
            ->descendantsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t4, $t5, $t6, $t7), iterator_to_array($objs), 'descendantsOf() filters by descendants');
    }

    public function testDescendantsOfWithScope()
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
        $objs = NestedSetEntity10Query::create()
            ->descendantsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'descendantsOf() filters by descendants of the same scope');
    }

    public function testBranchOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->branchOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t7), iterator_to_array($objs), 'branchOf() filters by descendants and includes object passed as parameter');
        $objs = NestedSetEntity9Query::create()
            ->branchOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'branchOf() filters by descendants and includes object passed as parameter');
        $objs = NestedSetEntity9Query::create()
            ->branchOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'branchOf() returns the whole tree for the root node');
    }

    public function testBranchOfWithScope()
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
        $objs = NestedSetEntity10Query::create()
            ->branchOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($objs), 'branchOf() filters by branch of the same scope');
    }

    public function testChildrenOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->childrenOf($t6)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array(), iterator_to_array($objs), 'childrenOf() returns empty collection for leaf nodes');
        $objs = NestedSetEntity9Query::create()
            ->childrenOf($t5)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t6, $t7), iterator_to_array($objs), 'childrenOf() filters by children');
        $objs = NestedSetEntity9Query::create()
            ->childrenOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t4, $t5), iterator_to_array($objs), 'childrenOf() filters by children and not by descendants');
    }

    public function testChildrenOfWithScope()
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
        $objs = NestedSetEntity10Query::create()
            ->childrenOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2, $t3), iterator_to_array($objs), 'childrenOf() filters by children of the same scope');
    }

    public function testSiblingsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $desc = NestedSetEntity9Query::create()
            ->siblingsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array(), iterator_to_array($desc), 'siblingsOf() returns empty collection for the root node');
        $desc = NestedSetEntity9Query::create()
            ->siblingsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2), iterator_to_array($desc), 'siblingsOf() filters by siblings');
    }

    public function testSiblingsOfWithScope()
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
        $desc = NestedSetEntity10Query::create()
            ->siblingsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t2), iterator_to_array($desc), 'siblingsOf() returns filters by siblings of the same scope');
    }

    public function testAncestorsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->ancestorsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array(), iterator_to_array($objs), 'ancestorsOf() returns empty collection for root node');
        $objs = NestedSetEntity9Query::create()
            ->ancestorsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1), iterator_to_array($objs), 'ancestorsOf() filters by ancestors');
        $objs = NestedSetEntity9Query::create()
            ->ancestorsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t3, $t5), iterator_to_array($objs), 'childrenOf() filters by ancestors');
    }

    /**
     * @todo, fix this test
     */
    public function testAncestorsOfWithScope()
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
        $objs = NestedSetEntity10Query::create()
            ->ancestorsOf($t5)
            ->orderByBranch()
            ->find();
        $this->assertEquals(iterator_to_array($objs), array($t1, $t3), 'ancestorsOf() filters by ancestors of the same scope');

        $objs = NestedSetEntity10Query::create()
            ->ancestorsOf($t10)
            ->orderByBranch()
            ->find();
        $this->assertEquals(iterator_to_array($objs), array($t8), 'ancestorsOf() filters by ancestors of the same scope');
    }

    public function testRootsOf()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        /* Tree used for tests
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->rootsOf($t1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1), iterator_to_array($objs), 'rootsOf() returns the root node for root node');
        $objs = NestedSetEntity9Query::create()
            ->rootsOf($t3)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t3), iterator_to_array($objs), 'rootsOf() filters by ancestors and includes the node passed as parameter');
        $objs = NestedSetEntity9Query::create()
            ->rootsOf($t7)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t3, $t5, $t7), iterator_to_array($objs), 'rootsOf() filters by ancestors  and includes the node passed as parameter');
    }

    public function testRootsOfWithScope()
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
        $objs = NestedSetEntity10Query::create()
            ->rootsOf($t5)
            ->orderByBranch()
            ->find();
        $this->assertEquals(iterator_to_array($objs), array($t1, $t3, $t5), 'rootsOf() filters by ancestors of the same scope');

        $objs = NestedSetEntity10Query::create()
            ->rootsOf($t9)
            ->orderByBranch()
            ->find();
        $this->assertEquals(iterator_to_array($objs), array($t8, $t9), 'rootsOf() filters by ancestors of the same scope');
    }

    public function testOrderByBranch()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager = $this->getConfiguration()->getRepository(NestedSetEntity9::class)->getNestedManager();
        $manager->moveToPrevSiblingOf($t5, $t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->orderByBranch()
            ->find();

        $this->assertTrue($this->compareObjectsArrays(array($t1, $t2, $t3, $t5, $t6, $t7, $t4), $objs), 'orderByBranch() orders by branch left to right');
        $objs = NestedSetEntity9Query::create()
            ->orderByBranch(true)
            ->find();
        $this->assertTrue($this->compareObjectsArrays(array($t4, $t7, $t6, $t5, $t3, $t2, $t1), $objs), 'orderByBranch(true) orders by branch right to left');
    }

    public function testOrderByLevel()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager = $this->getConfiguration()->getRepository(NestedSetEntity9::class)->getNestedManager();
        $manager->moveToPrevSiblingOf($t5, $t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $objs = NestedSetEntity9Query::create()
            ->orderByLevel()
            ->find();

        $this->assertTrue($this->compareObjectsArrays(array($t1, $t2, $t3, $t5, $t4, $t6, $t7), iterator_to_array($objs)), 'orderByLevel() orders by level, from the root to the leaf');

        $objs = NestedSetEntity9Query::create()
            ->orderByLevel(true)
            ->find();

        $this->assertTrue($this->compareObjectsArrays(array($t7, $t6, $t4, $t5, $t3, $t2, $t1), iterator_to_array($objs)), 'orderByLevel() orders by level, from the root to the leaf');
    }

    public function testFindRoot()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();
        $this->assertNull($repository->createQuery()->findRoot(), 'findRoot() returns null as long as no root node is defined');

        $t1 = new NestedSetEntity9();
        $t1->setLeftValue(123);
        $t1->setRightValue(456);
        $repository->save($t1);
        $this->assertNull($repository->createQuery()->findRoot(), 'findRoot() returns null as long as no root node is defined');

        $t2 = new NestedSetEntity9();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $repository->save($t2);
        $this->assertEquals($repository->createQuery()->findRoot(), $t2, 'findRoot() retrieves the root node');
    }

    public function testFindRootWithScope()
    {
        $this->assertTrue(method_exists(NestedSetEntity10Query::class, 'findRoot'), 'nested_set adds a findRoot() method');
        NestedSetEntity10Query::create()->deleteAll();
        $this->assertNull(NestedSetEntity10Query::create()->findRoot(1), 'findRoot() returns null as long as no root node is defined');
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
        $this->assertEquals($t1, NestedSetEntity10Query::create()->findRoot(1), 'findRoot() returns a tree root');
        $this->assertEquals($t8, NestedSetEntity10Query::create()->findRoot(2), 'findRoot() returns a tree root');
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
        $objs = NestedSetEntity10Query::create()
            ->findRoots();
        $this->assertEquals(array($t1, $t8), iterator_to_array($objs), 'findRoots() returns all root objects');
    }

    public function testfindTree()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $tree = NestedSetEntity9Query::create()->findTree();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($tree), 'findTree() retrieves the whole tree, ordered by branch');
    }

    public function testFindTreeWithScope()
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
        $tree = NestedSetEntity10Query::create()->findTree(1);
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
        $tree = NestedSetEntity10Query::create()->findTree(2);
        $this->assertEquals(array($t8, $t9, $t10), iterator_to_array($tree), 'findTree() retrieves the tree of a scope, ordered by branch');
    }

    public function testRetrieveRoot()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();
        $this->assertNull($repository->createQuery()->retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');

        $t1 = new NestedSetEntity9();
        $t1->setLeftValue(123);
        $t1->setRightValue(456);
        $repository->save($t1);

        $this->assertNull($repository->createQuery()->retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');

        $t2 = new NestedSetEntity9();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $repository->save($t2);

        $this->assertEquals($repository->createQuery()->retrieveRoot(), $t2, 'retrieveRoot() retrieves the root node');
    }

    public function testRetrieveRootWithScope()
    {
        $this->assertTrue(method_exists(NestedSetEntity10Query::class, 'retrieveRoot'), 'nested_set adds a retrieveRoot() method');
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity10();
        $t1->setLeftValue(1);
        $t1->setRightValue(2);
        $t1->setScopeValue(2);
        $repository->save($t1);

        $this->assertNull($repository->createQuery()->retrieveRoot(1), 'retrieveRoot() returns null as long as no root node is defined in the required scope');

        $t2 = new NestedSetEntity10();
        $t2->setLeftValue(1);
        $t2->setRightValue(2);
        $t2->setScopeValue(1);
        $repository->save($t2);

        $this->assertEquals($repository->createQuery()->retrieveRoot(1), $t2, 'retrieveRoot() retrieves the root node in the required scope');
    }

    public function testRetrieveRoots()
    {
        $this->assertTrue(
            method_exists(NestedSetEntity10Query::class, 'retrieveRoots'),
            'nested_set adds a retrieveRoots() method for trees that use scope'
        );
        $this->assertFalse(
            method_exists('NestedSetEntity9Query', 'retrieveRoots'),
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
        $this->assertEquals(array($t1, $t8), NestedSetEntity10Query::create()->retrieveRoots()->getArrayCopy(), 'retrieveRoots() returns the tree roots');
        $c = new Criteria(NestedSetEntity10EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity10EntityMap::FIELD_TITLE, 't1');
        $this->assertEquals(array($t1), NestedSetEntity10Query::create()->retrieveRoots($c)->getArrayCopy(), 'retrieveRoots() accepts a Criteria as first parameter');
    }

    public function testRetrieveTree()
    {
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $tree = NestedSetEntity9Query::create()->retrieveTree()->getArrayCopy();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), $tree, 'retrieveTree() retrieves the whole tree');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::LEFT_COL, 4, Criteria::GREATER_EQUAL);
        $tree = NestedSetEntity9Query::create()->retrieveTree($c)->getArrayCopy();
        $this->assertEquals(array($t3, $t4, $t5, $t6, $t7), $tree, 'retrieveTree() accepts a Criteria as first parameter');
    }

    public function testRetrieveTreeWithScope()
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
        $tree = NestedSetEntity10Query::create()->retrieveTree(1);
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $tree = NestedSetEntity10Query::create()->retrieveTree(2);
        $this->assertEquals(array($t8, $t9, $t10), $tree->getArrayCopy(), 'retrieveTree() retrieves the scoped tree');
        $c = new Criteria(NestedSetEntity10EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity10EntityMap::LEFT_COL, 4, Criteria::GREATER_EQUAL);
        $tree = NestedSetEntity10Query::create()->retrieveTree(1, $c);
        $this->assertEquals(array($t3, $t4, $t5, $t6, $t7), $tree->getArrayCopy(), 'retrieveTree() accepts a Criteria as first parameter');
    }

    public function testDeleteTree()
    {
        $this->initTree();
        NestedSetEntity9Query::create()->deleteTree();
        $this->assertCount(0, NestedSetEntity9Query::create()->find(), 'deleteTree() deletes the whole tree');
    }

    public function testDeleteTreeWithScope()
    {
        $this->initTreeWithScope();
        NestedSetEntity10Query::create()->deleteTree(1);
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'deleteTree() does not delete anything out of the scope');
    }

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
        $objs = NestedSetEntity10Query::create()
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
        $tree = NestedSetEntity10Query::create()
            ->inTree(1)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t1, $t2, $t3, $t4, $t5, $t6, $t7), iterator_to_array($tree), 'inTree() filters by node');
        $tree = NestedSetEntity10Query::create()
            ->inTree(2)
            ->orderByBranch()
            ->find();
        $this->assertEquals(array($t8, $t9, $t10), iterator_to_array($tree), 'inTree() filters by node');
    }
    
    /*
     * Useful method to compare an array of objects and an array of proxies objects.
     */
    private function compareObjectsArrays($objs1, $objs2)
    {
        foreach($objs1 as $key => $value) {
            $res = $objs1[$key]->getId() === $objs2[$key]->getId() &&
                $objs1[$key]->getLeftValue() === $objs2[$key]->getLeftValue() &&
                $objs1[$key]->getRightValue() === $objs2[$key]->getRightValue() &&
                $objs1[$key]->getLevel() === $objs2[$key]->getLevel();
            if (method_exists($objs1[$key], 'getScopeValue')) {
                $res = $res && ($objs1[$key]->getScopeValue() === $objs2[$key]->getScopeValue());
            }

            if (!$res) {
                return false;
            }
        }

        return true;
    }
}
