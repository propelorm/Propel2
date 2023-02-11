<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Exception;
use Map\NestedSetTable9TableMap;
use NestedSetTable10;
use NestedSetTable9;
use NestedSetTable9Query;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Tests\Fixtures\Generator\Behavior\NestedSet\PublicTable9;

/**
 * Tests for NestedSetBehaviorObjectBuilderModifier class
 */
class NestedSetBehaviorObjectBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function testDefault()
    {
        $t = new NestedSetTable9();
        $t->setTreeLeft('123');
        $this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
        $t->setTreeRight('456');
        $this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
        $t->setLevel('789');
        $this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
    }

    /**
     * @return void
     */
    public function testParameters()
    {
        $t = new NestedSetTable10();
        $t->setMyLeftColumn('123');
        $this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
        $t->setMyRightColumn('456');
        $this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
        $t->setMyLevelColumn('789');
        $this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
        $t->setMyScopeColumn('012');
        $this->assertEquals($t->getScopeValue(), '012', 'nested_set adds a getScopeValue() method');
    }

    /**
     * @return void
     */
    public function testObjectAttributes()
    {
        $expectedAttributes = ['nestedSetQueries'];
        foreach ($expectedAttributes as $attribute) {
            $this->assertClassHasAttribute($attribute, 'NestedSetTable9');
        }
    }

    /**
     * @return void
     */
    public function testSaveOutOfTree()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1');
        try {
            $t1->save();
            $this->assertTrue(true, 'A node can be saved without valid tree information');
        } catch (Exception $e) {
            $this->fail('A node can be saved without valid tree information');
        }
        try {
            $t1->makeRoot();
            $this->assertTrue(true, 'A saved node can be turned into root');
        } catch (Exception $e) {
            $this->fail('A saved node can be turned into root');
        }
        $t1->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t1');
        $t2->save();
        try {
            $t2->insertAsFirstChildOf($t1);
            $this->assertTrue(true, 'A saved node can be inserted into the tree');
        } catch (Exception $e) {
            $this->fail('A saved node can be inserted into the tree');
        }
        try {
            $t2->save();
            $this->assertTrue(true, 'A saved node can be inserted into the tree');
        } catch (Exception $e) {
            $this->fail('A saved node can be inserted into the tree');
        }
    }

    /**
     * @return void
     */
    public function testSaveRootInTreeWithExistingRoot()
    {
        $this->expectException(PropelException::class);

        NestedSetTable9TableMap::doDeleteAll();
        $t1 = new NestedSetTable9();
        $t1->makeRoot();
        $t1->save();
        $t2 = new NestedSetTable9();
        $t2->makeRoot();
        $t2->save();
    }

    /**
     * @return void
     */
    public function testPreUpdate()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t3->setLeftValue(null);
        try {
            $t3->save();
            $this->fail('Trying to save a node incorrectly updated throws an exception');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Trying to save a node incorrectly updated throws an exception');
        }
    }

    /**
     * @return void
     */
    public function testDelete()
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
        $t5->delete();
        $this->assertEquals(13, $t3->getRightValue(), 'delete() does not update existing nodes (because delete() clears the instance cache)');
        $expected = [
            't1' => [1, 8, 0],
            't2' => [2, 3, 1],
            't3' => [4, 7, 1],
            't4' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'delete() deletes all descendants and shifts the entire subtree correctly');
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        try {
            $t1->delete();
            $this->fail('delete() throws an exception when called on a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'delete() throws an exception when called on a root node');
        }
        $this->assertNotEquals([], NestedSetTable9Query::create()->find(), 'delete() called on the root node does not delete the whole tree');
    }

    /**
     * @return void
     */
    public function testDeleteNotInTree()
    {
        $t1 = new NestedSetTable9();
        $t1->save();
        $t1->delete();
        $this->assertTrue($t1->isDeleted());
    }

    /**
     * @return void
     */
    public function testMakeRoot()
    {
        $t = new NestedSetTable9();
        $t->makeRoot();
        $this->assertEquals($t->getLeftValue(), 1, 'makeRoot() initializes left_column to 1');
        $this->assertEquals($t->getRightValue(), 2, 'makeRoot() initializes right_column to 2');
        $this->assertEquals($t->getLevel(), 0, 'makeRoot() initializes right_column to 0');
        $t = new NestedSetTable9();
        $t->setLeftValue(12);
        try {
            $t->makeRoot();
            $this->fail('makeRoot() throws an exception when called on an object with a left_column value');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'makeRoot() throws an exception when called on an object with a left_column value');
        }
    }

    /**
     * @return void
     */
    public function testIsInTree()
    {
        $t1 = new NestedSetTable9();
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with no left and right value');
        $t1->save();
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for saved nodes with no left and right value');
        $t1->setLeftValue(1)->setRightValue(0);
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with zero left value');
        $t1->setLeftValue(0)->setRightValue(1);
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with zero right value');
        $t1->setLeftValue(1)->setRightValue(1);
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with equal left and right value');
        $t1->setLeftValue(1)->setRightValue(2);
        $this->assertTrue($t1->isInTree(), 'inInTree() returns true for nodes with left < right value');
        $t1->setLeftValue(2)->setRightValue(1);
        $this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with left > right value');
    }

    /**
     * @return void
     */
    public function testIsRoot()
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
        $this->assertTrue($t1->isRoot(), 'root is seen as root');
        $this->assertFalse($t2->isRoot(), 'leaf is not seen as root');
        $this->assertFalse($t3->isRoot(), 'node is not seen as root');
    }

    /**
     * @return void
     */
    public function testIsLeaf()
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
        $this->assertFalse($t1->isLeaf(), 'root is not seen as leaf');
        $this->assertTrue($t2->isLeaf(), 'leaf is seen as leaf');
        $this->assertFalse($t3->isLeaf(), 'node is not seen as leaf');
    }

    /**
     * @return void
     */
    public function testIsDescendantOf()
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
        $this->assertFalse($t1->isDescendantOf($t1), 'root is not seen as a descendant of root');
        $this->assertTrue($t2->isDescendantOf($t1), 'direct child is seen as a descendant of root');
        $this->assertFalse($t1->isDescendantOf($t2), 'root is not seen as a descendant of leaf');
        $this->assertTrue($t5->isDescendantOf($t1), 'grandchild is seen as a descendant of root');
        $this->assertTrue($t5->isDescendantOf($t3), 'direct child is seen as a descendant of node');
        $this->assertFalse($t3->isDescendantOf($t5), 'node is not seen as a descendant of its parent');
    }

    /**
     * @return void
     */
    public function testIsAncestorOf()
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
        $this->assertFalse($t1->isAncestorOf($t1), 'root is not seen as an ancestor of root');
        $this->assertTrue($t1->isAncestorOf($t2), 'root is seen as an ancestor of direct child');
        $this->assertFalse($t2->isAncestorOf($t1), 'direct child is not seen as an ancestor of root');
        $this->assertTrue($t1->isAncestorOf($t5), 'root is seen as an ancestor of grandchild');
        $this->assertTrue($t3->isAncestorOf($t5), 'parent is seen as an ancestor of node');
        $this->assertFalse($t5->isAncestorOf($t3), 'child is not seen as an ancestor of its parent');
    }

    /**
     * @return void
     */
    public function testHasParent()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t0 = new NestedSetTable9();
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->setLevel(0)->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->setLevel(1)->save();
        $t3 = new NestedSetTable9();
        $t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->setLevel(2)->save();
        $this->assertFalse($t0->hasParent(), 'empty node has no parent');
        $this->assertFalse($t1->hasParent(), 'root node has no parent');
        $this->assertTrue($t2->hasParent(), 'not root node has a parent');
        $this->assertTrue($t3->hasParent(), 'leaf node has a parent');
    }

    /**
     * @return void
     */
    public function testGetParent()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t0 = new NestedSetTable9();
        $this->assertFalse($t0->hasParent(), 'empty node has no parent');
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(8)->setLevel(0)->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(7)->setLevel(1)->save();
        $t3 = new NestedSetTable9();
        $t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->setLevel(2)->save();
        $t4 = new NestedSetTable9();
        $t4->setTitle('t4')->setLeftValue(5)->setRightValue(6)->setLevel(2)->save();
        $this->assertNull($t1->getParent($this->con), 'getParent() return null for root nodes');
        $this->assertEquals($t2->getParent($this->con), $t1, 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t3->getParent($this->con), $t2, 'getParent() correctly retrieves parent for leafs');
        $this->assertEquals($t4->getParent($this->con), $t2, 'getParent() retrieves the same parent for two siblings');
    }

    /**
     * @return void
     */
    public function testGetParentCache()
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
        $con = Propel::getServiceContainer()->getReadConnection(NestedSetTable9TableMap::DATABASE_NAME);
        $con->useDebug();

        $count = $con->getQueryCount();
        $parent = $t5->getParent($con);

        $this->assertEquals($count + 1, $con->getQueryCount(), 'getParent() only issues a query once');
        $this->assertEquals('t3', $parent->getTitle(), 'getParent() returns the parent Node');
    }

    /**
     * @return void
     */
    public function testHasPrevSibling()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t0 = new NestedSetTable9();
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(3)->save();
        $t3 = new NestedSetTable9();
        $t3->setTitle('t3')->setLeftValue(4)->setRightValue(5)->save();
        $this->assertFalse($t0->hasPrevSibling(), 'empty node has no previous sibling');
        $this->assertFalse($t1->hasPrevSibling(), 'root node has no previous sibling');
        $this->assertFalse($t2->hasPrevSibling(), 'first sibling has no previous sibling');
        $this->assertTrue($t3->hasPrevSibling(), 'not first sibling has a previous sibling');
    }

    /**
     * @return void
     */
    public function testGetPrevSibling()
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
        $this->assertNull($t1->getPrevSibling($this->con), 'getPrevSibling() returns null for root nodes');
        $this->assertNull($t2->getPrevSibling($this->con), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($t3->getPrevSibling($this->con), $t2, 'getPrevSibling() correctly retrieves prev sibling');
        $this->assertNull($t6->getPrevSibling($this->con), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($t7->getPrevSibling($this->con), $t6, 'getPrevSibling() correctly retrieves prev sibling');
    }

    /**
     * @return void
     */
    public function testHasNextSibling()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t0 = new NestedSetTable9();
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(3)->save();
        $t3 = new NestedSetTable9();
        $t3->setTitle('t3')->setLeftValue(4)->setRightValue(5)->save();
        $this->assertFalse($t0->hasNextSibling(), 'empty node has no next sibling');
        $this->assertFalse($t1->hasNextSibling(), 'root node has no next sibling');
        $this->assertTrue($t2->hasNextSibling(), 'not last sibling has a next sibling');
        $this->assertFalse($t3->hasNextSibling(), 'last sibling has no next sibling');
    }

    /**
     * @return void
     */
    public function testGetNextSibling()
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
        $this->assertNull($t1->getNextSibling($this->con), 'getNextSibling() returns null for root nodes');
        $this->assertEquals($t2->getNextSibling($this->con), $t3, 'getNextSibling() correctly retrieves next sibling');
        $this->assertNull($t3->getNextSibling($this->con), 'getNextSibling() returns null for last siblings');
        $this->assertEquals($t6->getNextSibling($this->con), $t7, 'getNextSibling() correctly retrieves next sibling');
        $this->assertNull($t7->getNextSibling($this->con), 'getNextSibling() returns null for last siblings');
    }

    /**
     * @return void
     */
    public function testAddNestedSetChildren()
    {
        $t0 = new NestedSetTable9();
        $t1 = new NestedSetTable9();
        $t2 = new NestedSetTable9();
        $t0->addNestedSetChild($t1);
        $t0->addNestedSetChild($t2);
        $this->assertEquals(2, $t0->countChildren(), 'addNestedSetChild() adds the object to the internal children collection');
        $this->assertEquals($t0, $t1->getParent(), 'addNestedSetChild() sets the object as th parent of the parameter');
        $this->assertEquals($t0, $t2->getParent(), 'addNestedSetChild() sets the object as th parent of the parameter');
    }

    /**
     * @return void
     */
    public function testHasChildren()
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
        $this->assertTrue($t1->hasChildren(), 'root has children');
        $this->assertFalse($t2->hasChildren(), 'leaf has no children');
        $this->assertTrue($t3->hasChildren(), 'node has children');
    }

    /**
     * @return void
     */
    public function testGetChildren()
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
        $this->assertTrue($t2->getChildren() instanceof ObjectCollection, 'getChildren() returns a collection');
        $this->assertEquals(0, count($t2->getChildren()), 'getChildren() returns an empty collection for leafs');
        $children = $t3->getChildren();
        $expected = [
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() returns a collection of children');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't5');
        $children = $t3->getChildren($c);
        $expected = [
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() accepts a criteria as parameter');
    }

    /**
     * @return void
     */
    public function testGetChildrenCache()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $con = Propel::getServiceContainer()->getReadConnection(NestedSetTable9TableMap::DATABASE_NAME);
        $count = $con->getQueryCount();
        $children = $t3->getChildren(null, $con);
        $this->assertEquals($count + 1, $con->getQueryCount(), 'getChildren() only issues a query once');
        $expected = [
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() returns a collection of children');
        // when using criteria, cache is not used
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't5');
        $children = $t3->getChildren($c, $con);
        $this->assertEquals($count + 2, $con->getQueryCount(), 'getChildren() issues a new query when âssed a non-null Criteria');
        $expected = [
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() accepts a criteria as parameter');
        // but not erased either
        $children = $t3->getChildren(null, $con);
        $this->assertEquals($count + 2, $con->getQueryCount(), 'getChildren() keeps its internal cache after being called with a Criteria');
        $expected = [
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() returns a collection of children');
    }

    /**
     * @return void
     */
    public function testCountChildren()
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
        $this->assertEquals(0, $t2->countChildren(), 'countChildren() returns 0 for leafs');
        $this->assertEquals(2, $t3->countChildren(), 'countChildren() returns the number of children');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't5');
        $this->assertEquals(1, $t3->countChildren($c), 'countChildren() accepts a criteria as parameter');
    }

    /**
     * @return void
     */
    public function testCountChildrenCache()
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
        $con = Propel::getServiceContainer()->getReadConnection(NestedSetTable9TableMap::DATABASE_NAME);
        $count = $con->getQueryCount();

        $children = $t3->getChildren(null, $con);
        $nbChildren = $t3->countChildren(null, $con);
        $this->assertEquals($count + 1, $con->getQueryCount(), 'countChildren() uses the internal collection when passed no Criteria');

        $nbChildren = $t3->countChildren(new Criteria(), $con);
        $this->assertEquals($count + 2, $con->getQueryCount(), 'countChildren() issues a new query when passed a Criteria');
    }

    /**
     * @return void
     */
    public function testGetFirstChild()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToNextSiblingOf($t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $this->assertEquals($t2, $t1->getFirstChild(), 'getFirstChild() returns the first child');
        $this->assertNull($t2->getFirstChild(), 'getFirstChild() returns null for leaf');
    }

    /**
     * @return void
     */
    public function testGetLastChild()
    {
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToNextSiblingOf($t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $this->assertEquals($t5, $t1->getLastChild(), 'getLastChild() returns the last child');
        $this->assertNull($t2->getLastChild(), 'getLastChild() returns null for leaf');
    }

    /**
     * @return void
     */
    public function testGetSiblings()
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
        $this->assertEquals([], $t1->getSiblings(), 'getSiblings() returns an empty array for root');
        $siblings = $t5->getSiblings();
        $expected = [
            't4' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings() returns an array of siblings');
        $siblings = $t5->getSiblings(true);
        $expected = [
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings(true) includes the current node');
        $t5->moveToNextSiblingOf($t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $this->assertEquals(0, count($t4->getSiblings()), 'getSiblings() returns an empty colleciton for lone children');
        $siblings = $t3->getSiblings();
        $expected = [
            't2' => [2, 3, 1],
            't5' => [8, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings() returns all siblings');
        $this->assertEquals('t2', $siblings[0]->getTitle(), 'getSiblings() returns siblings in natural order');
        $this->assertEquals('t5', $siblings[1]->getTitle(), 'getSiblings() returns siblings in natural order');
    }

    /**
     * @return void
     */
    public function testGetDescendants()
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
        $this->assertEquals([], $t2->getDescendants(), 'getDescendants() returns an empty array for leafs');
        $descendants = $t3->getDescendants();
        $expected = [
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() returns an array of descendants');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't5');
        $descendants = $t3->getDescendants($c);
        $expected = [
            't5' => [7, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() accepts a criteria as parameter');
    }

    /**
     * @return void
     */
    public function testCountDescendants()
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
        $this->assertEquals(0, $t2->countDescendants(), 'countDescendants() returns 0 for leafs');
        $this->assertEquals(4, $t3->countDescendants(), 'countDescendants() returns the number of descendants');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't5');
        $this->assertEquals(1, $t3->countDescendants($c), 'countDescendants() accepts a criteria as parameter');
    }

    /**
     * @return void
     */
    public function testGetBranch()
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
        $this->assertEquals([$t2], $t2->getBranch()->getArrayCopy(), 'getBranch() returns the current node for leafs');
        $descendants = $t3->getBranch();
        $expected = [
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() returns an array of descendants, including the current node');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't3', Criteria::NOT_EQUAL);
        $descendants = $t3->getBranch($c);
        unset($expected['t3']);
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() accepts a criteria as first parameter');
    }

    /**
     * @return void
     */
    public function testGetAncestors()
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
        $this->assertEquals([], $t1->getAncestors(), 'getAncestors() returns an empty array for roots');
        $ancestors = $t5->getAncestors();
        $expected = [
            't1' => [1, 14, 0],
            't3' => [4, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() returns an array of ancestors');
        $c = new Criteria();
        $c->add(NestedSetTable9TableMap::COL_TITLE, 't3');
        $ancestors = $t5->getAncestors($c);
        $expected = [
            't3' => [4, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() accepts a criteria as parameter');
    }

    /**
     * @return void
     */
    public function testAddChild()
    {
        NestedSetTable9TableMap::doDeleteAll();
        $t1 = new NestedSetTable9();
        $t1->setTitle('t1');
        $t1->makeRoot();
        $t1->save();
        $t2 = new NestedSetTable9();
        $t2->setTitle('t2');
        $t1->addChild($t2);
        $t2->save();
        $t3 = new NestedSetTable9();
        $t3->setTitle('t3');
        $t1->addChild($t3);
        $t3->save();
        $t4 = new NestedSetTable9();
        $t4->setTitle('t4');
        $t2->addChild($t4);
        $t4->save();
        $expected = [
            't1' => [1, 8, 0],
            't2' => [4, 7, 1],
            't3' => [2, 3, 1],
            't4' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'addChild() adds the child and saves it');
    }

    /**
     * @return void
     */
    public function testInsertAsFirstChildOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'insertAsFirstChildOf'), 'nested_set adds a insertAsFirstChildOf() method');
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
        $t8 = new PublicTable9();
        $t8->setTitle('t8');
        $t = $t8->insertAsFirstChildOf($t3);
        $this->assertEquals($t8, $t, 'insertAsFirstChildOf() returns the object it was called on');
        $this->assertEquals(5, $t4->getLeftValue(), 'insertAsFirstChildOf() does not modify the tree until the object is saved');
        $t8->save();
        $this->assertEquals(5, $t8->getLeftValue(), 'insertAsFirstChildOf() sets the left value correctly');
        $this->assertEquals(6, $t8->getRightValue(), 'insertAsFirstChildOf() sets the right value correctly');
        $this->assertEquals(2, $t8->getLevel(), 'insertAsFirstChildOf() sets the level correctly');
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [4, 15, 1],
            't4' => [7, 8, 2],
            't5' => [9, 14, 2],
            't6' => [10, 11, 3],
            't7' => [12, 13, 3],
            't8' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsFirstChildOf() shifts the other nodes correctly');
        try {
            $t8->insertAsFirstChildOf($t4);
            $this->fail('insertAsFirstChildOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsFirstChildOf() throws an exception when called on a saved object');
        }
    }

    /**
     * @return void
     */
    public function testInsertAsFirstChildOfExistingObject()
    {
        NestedSetTable9Query::create()->deleteAll();
        $t = new NestedSetTable9();
        $t->makeRoot();
        $t->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $t1 = new NestedSetTable9();
        $t1->save();
        $t1->insertAsFirstChildOf($t);
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $t1->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    /**
     * @return void
     */
    public function testInsertAsLastChildOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'insertAsLastChildOf'), 'nested_set adds a insertAsLastChildOf() method');
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
        $t8 = new PublicTable9();
        $t8->setTitle('t8');
        $t = $t8->insertAsLastChildOf($t3);
        $this->assertEquals($t8, $t, 'insertAsLastChildOf() returns the object it was called on');
        $this->assertEquals(13, $t3->getRightValue(), 'insertAsLastChildOf() does not modify the tree until the object is saved');
        $t8->save();
        $this->assertEquals(13, $t8->getLeftValue(), 'insertAsLastChildOf() sets the left value correctly');
        $this->assertEquals(14, $t8->getRightValue(), 'insertAsLastChildOf() sets the right value correctly');
        $this->assertEquals(2, $t8->getLevel(), 'insertAsLastChildOf() sets the level correctly');
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [4, 15, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
            't8' => [13, 14, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsLastChildOf() shifts the other nodes correctly');
        try {
            $t8->insertAsLastChildOf($t4);
            $this->fail('insertAsLastChildOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsLastChildOf() throws an exception when called on a saved object');
        }
    }

    /**
     * @return void
     */
    public function testInsertAsLastChildOfExistingObject()
    {
        NestedSetTable9Query::create()->deleteAll();
        $t = new NestedSetTable9();
        $t->makeRoot();
        $t->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $t1 = new NestedSetTable9();
        $t1->save();
        $t1->insertAsLastChildOf($t);
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $t1->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    /**
     * @return void
     */
    public function testInsertAsPrevSiblingOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'insertAsPrevSiblingOf'), 'nested_set adds a insertAsPrevSiblingOf() method');
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
        $t8 = new PublicTable9();
        $t8->setTitle('t8');
        $t = $t8->insertAsPrevSiblingOf($t3);
        $this->assertEquals($t8, $t, 'insertAsPrevSiblingOf() returns the object it was called on');
        $this->assertEquals(4, $t3->getLeftValue(), 'insertAsPrevSiblingOf() does not modify the tree until the object is saved');
        $t8->save();
        $this->assertEquals(4, $t8->getLeftValue(), 'insertAsPrevSiblingOf() sets the left value correctly');
        $this->assertEquals(5, $t8->getRightValue(), 'insertAsPrevSiblingOf() sets the right value correctly');
        $this->assertEquals(1, $t8->getLevel(), 'insertAsPrevSiblingOf() sets the level correctly');
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [6, 15, 1],
            't4' => [7, 8, 2],
            't5' => [9, 14, 2],
            't6' => [10, 11, 3],
            't7' => [12, 13, 3],
            't8' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsPrevSiblingOf() shifts the other nodes correctly');
        try {
            $t8->insertAsPrevSiblingOf($t4);
            $this->fail('insertAsPrevSiblingOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsPrevSiblingOf() throws an exception when called on a saved object');
        }
    }

    /**
     * @return void
     */
    public function testInsertAsPrevSiblingOfExistingObject()
    {
        NestedSetTable9Query::create()->deleteAll();
        $t = new NestedSetTable9();
        $t->makeRoot();
        $t->save();
        $t1 = new NestedSetTable9();
        $t1->insertAsFirstChildOf($t);
        $t1->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $t2 = new NestedSetTable9();
        $t2->save();
        $t2->insertAsPrevSiblingOf($t1);
        $this->assertEquals(2, $t2->getLeftValue());
        $this->assertEquals(3, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
        $t2->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(6, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(4, $t1->getLeftValue());
        $this->assertEquals(5, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $this->assertEquals(2, $t2->getLeftValue());
        $this->assertEquals(3, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
    }

    /**
     * @return void
     */
    public function testInsertAsNextSiblingOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'insertAsNextSiblingOf'), 'nested_set adds a insertAsNextSiblingOf() method');
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
        $t8 = new PublicTable9();
        $t8->setTitle('t8');
        $t = $t8->insertAsNextSiblingOf($t3);
        $this->assertEquals($t8, $t, 'insertAsNextSiblingOf() returns the object it was called on');
        $this->assertEquals(14, $t1->getRightValue(), 'insertAsNextSiblingOf() does not modify the tree until the object is saved');
        $t8->save();
        $this->assertEquals(14, $t8->getLeftValue(), 'insertAsNextSiblingOf() sets the left value correctly');
        $this->assertEquals(15, $t8->getRightValue(), 'insertAsNextSiblingOf() sets the right value correctly');
        $this->assertEquals(1, $t8->getLevel(), 'insertAsNextSiblingOf() sets the level correctly');
        $expected = [
            't1' => [1, 16, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
            't8' => [14, 15, 1],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsNextSiblingOf() shifts the other nodes correctly');
        try {
            $t8->insertAsNextSiblingOf($t4);
            $this->fail('insertAsNextSiblingOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsNextSiblingOf() throws an exception when called on a saved object');
        }
    }

    /**
     * @return void
     */
    public function testInsertAsNextSiblingOfExistingObject()
    {
        NestedSetTable9Query::create()->deleteAll();
        $t = new NestedSetTable9();
        $t->makeRoot();
        $t->save();
        $t1 = new NestedSetTable9();
        $t1->insertAsFirstChildOf($t);
        $t1->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $t2 = new NestedSetTable9();
        $t2->save();
        $t2->insertAsNextSiblingOf($t1);
        $this->assertEquals(4, $t2->getLeftValue());
        $this->assertEquals(5, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
        $t2->save();
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(6, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
        $this->assertEquals(4, $t2->getLeftValue());
        $this->assertEquals(5, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
    }

    /**
     * @return void
     */
    public function testMoveToFirstChildOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'moveToFirstChildOf'), 'nested_set adds a moveToFirstChildOf() method');
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
        try {
            $t3->moveToFirstChildOf($t5);
            $this->fail('moveToFirstChildOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToFirstChildOf() throws an exception when the target is a child node');
        }
        // moving down
        $t = $t3->moveToFirstChildOf($t2);
        $this->assertEquals($t3, $t, 'moveToFirstChildOf() returns the object it was called on');
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 13, 1],
            't3' => [3, 12, 2],
            't4' => [4, 5, 3],
            't5' => [6, 11, 3],
            't6' => [7, 8, 4],
            't7' => [9, 10, 4],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree down correctly');
        // moving up
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToFirstChildOf($t1);
        $expected = [
            't1' => [1, 14, 0],
            't2' => [8, 9, 1],
            't3' => [10, 13, 1],
            't4' => [11, 12, 2],
            't5' => [2, 7, 1],
            't6' => [3, 4, 2],
            't7' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree up correctly');
        // moving to the same level
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t5->moveToFirstChildOf($t3);
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [11, 12, 2],
            't5' => [5, 10, 2],
            't6' => [6, 7, 3],
            't7' => [8, 9, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree to the same level correctly');
    }

    /**
     * @return void
     */
    public function testMoveToFirstChildOfAndChildrenCache()
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
        // fill children cache
        $t3->getChildren();
        $t1->getChildren();
        // move
        $t5->moveToFirstChildOf($t1);
        $children = $t3->getChildren();
        $expected = [
            't4' => [11, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToFirstChildOf() reinitializes the child collection of all concerned nodes');
        $children = $t1->getChildren();
        $expected = [
            't5' => [2, 7, 1],
            't2' => [8, 9, 1],
            't3' => [10, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToFirstChildOf() reinitializes the child collection of all concerned nodes');
    }

    /**
     * @return void
     */
    public function testMoveToLastChildOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'moveToLastChildOf'), 'nested_set adds a moveToLastChildOf() method');
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
        try {
            $t3->moveToLastChildOf($t5);
            $this->fail('moveToLastChildOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToLastChildOf() throws an exception when the target is a child node');
        }
        // moving up
        $t = $t5->moveToLastChildOf($t1);
        $this->assertEquals($t5, $t, 'moveToLastChildOf() returns the object it was called on');
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 7, 1],
            't4' => [5, 6, 2],
            't5' => [8, 13, 1],
            't6' => [9, 10, 2],
            't7' => [11, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree up correctly');
        // moving down
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t3->moveToLastChildOf($t2);
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 13, 1],
            't3' => [3, 12, 2],
            't4' => [4, 5, 3],
            't5' => [6, 11, 3],
            't6' => [7, 8, 4],
            't7' => [9, 10, 4],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree down correctly');
        // moving to the same level
        [$t1, $t2, $t3, $t4, $t5, $t6, $t7] = $this->initTree();
        $t4->moveToLastChildOf($t3);
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [11, 12, 2],
            't5' => [5, 10, 2],
            't6' => [6, 7, 3],
            't7' => [8, 9, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree to the same level correctly');
    }

    /**
     * @return void
     */
    public function testMoveToLastChildOfAndChildrenCache()
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
        // fill children cache
        $t3->getChildren();
        $t1->getChildren();
        // move
        $t5->moveToLastChildOf($t1);
        $children = $t3->getChildren();
        $expected = [
            't4' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToLastChildOf() reinitializes the child collection of all concerned nodes');
        $children = $t1->getChildren();
        $expected = [
            't2' => [2, 3, 1],
            't3' => [4, 7, 1],
            't5' => [8, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToLastChildOf() reinitializes the child collection of all concerned nodes');
    }

    /**
     * @return void
     */
    public function testMoveToPrevSiblingOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'moveToPrevSiblingOf'), 'nested_set adds a moveToPrevSiblingOf() method');
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
        try {
            $t5->moveToPrevSiblingOf($t1);
            $this->fail('moveToPrevSiblingOf() throws an exception when the target is a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToPrevSiblingOf() throws an exception when the target is a root node');
        }
        try {
            $t5->moveToPrevSiblingOf($t6);
            $this->fail('moveToPrevSiblingOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToPrevSiblingOf() throws an exception when the target is a child node');
        }
        // moving up
        $t = $t5->moveToPrevSiblingOf($t3);
        /* Results in
         t1
         | \     \
         t2 t5    t3
            | \    |
            t6 t7  t4
        */
        $this->assertEquals($t5, $t, 'moveToPrevSiblingOf() returns the object it was called on');
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [10, 13, 1],
            't4' => [11, 12, 2],
            't5' => [4, 9, 1],
            't6' => [5, 6, 2],
            't7' => [7, 8, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree up correctly');
        // moving down
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
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [11, 12, 2],
            't5' => [5, 10, 2],
            't6' => [6, 7, 3],
            't7' => [8, 9, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree down correctly');
        // moving at the same level
        $t4->moveToPrevSiblingOf($t5);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree at the same level correctly');
    }

    /**
     * @return void
     */
    public function testMoveToPrevSiblingOfAndChildrenCache()
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
        // fill children cache
        $t3->getChildren();
        $t1->getChildren();
        // move
        $t5->moveToPrevSiblingOf($t2);
        $children = $t3->getChildren();
        $expected = [
            't4' => [11, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToPrevSiblingOf() reinitializes the child collection of all concerned nodes');
        $children = $t1->getChildren();
        $expected = [
            't5' => [2, 7, 1],
            't2' => [8, 9, 1],
            't3' => [10, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToPrevSiblingOf() reinitializes the child collection of all concerned nodes');
    }

    /**
     * @return void
     */
    public function testMoveToNextSiblingOfAndChildrenCache()
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
        // fill children cache
        $t3->getChildren();
        $t1->getChildren();
        // move
        $t5->moveToNextSiblingOf($t3);
        $children = $t3->getChildren();
        $expected = [
            't4' => [5, 6, 2],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToNextSiblingOf() reinitializes the child collection of all concerned nodes');
        $children = $t1->getChildren();
        $expected = [
            't2' => [2, 3, 1],
            't3' => [4, 7, 1],
            't5' => [8, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'moveToNextSiblingOf() reinitializes the child collection of all concerned nodes');
    }

    /**
     * @return void
     */
    public function testMoveToNextSiblingOf()
    {
        $this->assertTrue(method_exists('NestedSetTable9', 'moveToNextSiblingOf'), 'nested_set adds a moveToNextSiblingOf() method');
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
        try {
            $t5->moveToNextSiblingOf($t1);
            $this->fail('moveToNextSiblingOf() throws an exception when the target is a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToNextSiblingOf() throws an exception when the target is a root node');
        }
        try {
            $t5->moveToNextSiblingOf($t6);
            $this->fail('moveToNextSiblingOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToNextSiblingOf() throws an exception when the target is a child node');
        }
        // moving up
        $t = $t5->moveToNextSiblingOf($t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $this->assertEquals($t5, $t, 'moveToPrevSiblingOf() returns the object it was called on');
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 7, 1],
            't4' => [5, 6, 2],
            't5' => [8, 13, 1],
            't6' => [9, 10, 2],
            't7' => [11, 12, 2],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree up correctly');
        // moving down
        $t = $t5->moveToNextSiblingOf($t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [5, 6, 2],
            't5' => [7, 12, 2],
            't6' => [8, 9, 3],
            't7' => [10, 11, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree down correctly');
        // moving at the same level
        $t = $t4->moveToNextSiblingOf($t5);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $expected = [
            't1' => [1, 14, 0],
            't2' => [2, 3, 1],
            't3' => [4, 13, 1],
            't4' => [11, 12, 2],
            't5' => [5, 10, 2],
            't6' => [6, 7, 3],
            't7' => [8, 9, 3],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree at the same level correctly');
    }

    /**
     * @return void
     */
    public function testDeleteDescendants()
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
        $this->assertNull($t2->deleteDescendants(), 'deleteDescendants() returns null leafs');
        $this->assertEquals(4, $t3->deleteDescendants(), 'deleteDescendants() returns the number of deleted nodes');
        $this->assertEquals(5, $t3->getRightValue(), 'deleteDescendants() updates the current node');
        $this->assertEquals(5, $t4->getLeftValue(), 'deleteDescendants() does not update existing nodes (because delete() clears the instance cache)');
        $expected = [
            't1' => [1, 6, 0],
            't2' => [2, 3, 1],
            't3' => [4, 5, 1],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'deleteDescendants() shifts the entire subtree correctly');
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
        $this->assertEquals(6, $t1->deleteDescendants(), 'deleteDescendants() can be called on the root node');
        $expected = [
            't1' => [1, 2, 0],
        ];
        $this->assertEquals($expected, $this->dumpTree(), 'deleteDescendants() can delete all descendants of the root node');
    }

    /**
     * @return void
     */
    public function testGetIterator()
    {
        $fixtures = $this->initTree();
        $this->assertTrue(method_exists('NestedSetTable9', 'getIterator'), 'nested_set adds a getIterator() method');
        $root = NestedSetTable9Query::retrieveRoot();
        $iterator = $root->getIterator();
        $this->assertTrue($iterator instanceof NestedSetRecursiveIterator, 'getIterator() returns a NestedSetRecursiveIterator');
        foreach ($iterator as $node) {
            $expected = array_shift($fixtures);
            $this->assertEquals($expected, $node, 'getIterator returns an iterator parsing the tree order by left column');
        }
    }

    /**
     * @return void
     */
    public function testGetAncestors2()
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
        $path = $t5->getAncestors();
        $expected = [
            't1' => [1, 14, 0],
            't3' => [4, 13, 1],
        ];
        $this->assertEquals($expected, $this->dumpNodes($path), 'getAncestors() returns path from the current scope only');
    }

    /**
     * @return void
     */
    public function testConstants()
    {
        $this->assertEquals(NestedSetTable9::LEFT_COL, 'nested_set_table9.tree_left', 'nested_set adds a LEFT_COL constant');
        $this->assertEquals(NestedSetTable9::RIGHT_COL, 'nested_set_table9.tree_right', 'nested_set adds a RIGHT_COL constant');
        $this->assertEquals(NestedSetTable9::LEVEL_COL, 'nested_set_table9.tree_level', 'nested_set adds a LEVEL_COL constant');
    }
}
