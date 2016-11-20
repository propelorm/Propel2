<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\MapNestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10Query;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9;
use Propel\Tests\Bookstore\BehaviorNestedSetEntity10;
use Propel\Tests\Bookstore\BehaviorNestedSetEntity9;

/**
 * Class NestedSetBehaviorNestedManagerTest
 *
 * @author  Cristiano Cinotti <cristianocinotti@gmail.com>
 * @group database
 */
class NestedSetBehaviorNestedManagerTest extends TestCase
{
    public function testCountChildren()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertEquals(2, $manager->countChildren($t1), 'Retrieve correct number of children');
        $this->assertEquals(2, $manager->countChildren($t3), 'Retrieve correct number of children');
        $this->assertEquals(2, $manager->countChildren($t5), 'Retrieve correct number of children');

        $this->assertEquals(0, $manager->countChildren($t4), 'Leaf node has no child');
        $this->assertEquals(0, $manager->countChildren($t7), 'Leaf node has no child');
        $this->assertEquals(0, $manager->countChildren($t6), 'Leaf node has no child');
        $this->assertEquals(0, $manager->countChildren($t2), 'Leaf node has no child');

        $obj = new NestedSetEntity9();
        $this->assertEquals(0, $manager->countChildren($obj), 'Return 0 if new node');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't5');
        $this->assertEquals(1, $manager->countChildren($t3, $c), 'countChildren() accepts a criteria as parameter');
    }

    public function testCountDescendants()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertEquals(6, $manager->countDescendants($t1), 'countDescendants() returns the number of descendants');
        $this->assertEquals(0, $manager->countDescendants($t2), 'countDescendants() returns 0 for leafs');
        $this->assertEquals(4, $manager->countDescendants($t3), 'countDescendants() returns the number of descendants');
        $this->assertEquals(0, $manager->countDescendants($t4), 'countDescendants() returns 0 for leafs');
        $this->assertEquals(2, $manager->countDescendants($t5), 'countDescendants() returns the number of descendants');
        $this->assertEquals(0, $manager->countDescendants($t6), 'countDescendants() returns 0 for leafs');
        $this->assertEquals(0, $manager->countDescendants($t7), 'countDescendants() returns 0 for leafs');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't5');
        $this->assertEquals(1, $manager->countDescendants($t3, $c), 'countDescendants() accepts a criteria as parameter');
    }

    public function testGetParent()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertEquals($t1, $manager->getParent($t2), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t1, $manager->getParent($t3), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t3, $manager->getParent($t4), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t3, $manager->getParent($t5), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t5, $manager->getParent($t6), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($t5, $manager->getParent($t7), 'getParent() correctly retrieves parent for nodes');
        $this->assertEquals($manager->getParent($t2), $manager->getParent($t3), 'getParent() returns the same parent for sibling nodes');
        $this->assertNull($manager->getParent($t1), 'getParent() return null for root nodes');

        $tn = new NestedSetEntity9();
        $this->assertNull($manager->getParent($tn), 'getParent() return null for empty nodes');
    }

    public function testGetParentWithScope()
    {
        $manager = $this->getManagerWithScope();
        $this->initTreeWithScope();

        $t1 = $this->getByTitle('t1');
        $this->assertNull($manager->getParent($t1), 'getParent() return null for root nodes');
        $t2 = $this->getByTitle('t2');
        $this->assertEquals($manager->getParent($t2), $t1, 'getParent() correctly retrieves parent for leafs');
        $t3 = $this->getByTitle('t3');
        $this->assertEquals($manager->getParent($t3), $t1, 'getParent() correctly retrieves parent for nodes');
        $t4 = $this->getByTitle('t4');
        $this->assertEquals($manager->getParent($t4), $t3, 'getParent() retrieves the same parent for nodes');
    }

    public function testGetPrevSibling()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertNull($manager->getPrevSibling($t1), 'getPrevSibling() returns null for root nodes');
        $this->assertNull($manager->getPrevSibling($t2), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($manager->getPrevSibling($t3), $t2, 'getPrevSibling() correctly retrieves prev sibling');
        $this->assertNull($manager->getPrevSibling($t6), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($manager->getPrevSibling($t7), $t6, 'getPrevSibling() correctly retrieves prev sibling');

        $con = $this->getConfiguration()->getConnectionManager(NestedSetEntity9EntityMap::DATABASE_NAME)->getWriteConnection();
        $this->assertNull($manager->getPrevSibling($t4, $con), 'getPrevSibling() accepts a connection parameter');
        $this->assertEquals($manager->getPrevSibling($t5, $con), $t4, 'getPrevSibling() accepts a connection parameter');
    }

    public function testGetPrevSiblingWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertNull($manager->getPrevSibling($t1), 'getPrevSibling() returns null for root nodes');
        $this->assertNull($manager->getPrevSibling($t2), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($manager->getPrevSibling($t3), $t2, 'getPrevSibling() correctly retrieves prev sibling');
        $this->assertNull($manager->getPrevSibling($t6), 'getPrevSibling() returns null for first siblings');
        $this->assertEquals($manager->getPrevSibling($t7), $t6, 'getPrevSibling() correctly retrieves prev sibling');
    }

    public function testGetNextSibling()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertNull($manager->getNextSibling($t1), 'getNextSibling() returns null for root nodes');
        $this->assertEquals($manager->getNextSibling($t2), $t3, 'getNextSibling() correctly retrieves next sibling');
        $this->assertNull($manager->getNextSibling($t3), 'getNextSibling() returns null for last siblings');

        $con = $this->getConfiguration()->getconnectionManager(NestedSetEntity9EntityMap::DATABASE_NAME)->getReadConnection();
        $this->assertEquals($manager->getNextSibling($t6, $con), $t7, 'getNextSibling() accepts a connection parameter');
        $this->assertNull($manager->getNextSibling($t7, $con), 'getNextSibling() accepts a connection parameter');
    }

    public function testGetNextSiblingWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertNull($manager->getNextSibling($t1), 'getNextSibling() returns null for root nodes');
        $this->assertEquals($manager->getNextSibling($t2), $t3, 'getNextSibling() correctly retrieves next sibling');
        $this->assertNull($manager->getNextSibling($t3), 'getNextSibling() returns null for last siblings');
        $this->assertEquals($manager->getNextSibling($t6), $t7, 'getNextSibling() correctly retrieves next sibling');
        $this->assertNull($manager->getNextSibling($t7), 'getNextSibling() returns null for last siblings');
    }

    public function testGetChildren()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertTrue($manager->getChildren($t2) instanceof ObjectCollection, 'getChildren() returns a collection');
        $this->assertEquals(0, count($manager->getChildren($t2)), 'getChildren() returns an empty collection for leafs');
        $children = $manager->getChildren($t3);
        $expected = array(
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
        );
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() returns a collection of children');

        $con = $this->getConfiguration()->getconnectionManager(NestedSetEntity9EntityMap::DATABASE_NAME)->getReadConnection();
        $children = $manager->getChildren($t5, null, $con);
        $expected = array(
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() accepts a connection as parameter');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't5');
        $children = $manager->getChildren($t3, $c);
        $expected = array(
            't5' => array(7, 12, 2),
        );
        $this->assertEquals($expected, $this->dumpNodes($children, true), 'getChildren() accepts a criteria as parameter');
    }

    public function testGetFirstChild()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertEquals($t2, $manager->getFirstChild($t1), 'getFirstChild() returns the first child');
        $this->assertEquals($t4, $manager->getFirstChild($t3), 'getFirstChild() returns the first child');
        $this->assertNull($manager->getFirstChild($t7), 'getFirstChild() returns null for leaf nodes');
        $this->assertNull($manager->getFirstChild($t2), 'getFirstChild() returns null for leaf nodes');

        $con = $this->getConfiguration()->getconnectionManager(NestedSetEntity9EntityMap::DATABASE_NAME)->getReadConnection();
        $this->assertNull($manager->getFirstChild($t6, null, $con), 'getFirstChild() accepts a connection as parameter');
        $this->assertEquals($t6, $manager->getFirstChild($t5, null, $con), 'getFirstChild() accept a connection as parameter');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't4');

        $this->assertEquals($t4, $manager->getFirstChild($t3, $c), 'getFirstChild() accepts a criteria as parameter');
    }

    public function testGetLastChild()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertEquals($t3, $manager->getLastChild($t1), 'getFirstChild() returns the first child');
        $this->assertEquals($t5, $manager->getLastChild($t3), 'getFirstChild() returns the first child');
        $this->assertNull($manager->getLastChild($t7), 'getFirstChild() returns null for leaf nodes');
        $this->assertNull($manager->getLastChild($t2), 'getFirstChild() returns null for leaf nodes');

        $con = $this->getConfiguration()->getconnectionManager(NestedSetEntity9EntityMap::DATABASE_NAME)->getReadConnection();
        $this->assertNull($manager->getLastChild($t6, null, $con), 'getFirstChild() accepts a connection as parameter');
        $this->assertEquals($t7, $manager->getLastChild($t5, null, $con), 'getFirstChild() accept a connection as parameter');

        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't5');

        $this->assertEquals($t5, $manager->getLastChild($t3, $c), 'getFirstChild() accepts a criteria as parameter');
    }

    public function testGetSiblings()
    {
        $manager = $this->getManager();
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
        $this->assertEquals(array(), $manager->getSiblings($t1), 'getSiblings() returns an empty array for root');
        $siblings = $manager->getSiblings($t5);
        $expected = array(
            't4' => array(5, 6, 2),
        );
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings() returns an array of siblings');
        $siblings = $manager->getSiblings($t5, true);
        $expected = array(
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2)
        );
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings(true) includes the current node');
        $manager->moveToNextSiblingOf($t5, $t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $this->assertEquals(0, count($manager->getSiblings($t4)), 'getSiblings() returns an empty collection for lone children');
        $siblings = $manager->getSiblings($t3);
        $expected = array(
            't2' => array(2, 3, 1),
            't5' => array(8, 13, 1),
        );
        $this->assertEquals($expected, $this->dumpNodes($siblings), 'getSiblings() returns all siblings');
        $this->assertEquals('t2', $siblings[0]->getTitle(), 'getSiblings() returns siblings in natural order');
        $this->assertEquals('t5', $siblings[1]->getTitle(), 'getSiblings() returns siblings in natural order');
    }

    public function testGetDescendants()
    {
        $manager = $this->getManager();
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
        $this->assertEquals(array(), $manager->getDescendants($t2), 'getDescendants() returns an empty array for leafs');
        $descendants = $manager->getDescendants($t3);
        $expected = array(
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() returns an array of descendants');
        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't5');
        $descendants = $manager->getDescendants($t3, $c);
        $expected = array(
            't5' => array(7, 12, 2),
        );
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() accepts a criteria as parameter');
    }

    public function testGetDescendantsWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $descendants = $manager->getDescendants($t3);
        $expected = array(
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() returns descendants from the current scope only');
    }

    public function testGetBranch()
    {
        $manager = $this->getManager();
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
        $descendants = $manager->getBranch($t3);
        $expected = array(
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() returns an array of descendants, including the current node');
        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't3', Criteria::NOT_EQUAL);
        $descendants = $manager->getBranch($t3, $c);
        unset($expected['t3']);
        $this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() accepts a criteria as first parameter');
    }

    public function testGetAncestors()
    {
        $manager = $this->getManager();
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
        $this->assertEquals(array(), $manager->getAncestors($t1), 'getAncestors() returns an empty array for roots');
        $ancestors = $manager->getAncestors($t5);
        $expected = array(
            't1' => array(1, 14, 0),
            't3' => array(4, 13, 1),
        );
        $this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() returns an array of ancestors');
        $c = new Criteria(NestedSetEntity9EntityMap::DATABASE_NAME);
        $c->add(NestedSetEntity9EntityMap::FIELD_TITLE, 't3');
        $ancestors = $manager->getAncestors($t5, $c);
        $expected = array(
            't3' => array(4, 13, 1),
        );
        $this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() accepts a criteria as parameter');
    }

    public function testGetAncestorsWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertEquals(array(), $manager->getAncestors($t1), 'getAncestors() returns an empty array for roots');
        $ancestors = $manager->getAncestors($t5);
        $expected = array(
            't1' => array(1, 14, 0),
            't3' => array(4, 13, 1),
        );
        $this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() returns ancestors from the current scope only');
    }

    public function testHasParent()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t0 = new NestedSetEntity9();
        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->setLevel(0);
        $repository->save($t1);
        $t2 = new NestedSetEntity9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->setLevel(1);
        $repository->save($t2);
        $t3 = new NestedSetEntity9();
        $t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->setLevel(2);
        $repository->save($t3);

        $this->assertFalse($manager->hasParent($t0), 'empty node has no parent');
        $this->assertFalse($manager->hasParent($t1), 'root node has no parent');
        $this->assertTrue($manager->hasParent($t2), 'not root node has a parent');
        $this->assertTrue($manager->hasParent($t3), 'leaf node has a parent');
    }

    public function testHasPrevSibling()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();
        
        $t0 = new NestedSetEntity9();
        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6);
        $repository->save($t1);
        $t2 = new NestedSetEntity9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(3);
        $repository->save($t2);
        $t3 = new NestedSetEntity9();
        $t3->setTitle('t3')->setLeftValue(4)->setRightValue(5);
        $repository->save($t3);

        $this->assertFalse($manager->hasPrevSibling($t0), 'empty node has no previous sibling');
        $this->assertFalse($manager->hasPrevSibling($t1), 'root node has no previous sibling');
        $this->assertFalse($manager->hasPrevSibling($t2), 'first sibling has no previous sibling');
        $this->assertTrue($manager->hasPrevSibling($t3), 'not first sibling has a previous sibling');
    }

    public function testHasNextSibling()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();
        
        $t0 = new NestedSetEntity9();
        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1')->setLeftValue(1)->setRightValue(6);
        $repository->save($t1);
        $t2 = new NestedSetEntity9();
        $t2->setTitle('t2')->setLeftValue(2)->setRightValue(3);
        $repository->save($t2);
        $t3 = new NestedSetEntity9();
        $t3->setTitle('t3')->setLeftValue(4)->setRightValue(5);
        $repository->save($t3);

        $this->assertFalse($manager->hasNextSibling($t0), 'empty node has no next sibling');
        $this->assertFalse($manager->hasNextSibling($t1), 'root node has no next sibling');
        $this->assertTrue($manager->hasNextSibling($t2), 'not last sibling has a next sibling');
        $this->assertFalse($manager->hasNextSibling($t3), 'last sibling has no next sibling');
    }

    public function testHasChildren()
    {
        $manager = $this->getManager();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();

        $this->assertFalse($manager->hasChildren($t6), 'Leaf nodes has no child');
        $this->assertFalse($manager->hasChildren($t2), 'Leaf nodes has no child');
        $this->assertTrue($manager->hasChildren($t1), 'Root node has children');
        $this->assertTrue($manager->hasChildren($t3), 'Non leaf nodes has children');
    }

    public function testAddChild()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1');
        $manager->makeRoot($t1);
        $repository->save($t1);

        $t2 = new NestedSetEntity9();
        $t2->setTitle('t2');
        $manager->addChild($t1, $t2);
        $repository->save($t2);

        $t3 = new NestedSetEntity9();
        $t3->setTitle('t3');
        $manager->addChild($t1, $t3);
        $repository->save($t3);

        $t4 = new NestedSetEntity9();
        $t4->setTitle('t4');
        $manager->addChild($t2, $t4);
        $repository->save($t4);
        

        $expected = array(
            't1' => array(1, 8, 0),
            't2' => array(4, 7, 1),
            't3' => array(2, 3, 1),
            't4' => array(5, 6, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'addChild() adds the child and saves it');
    }

    public function testInsertAsFirstChildOf()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
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
        $t8 = new NestedSetEntity9();
        $t8->setTitle('t8');
        $manager->insertAsFirstChildOf($t8, $t3);
        $this->assertEquals(5, $t4->getLeftValue(), 'insertAsFirstChildOf() does not modify the tree until the object is saved');
        $repository->save($t8);
        
        $this->assertEquals(5, $t8->getLeftValue(), 'insertAsFirstChildOf() sets the left value correctly');
        $this->assertEquals(6, $t8->getRightValue(), 'insertAsFirstChildOf() sets the right value correctly');
        $this->assertEquals(2, $t8->getLevel(), 'insertAsFirstChildOf() sets the level correctly');
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
            't8' => array(5, 6, 2)
        );
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsFirstChildOf() shifts the other nodes correctly');
        try {
            $manager->insertAsFirstChildOf($t8, $t4);
            $this->fail('insertAsFirstChildOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsFirstChildOf() throws an exception when called on a saved object');
        }
    }

    public function testInsertAsFirstChildOfExistingObject()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t = new NestedSetEntity9();
        $manager->makeRoot($t);
        $repository->save($t);

        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());

        $t1 = new NestedSetEntity9();
        $manager->insertAsFirstChildOf($t1, $t);
        $repository->save($t1);

        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());

        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    public function testInsertAsFirstChildOfWithScope()
    {
        $manager = $this->getManagerWithScope();
        $session = $this->getConfiguration()->getSession();
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
        $t11 = new NestedSetEntity10();
        $t11->setTitle('t11');
        $manager->insertAsFirstChildOf($t11, $fixtures[2]); // first child of t3
        $this->assertEquals(1, $t11->getScopeValue(), 'insertAsFirstChildOf() sets the scope value correctly');
        $session->persist($t11);
        $session->commit();
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
            't11' => array(5, 6, 2)
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'insertAsFirstChildOf() shifts the other nodes correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'insertAsFirstChildOf() does not shift anything out of the scope');
    }

    public function testInsertAsFirstChildOfExistingObjectWithScope()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();
        $manager = $this->getManagerWithScope();

        $t = new NestedSetEntity10();
        $t->setScopeValue(34);
        $manager->makeRoot($t);
        $repository->save($t);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $t1 = new NestedSetEntity10();
        $repository->save($t1);
        $manager->insertAsFirstChildOf($t1, $t);
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $repository->save($t1);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    public function testInsertAsLastChildOf()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
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
        $t8 = new NestedSetEntity9();
        $t8->setTitle('t8');
        $manager->insertAsLastChildOf($t8, $t3);
        $this->assertEquals(13, $t3->getRightValue(), 'insertAsLastChildOf() does not modify the tree until the object is saved');
        $repository->save($t8);

        $this->assertEquals(13, $t8->getLeftValue(), 'insertAsLastChildOf() sets the left value correctly');
        $this->assertEquals(14, $t8->getRightValue(), 'insertAsLastChildOf() sets the right value correctly');
        $this->assertEquals(2, $t8->getLevel(), 'insertAsLastChildOf() sets the level correctly');
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
            't8' => array(13, 14, 2)
        );
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsLastChildOf() shifts the other nodes correctly');
        try {
            $manager->insertAsLastChildOf($t8, $t4);
            $this->fail('insertAsLastChildOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsLastChildOf() throws an exception when called on a saved object');
        }
    }

    public function testInsertAsLastChildOfExistingObject()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t = new NestedSetEntity9();
        $manager->makeRoot($t);
        $repository->save($t);
        
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());

        $t1 = new NestedSetEntity9();
        $manager->insertAsLastChildOf($t1, $t);
        $repository->save($t1);
        
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());

        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    public function testInsertAsLastChildOfWhithScope()
    {
        $manager = $this->getManagerWithScope();
        $session = $this->getConfiguration()->getSession();
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
        $t11 = new NestedSetEntity10();
        $t11->setTitle('t11');
        $manager->insertAsLastChildOf($t11, $fixtures[2]); // last child of t3
        $this->assertEquals(1, $t11->getScopeValue(), 'insertAsLastChildOf() sets the scope value correctly');
        $session->persist($t11);
        $session->commit();
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
            't11' => array(13, 14, 2)
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'insertAsLastChildOf() shifts the other nodes correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'insertAsLastChildOf() does not shift anything out of the scope');
    }

    public function testInsertAsLastChildOfExistingObjectWithScope()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();
        $manager = $this->getManagerWithScope();

        $t = new NestedSetEntity10();
        $t->setScopeValue(34);
        $manager->makeRoot($t);
        $repository->save($t);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(2, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $t1 = new NestedSetEntity10();
        $repository->save($t1);
        $manager->insertAsLastChildOf($t1, $t);
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $repository->save($t1);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
    }

    public function testInsertAsPrevSiblingOf()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
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
        $t8 = new NestedSetEntity9();
        $t8->setTitle('t8');
        $manager->insertAsPrevSiblingOf($t8, $t3);
        $this->assertEquals(4, $t3->getLeftValue(), 'insertAsPrevSiblingOf() does not modify the tree until the object is saved');
        $repository->save($t8);
        
        $this->assertEquals(4, $t8->getLeftValue(), 'insertAsPrevSiblingOf() sets the left value correctly');
        $this->assertEquals(5, $t8->getRightValue(), 'insertAsPrevSiblingOf() sets the right value correctly');
        $this->assertEquals(1, $t8->getLevel(), 'insertAsPrevSiblingOf() sets the level correctly');
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(6, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
            't8' => array(4, 5, 1)
        );
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsPrevSiblingOf() shifts the other nodes correctly');
        try {
            $manager->insertAsPrevSiblingOf($t8, $t4);
            $this->fail('insertAsPrevSiblingOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsPrevSiblingOf() throws an exception when called on a saved object');
        }
    }

    public function testInsertAsPrevSiblingOfExistingObject()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t = new NestedSetEntity9();
        $t->setTitle('t');
        $manager->makeRoot($t);
        $repository->save($t);

        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1');
        $manager->insertAsFirstChildOf($t1, $t);
        $repository->save($t1);

        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());

        $t2 = new NestedSetEntity9();
        $t2->setTitle('t2');
        $repository->save($t2);

        $manager->insertAsPrevSiblingOf($t2, $t1);

        $this->assertEquals(2, $t2->getLeftValue());
        $this->assertEquals(3, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
        $repository->save($t2);

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

    public function testInsertAsPrevSiblingOfWithScope()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $manager = $this->getManagerWithScope();

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
        $t11 = new NestedSetEntity10();
        $t11->setTitle('t11');
        $manager->insertAsPrevSiblingOf($t11, $fixtures[2]); // prev sibling of t3
        $this->assertEquals(1, $t11->getScopeValue(), 'insertAsPrevSiblingOf() sets the scope value correctly');
        $repository->save($t11);
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(6, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
            't11' => array(4, 5, 1)
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'insertAsPrevSiblingOf() shifts the other nodes correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'insertAsPrevSiblingOf() does not shift anything out of the scope');
    }

    public function testInsertAsPrevSiblingOfExistingObjectWithScope()
    {
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();
        $manager = $this->getManagerWithScope();

        $t = new NestedSetEntity10();
        $t->setScopeValue(34);
        $manager->makeRoot($t);
        $repository->save($t);
        $t1 = new NestedSetEntity10();
        $manager->insertAsFirstChildOf($t1, $t);
        $repository->save($t1);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $t2 = new NestedSetEntity10();
        $repository->save($t2);
        $manager->insertAsPrevSiblingOf($t2, $t1);
        $this->assertEquals(2, $t2->getLeftValue());
        $this->assertEquals(3, $t2->getRightValue());
        $this->assertEquals(34, $t2->getScopeValue());
        $this->assertEquals(1, $t2->getLevel());
        $repository->save($t2);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(6, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(4, $t1->getLeftValue());
        $this->assertEquals(5, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $this->assertEquals(2, $t2->getLeftValue());
        $this->assertEquals(3, $t2->getRightValue());
        $this->assertEquals(34, $t2->getScopeValue());
        $this->assertEquals(1, $t2->getLevel());
    }

    public function testInsertAsNextSiblingOf()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
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
        $t8 = new NestedSetEntity9();
        $t8->setTitle('t8');
        $manager->insertAsNextSiblingOf($t8, $t3);
        $this->assertEquals(14, $t1->getRightValue(), 'insertAsNextSiblingOf() does not modify the tree until the object is saved');
        $repository->save($t8);
        
        $this->assertEquals(14, $t8->getLeftValue(), 'insertAsNextSiblingOf() sets the left value correctly');
        $this->assertEquals(15, $t8->getRightValue(), 'insertAsNextSiblingOf() sets the right value correctly');
        $this->assertEquals(1, $t8->getLevel(), 'insertAsNextSiblingOf() sets the level correctly');
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
            't8' => array(14, 15, 1)
        );
        $this->assertEquals($expected, $this->dumpTree(), 'insertAsNextSiblingOf() shifts the other nodes correctly');
        try {
            $manager->insertAsNextSiblingOf($t8, $t4);
            $this->fail('insertAsNextSiblingOf() throws an exception when called on a saved object');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'insertAsNextSiblingOf() throws an exception when called on a saved object');
        }
    }

    public function testInsertAsNextSiblingOfExistingObject()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t = new NestedSetEntity9();
        $manager->makeRoot($t);
        $repository->save($t);

        $t1 = new NestedSetEntity9();
        $manager->insertAsFirstChildOf($t1, $t);
        $repository->save($t1);
        
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(1, $t1->getLevel());

        $t2 = new NestedSetEntity9();
        $repository->save($t2);
        
        $manager->insertAsNextSiblingOf($t2, $t1);
        $this->assertEquals(4, $t2->getLeftValue());
        $this->assertEquals(5, $t2->getRightValue());
        $this->assertEquals(1, $t2->getLevel());
        $repository->save($t2);
        
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

    public function testInsertAsNextSiblingOfWithScope()
    {
        $manager = $this->getManagerWithScope();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
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
        $t11 = new NestedSetEntity10();
        $t11->setTitle('t11');
        $manager->insertAsNextSiblingOf($t11, $fixtures[2]); // next sibling of t3
        $this->assertEquals(1, $t11->getScopeValue(), 'insertAsNextSiblingOf() sets the scope value correctly');
        $repository->save($t11);
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
            't11' => array(14, 15, 1)
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'insertAsNextSiblingOf() shifts the other nodes correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'insertAsNextSiblingOf() does not shift anything out of the scope');
    }

    public function testInsertAsNextSiblingOfExistingObjectWithScope()
    {
        $manager = $this->getManagerWithScope();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();

        $t = new NestedSetEntity10();
        $t->setScopeValue(34);
        $manager->makeRoot($t);
        $repository->save($t);
        $t1 = new NestedSetEntity10();
        $manager->insertAsFirstChildOf($t1, $t);
        $repository->save($t1);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(4, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $t2 = new NestedSetEntity10();
        $repository->save($t2);
        $manager->insertAsNextSiblingOf($t2, $t1);
        $this->assertEquals(4, $t2->getLeftValue());
        $this->assertEquals(5, $t2->getRightValue());
        $this->assertEquals(34, $t2->getScopeValue());
        $this->assertEquals(1, $t2->getLevel());
        $repository->save($t2);
        $this->assertEquals(1, $t->getLeftValue());
        $this->assertEquals(6, $t->getRightValue());
        $this->assertEquals(0, $t->getLevel());
        $this->assertEquals(2, $t1->getLeftValue());
        $this->assertEquals(3, $t1->getRightValue());
        $this->assertEquals(34, $t1->getScopeValue());
        $this->assertEquals(1, $t1->getLevel());
        $this->assertEquals(4, $t2->getLeftValue());
        $this->assertEquals(5, $t2->getRightValue());
        $this->assertEquals(34, $t2->getScopeValue());
        $this->assertEquals(1, $t2->getLevel());
    }

    public function testIsInTree()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);

        $t1 = new NestedSetEntity9();
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for nodes with no left and right value');
        $repository->save($t1);
        
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for saved nodes with no left and right value');
        $t1->setLeftValue(1)->setRightValue(0);
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for nodes with zero left value');
        $t1->setLeftValue(0)->setRightValue(1);
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for nodes with zero right value');
        $t1->setLeftValue(1)->setRightValue(1);
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for nodes with equal left and right value');
        $t1->setLeftValue(1)->setRightValue(2);
        $this->assertTrue($manager->isInTree($t1), 'isInTree() returns true for nodes with left < right value');
        $t1->setLeftValue(2)->setRightValue(1);
        $this->assertFalse($manager->isInTree($t1), 'isInTree() returns false for nodes with left > right value');
    }

    public function testIsRoot()
    {
        $manager = $this->getManager();
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
        $this->assertTrue($manager->isRoot($t1), 'root is seen as root');
        $this->assertFalse($manager->isRoot($t2), 'leaf is not seen as root');
        $this->assertFalse($manager->isRoot($t3), 'node is not seen as root');
    }

    public function testIsLeaf()
    {
        $manager = $this->getManager();
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
        $this->assertFalse($manager->isLeaf($t1), 'root is not seen as leaf');
        $this->assertTrue($manager->isLeaf($t2), 'leaf is seen as leaf');
        $this->assertFalse($manager->isLeaf($t3), 'node is not seen as leaf');
    }

    public function testIsDescendantOf()
    {
        $manager = $this->getManager();
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
        $this->assertFalse($manager->isDescendantOf($t1, $t1), 'root is not seen as a descendant of root');
        $this->assertTrue($manager->isDescendantOf($t2, $t1), 'direct child is seen as a descendant of root');
        $this->assertFalse($manager->isDescendantOf($t1, $t2), 'root is not seen as a descendant of leaf');
        $this->assertTrue($manager->isDescendantOf($t5, $t1), 'grandchild is seen as a descendant of root');
        $this->assertTrue($manager->isDescendantOf($t5, $t3), 'direct child is seen as a descendant of node');
        $this->assertFalse($manager->isDescendantOf($t3, $t5), 'node is not seen as a descendant of its parent');
    }

    public function testIsDescendantOfWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertFalse($manager->isDescendantOf($t8, $t9), 'root is not seen as a child of root');
        $this->assertTrue($manager->isDescendantOf($t9, $t8), 'direct child is seen as a child of root');

        $this->assertFalse($manager->isDescendantOf($t2, $t8), 'is false, since both are in different scopes');
    }

    public function testIsAncestorOf()
    {
        $manager = $this->getManager();
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
        $this->assertFalse($manager->isAncestorOf($t1, $t1), 'root is not seen as an ancestor of root');
        $this->assertTrue($manager->isAncestorOf($t1, $t2), 'root is seen as an ancestor of direct child');
        $this->assertFalse($manager->isAncestorOf($t2, $t1), 'direct child is not seen as an ancestor of root');
        $this->assertTrue($manager->isAncestorOf($t1, $t5), 'root is seen as an ancestor of grandchild');
        $this->assertTrue($manager->isAncestorOf($t3, $t5), 'parent is seen as an ancestor of node');
        $this->assertFalse($manager->isAncestorOf($t5, $t3), 'child is not seen as an ancestor of its parent');
    }

    public function testMoveToFirstChildOf()
    {
        $manager = $this->getManager();
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
        try {
            $manager->moveToFirstChildOf($t3, $t5);
            $this->fail('moveToFirstChildOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToFirstChildOf() throws an exception when the target is a child node');
        }
        // moving down
        $manager->moveToFirstChildOf($t3, $t2);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 13, 1),
            't3' => array(3, 12, 2),
            't4' => array(4, 5, 3),
            't5' => array(6, 11, 3),
            't6' => array(7, 8, 4),
            't7' => array(9, 10, 4),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree down correctly');
        // moving up
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToFirstChildOf($t5, $t1);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(8, 9, 1),
            't3' => array(10, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(2, 7, 1),
            't6' => array(3, 4, 2),
            't7' => array(5, 6, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree up correctly');
        // moving to the same level
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToFirstChildOf($t5, $t3);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(5, 10, 2),
            't6' => array(6, 7, 3),
            't7' => array(8, 9, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToFirstChildOf() moves the entire subtree to the same level correctly');
    }

    public function testMoveToFirstChildOfWithScope()
    {
        $manager = $this->getManagerWithScope();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
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
        $this->assertEquals(13, $t3->getRightValue(), 't3 left has 13 per init');
        $this->assertEquals(1, $t10->getLevel(), 'Init level is 1');

        $manager->moveToFirstChildOf($t10, $t2);

        $this->assertEquals(2, $t2->getLeftValue(), 'As before');
        $this->assertEquals(5, $t2->getRightValue(), 'Extended by 2');

        $this->assertEquals(3, $t10->getLeftValue(), 'Moved into t2');
        $this->assertEquals(4, $t10->getRightValue(), 'Moved into t2');

        $this->assertEquals(2, $t10->getLevel(), 'New level is 2');

        $this->assertEquals($t2->getScopeValue(), $t10->getScopeValue(), 'Should have now the same scope');

        $expected = array(
            't8' => array(1, 4, 0),
            't9' => array(2, 3, 1),
        );

        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 't10 removed from scope 2, therefore t8 `right` has been changed');
        $this->assertEquals(15, $t3->getRightValue(), 't3 has shifted by one item, so from 13 to 15');

        //move t7 into t9, from scope 1 to scope 2
        $manager->moveToFirstChildOf($t7, $t9);

        $this->assertEquals(13, $t3->getRightValue(), 't3 `right` has now 15-2 => 13');
        $this->assertEquals(2, $t7->getScopeValue(), 't7 is now in scope 2');
        $this->assertEquals(6, $t8->getRightValue(), 't8 extended by 1 item, 4+2 => 6');
        $this->assertEquals(2, $t7->getLevel(), 'New level is 2');

        //dispose scope 2
        $oldt4Left = $t4->getLeftValue();

        $manager->moveToFirstChildOf($t8, $t3);

        $this->assertEquals($t3->getLeftValue()+1, $t8->getLeftValue(), 't8 has been moved to first children of t3');
        $this->assertEquals(19, $t3->getRightValue(), 't3 was extended for 3 more children, from 13+(3*2) to 19');
        $this->assertEquals($oldt4Left+(2*3), $t4->getLeftValue(), 't4 was moved by 3 items before it');
        $this->assertEquals(3, $t9->getLevel(), 'New level is 3');

        $expected = array();
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'root of scope 2 to scope 1, therefore scope 2 is empty');
    }

    public function testMoveToLastChildOf()
    {
        $manager = $this->getManager();
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
        try {
            $manager->moveToLastChildOf($t3, $t5);
            $this->fail('moveToLastChildOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToLastChildOf() throws an exception when the target is a child node');
        }
        // moving up
        $manager->moveToLastChildOf($t5, $t1);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 7, 1),
            't4' => array(5, 6, 2),
            't5' => array(8, 13, 1),
            't6' => array(9, 10, 2),
            't7' => array(11, 12, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree up correctly');
        // moving down
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToLastChildOf($t3, $t2);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 13, 1),
            't3' => array(3, 12, 2),
            't4' => array(4, 5, 3),
            't5' => array(6, 11, 3),
            't6' => array(7, 8, 4),
            't7' => array(9, 10, 4),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree down correctly');
        // moving to the same level
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToLastChildOf($t4, $t3);
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(5, 10, 2),
            't6' => array(6, 7, 3),
            't7' => array(8, 9, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToLastChildOf() moves the entire subtree to the same level correctly');
    }

    public function testMoveToLastChildOfWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertEquals(13, $t3->getRightValue(), 't3 left has 13 per init');

        $manager->moveToLastChildOf($t10, $t2);

        $this->assertEquals(2, $t2->getLeftValue(), 'As before');
        $this->assertEquals(5, $t2->getRightValue(), 'Extended by 2');

        $this->assertEquals(3, $t10->getLeftValue(), 'Moved into t2');
        $this->assertEquals(4, $t10->getRightValue(), 'Moved into t2');
        $this->assertEquals(2, $t10->getLevel(), 'New level is 2');

        $this->assertEquals($t2->getScopeValue(), $t10->getScopeValue(), 'Should have now the same scope');

        $expected = array(
            't8' => array(1, 4, 0),
            't9' => array(2, 3, 1),
        );

        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 't10 removed from scope 2, therefore t8 `right` has been changed');
        $this->assertEquals(15, $t3->getRightValue(), 't3 has shifted by one item, so from 13 to 15');

        //move t7 into t9, from scope 1 to scope 2
        $manager->moveToLastChildOf($t7, $t9);

        $this->assertEquals(13, $t3->getRightValue(), 't3 `right` has now 15-2 => 13');
        $this->assertEquals(2, $t7->getScopeValue(), 't7 is now in scope 2');
        $this->assertEquals(6, $t8->getRightValue(), 't8 extended by 1 item, 4+2 => 6');
        $this->assertEquals(2, $t7->getLevel(), 'New level is 2');

        //dispose scope 2
        $manager->moveToLastChildOf($t8, $t3);

        $this->assertEquals(13, $t8->getLeftValue(), 't8 has been moved to last children of t3');
        $this->assertEquals(19, $t3->getRightValue(), 't3 was extended for 3 more children, from 13+(3*2) to 19');
        $this->assertEquals(3, $t9->getLevel(), 'New level is 3');

        $expected = array();
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'root of scope 2 to scope 1, therefore scope 2 is empty');
    }

    public function testMoveToPrevSiblingOf()
    {
        $manager = $this->getManager();
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
        try {
            $manager->moveToPrevSiblingOf($t5, $t1);
            $this->fail('moveToPrevSiblingOf() throws an exception when the target is a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToPrevSiblingOf() throws an exception when the target is a root node');
        }
        try {
            $manager->moveToPrevSiblingOf($t5, $t6);
            $this->fail('moveToPrevSiblingOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToPrevSiblingOf() throws an exception when the target is a child node');
        }
        // moving up
        $manager->moveToPrevSiblingOf($t5, $t3);
        /* Results in
         t1
         | \     \
         t2 t5    t3
            | \    |
            t6 t7  t4
        */
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(10, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(4, 9, 1),
            't6' => array(5, 6, 2),
            't7' => array(7, 8, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree up correctly');
        // moving down
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
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
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(5, 10, 2),
            't6' => array(6, 7, 3),
            't7' => array(8, 9, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree down correctly');
        // moving at the same level
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToPrevSiblingOf($t4, $t5);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToPrevSiblingOf() moves the entire subtree at the same level correctly');
    }

    public function testMoveToPrevSiblingOfWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertEquals(13, $t3->getRightValue(), 't3 left has 13 per init');
        $this->assertEquals(2, $t2->getLeftValue(), 'Init');
        $this->assertEquals(3, $t2->getRightValue(), 'Init');

        $manager->moveToPrevSiblingOf($t10, $t2);

        $this->assertEquals(4, $t2->getLeftValue(), 'Move by one item, +2');
        $this->assertEquals(5, $t2->getRightValue(), 'Move by one item, +2');
        $this->assertEquals(1, $t10->getLevel(), 'Level is 1 as old');

        $this->assertEquals(2, $t10->getLeftValue(), 'Moved before t2');
        $this->assertEquals(3, $t10->getRightValue(), 'Moved before t2');

        $this->assertEquals($t2->getScopeValue(), $t10->getScopeValue(), 'Should have now the same scope');

        $expected = array(
            't8' => array(1, 4, 0),
            't9' => array(2, 3, 1),
        );

        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 't10 removed from scope 2, therefore t8 `right` has been changed');
        $this->assertEquals(15, $t3->getRightValue(), 't3 has shifted by one item, so from 13 to 15');

        //move t7 before t9, from scope 1 to scope 2
        $manager->moveToPrevSiblingOf($t7, $t9);

        $this->assertEquals(13, $t3->getRightValue(), 't3 `right` has now 15-2 => 13');
        $this->assertEquals(2, $t7->getScopeValue(), 't7 is now in scope 2');
        $this->assertEquals(6, $t8->getRightValue(), 't8 extended by 1 item, 4+2 => 6');
        $this->assertEquals(1, $t7->getLevel(), 'New level is 1');

        //dispose scope 2
        $manager->moveToPrevSiblingOf($t8, $t3);

        $this->assertEquals(6, $t8->getLeftValue(), 't8 has been moved to last children of t3');
        $this->assertEquals(19, $t3->getRightValue(), 't3 was moved for 3 item before it, so from 13+(3*2) to 19');
        $this->assertEquals(2, $t9->getLevel(), 'New level is 2');
        $this->assertEquals(1, $t8->getLevel(), 'New level is 1');

        $expected = array();
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'root of scope 2 to scope 1, therefore scope 2 is empty');
    }

    public function testMoveToNextSiblingOf()
    {
        $manager = $this->getManager();
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
        try {
            $manager->moveToNextSiblingOf($t5, $t1);
            $this->fail('moveToNextSiblingOf() throws an exception when the target is a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToNextSiblingOf() throws an exception when the target is a root node');
        }
        try {
            $manager->moveToNextSiblingOf($t5, $t6);
            $this->fail('moveToNextSiblingOf() throws an exception when the target is a child node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'moveToNextSiblingOf() throws an exception when the target is a child node');
        }
        // moving up
        $manager->moveToNextSiblingOf($t5, $t3);
        /* Results in
         t1
         | \   \
         t2 t3  t5
            |   | \
            t4  t6 t7
        */
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 7, 1),
            't4' => array(5, 6, 2),
            't5' => array(8, 13, 1),
            't6' => array(9, 10, 2),
            't7' => array(11, 12, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree up correctly');
        // moving down
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToNextSiblingOf($t5, $t4);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t4 t5
               |  \
               t6 t7
        */
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree down correctly');
        // moving at the same level
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $manager->moveToNextSiblingOf($t4, $t5);
        /* Results in
         t1
         |  \
         t2 t3
            |  \
            t5 t4
            | \
            t6 t7
        */
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(11, 12, 2),
            't5' => array(5, 10, 2),
            't6' => array(6, 7, 3),
            't7' => array(8, 9, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'moveToNextSiblingOf() moves the entire subtree at the same level correctly');
    }

    public function testMoveToNextSiblingOfWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertEquals(13, $t3->getRightValue(), 't3 left has 13 per init');
        $this->assertEquals(2, $t2->getLeftValue(), 'Init');
        $this->assertEquals(3, $t2->getRightValue(), 'Init');

        $manager->moveToNextSiblingOf($t10, $t2);

        $this->assertEquals(2, $t2->getLeftValue(), 'Same as before');
        $this->assertEquals(3, $t2->getRightValue(), 'Same as before');
        $this->assertEquals(1, $t10->getLevel(), 'Level is 1 as before');

        $this->assertEquals(6, $t3->getLeftValue(), 'Move by one item, +2');
        $this->assertEquals(15, $t3->getRightValue(), 'Move by one item, +2');

        $this->assertEquals(4, $t10->getLeftValue(), 'Moved after t2');
        $this->assertEquals(5, $t10->getRightValue(), 'Moved after t2');

        $this->assertEquals($t2->getScopeValue(), $t10->getScopeValue(), 'Should have now the same scope');

        $expected = array(
            't8' => array(1, 4, 0),
            't9' => array(2, 3, 1),
        );

        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 't10 removed from scope 2, therefore t8 `right` has been changed');
        $this->assertEquals(15, $t3->getRightValue(), 't3 has shifted by one item, so from 13 to 15');

        //move t7 after t9, from scope 1 to scope 2
        $manager->moveToNextSiblingOf($t7, $t9);

        $this->assertEquals(13, $t3->getRightValue(), 't3 `right` has now 15-2 => 13');
        $this->assertEquals(2, $t7->getScopeValue(), 't7 is now in scope 2');
        $this->assertEquals(6, $t8->getRightValue(), 't8 extended by 1 item, 4+2 => 6');
        $this->assertEquals(1, $t7->getLevel(), 'New level is 1');

        $this->assertEquals($t9->getRightValue()+1, $t7->getLeftValue(), 'Moved after t9, so we have t9.right+1 as left');

        //dispose scope 2
        $oldT1Right = $t1->getRightValue();
        $manager->moveToNextSiblingOf($t8, $t3);

        $this->assertEquals($oldT1Right+(2*3), $t1->getRightValue(), 't1 has been extended by 3 items');
        $this->assertEquals(13, $t3->getRightValue(), 't3 has no change.');
        $this->assertEquals(1, $t8->getLevel(), 'New level is 1');
        $this->assertEquals(2, $t9->getLevel(), 'New level is 2');

        $expected = array();
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'root of scope 2 to scope 1, therefore scope 2 is empty');
    }

    public function testMakeRoot()
    {
        $manager = $this->getManager();
        $t = new NestedSetEntity9();
        $manager->makeRoot($t);
        $this->assertEquals($t->getLeftValue(), 1, 'makeRoot() initializes left_field to 1');
        $this->assertEquals($t->getRightValue(), 2, 'makeRoot() initializes right_field to 2');
        $this->assertEquals($t->getLevel(), 0, 'makeRoot() initializes right_field to 0');
        $t = new NestedSetEntity9();
        $t->setLeftValue(12);
        try {
            $manager->makeRoot($t);
            $this->fail('makeRoot() throws an exception when called on an object with a left_field value');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'makeRoot() throws an exception when called on an object with a left_field value');
        }
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSaveRootInTreeWithExistingRoot()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity9();
        $manager->makeRoot($t1);
        $repository->save($t1);
        
        $t2 = new NestedSetEntity9();
        $manager->makeRoot($t2);
        $repository->save($t2);
        
    }

    public function testDeleteDescendants()
    {
        $manager = $this->getManager();
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
        $this->assertNull($manager->deleteDescendants($t2), 'deleteDescendants() returns null leafs');
        $this->assertEquals(4, $manager->deleteDescendants($t3), 'deleteDescendants() returns the number of deleted nodes');
        $this->assertEquals(5, $t3->getRightValue(), 'deleteDescendants() updates the current node');
        $this->assertEquals(5, $t4->getLeftValue(), 'deleteDescendants() does not update existing nodes (because delete() clears the instance cache)');

        $expected = array(
            't1' => array(1, 6, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'deleteDescendants() shifts the entire subtree correctly');
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
        $this->assertEquals(6, $manager->deleteDescendants($t1), 'deleteDescendants() can be called on the root node');
        $expected = array(
            't1' => array(1, 2, 0),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'deleteDescendants() can delete all descendants of the root node');
    }

    public function testDeleteDescendantsWithScope()
    {
        $manager = $this->getManagerWithScope();
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
        $this->assertEquals(4, $manager->deleteDescendants($t3), 'deleteDescendants() returns the number of deleted nodes');
        $expected = array(
            't1' => array(1, 6, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'deleteDescendants() shifts the entire subtree correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'deleteDescendants() does not delete anything out of the scope');
    }

    public function testIsValid()
    {
        $manager = $this->getManager();
        $this->assertFalse($manager->isValid(null), 'isValid() returns false when passed null ');
        $t1 = new NestedSetEntity9();
        $this->assertFalse($manager->isValid($t1), 'isValid() returns false when passed an empty node object');
        $t2 = new NestedSetEntity9();
        $t2->setLeftValue(5);
        $t2->setRightValue(2);
        $this->assertFalse($manager->isValid($t2), 'isValid() returns false when passed a node object with left > right');
        $t3 = new NestedSetEntity9();
        $t3->setLeftValue(5);
        $t3->setRightValue(5);
        $this->assertFalse($manager->isValid($t3), 'isValid() returns false when passed a node object with left = right');
        $t4 = new NestedSetEntity9();
        $t4->setLeftValue(2);
        $t4->setRightValue(5);
        $this->assertTrue($manager->isValid($t4), 'isValid() returns true when passed a node object with left < right');
    }

    public function testSaveOutOfTree()
    {
        $manager = $this->getManager();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity9::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity9();
        $t1->setTitle('t1');
        try {
            $repository->save($t1);
            
            $this->assertTrue(true, 'A node can be saved without valid tree information');
        } catch (Exception $e) {
            $this->fail('A node can be saved without valid tree information');
        }
        try {
            $manager->makeRoot($t1);
            $this->assertTrue(true, 'A saved node can be turned into root');
        } catch (Exception $e) {
            $this->fail('A saved node can be turned into root');
        }
        $repository->save($t1);
        
        $t2 = new NestedSetEntity9();
        $t2->setTitle('t1');
        $repository->save($t2);
        
        try {
            $manager->insertAsFirstChildOf($t2, $t1);
            $this->assertTrue(true, 'A saved node can be inserted into the tree');
        } catch (Exception $e) {
            $this->fail('A saved node can be inserted into the tree');
        }
        try {
            $repository->save($t2);
            
            $this->assertTrue(true, 'A saved node can be inserted into the tree');
        } catch (Exception $e) {
            $this->fail('A saved node can be inserted into the tree');
        }
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     * @expectedMessage A NestedSetEntity9 object must not be new to accept children.
     */
    public function testAddNestedSetChildrenOnNewEntityThrowsException()
    {
        $manager = $this->getManager();
        $t0 = new NestedSetEntity9();
        $t1 = new NestedSetEntity9();
        $manager->addChild($t0, $t1);
    }

    //Tests with scope

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSaveRootInTreeWithExistingRootWithSameScope()
    {
        $manager = $this->getManagerWithScope();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity10();
        $t1->setScopeValue(1);
        $manager->makeRoot($t1);
        $repository->save($t1);
        $t2 = new NestedSetEntity10();
        $t2->setScopeValue(1);
        $manager->makeRoot($t2);
        $repository->save($t2);
    }

    public function testSaveRootInTreeWithExistingRootWithDifferentScope()
    {
        $manager = $this->getManagerWithScope();
        $repository = $this->getConfiguration()->getRepository(NestedSetEntity10::class);
        $repository->deleteAll();

        $t1 = new NestedSetEntity10();
        $t1->setScopeValue(1);
        $manager->makeRoot($t1);
        $repository->save($t1);
        $t2 = new NestedSetEntity10();
        $t2->setScopeValue(2);
        $manager->makeRoot($t2);
        $repository->save($t2);
        $this->assertTrue(!$this->getConfiguration()->getSession()->isNew($t2));
    }

    public function testDelete()
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
        $session = $this->getConfiguration()->getSession();
        $session->remove($t5);
        $session->commit();

        $expected = array(
            't1' => array(1, 8, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 7, 1),
            't4' => array(5, 6, 2),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(1), 'delete() deletes all descendants and shifts the entire subtree correctly');
        $expected = array(
            't8' => array(1, 6, 0),
            't9' => array(2, 3, 1),
            't10' => array(4, 5, 1),
        );
        $this->assertEquals($expected, $this->dumpTreeWithScope(2), 'delete() does not delete anything out of the scope');
    }

    protected function getManager()
    {
        return $this->getConfiguration()->getRepository(NestedSetEntity9::class)->getNestedManager();
    }

    protected function getManagerWithScope()
    {
        return $this->getConfiguration()->getRepository(NestedSetEntity10::class)->getNestedManager();
    }

    protected function getByTitle($title)
    {
        return NestedSetEntity10Query::create()->filterByTitle($title)->findOne();
    }
}
