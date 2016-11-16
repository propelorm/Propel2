<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\Base\BaseNestedSetEntity10Repository;
use Propel\Tests\Bookstore\Behavior\Base\BaseNestedSetEntity9Repository;
use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\MapNestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9;
use Propel\Tests\Bookstore\BehaviorBaseNestedSetEntity9Repository;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10;
use Propel\Tests\Bookstore\BehaviorNestedSetEntity9;

/**
 * Class NestedSetBehaviorRepositoryTest
 *
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 * @group database
 */
class NestedSetBehaviorRepositoryTest extends TestCase
{
    public function tearDown()
    {
        $this->clearEntityPool();
        $this->clearEntityPool(true);
        parent::tearDown();
    }

    public function testAttributes()
    {
        $repository = $this->getRepository();
        $this->assertObjectHasAttribute('nestedSetQueries', $repository);
        $this->assertObjectHasAttribute('nestedSetEntityPool', $repository);
    }

    public function testAddQueryThrowsExceptionIfMalformedQuery()
    {
        $repository = $this->getRepository();

        try {
            $repository->addNestedSetQuery(['foo' => 0, 'bar' => 1]);
            $this->fail('Wrong key names throw exception');
        } catch (PropelException $e) {
            $this->assertTrue(true);
        }

        try {
            $repository->addNestedSetQuery(['callable' => 0, 'bar' => 1]);
            $this->fail('Wrong key names throw exception');
        } catch (PropelException $e) {
            $this->assertTrue(true);
        }

        try {
            $repository->addNestedSetQuery(['callable' => 'function', 'attributes' => 1]);
            $this->fail('`attributes` key must be an array');
        } catch (PropelException $e) {
            $this->assertTrue(true);
        }
    }

    public function testAddQuery()
    {
        $evtDispatcherMock = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')->getMock();
        $configMock = $this->getMockBuilder('\Propel\Runtime\Configuration')
            ->setMethods(['getEventDispatcher'])
            ->getMock();
        $configMock->method('getEventDispatcher')->willReturn($evtDispatcherMock);
        $entityMapMock = $this->getMockBuilder(NestedSetEntity9EntityMap::class)->disableOriginalConstructor()->getMock();

        $repository = new BaseNestedSetEntity9Repository($entityMapMock, $configMock);

        $query = ['callable' => 'myCallable', 'arguments' => ['attr1' => 'foo', 'attr2' => true]];
        $repository->addNestedSetQuery($query);

        $refProperty = new \ReflectionProperty(BaseNestedSetEntity9Repository::class, 'nestedSetQueries');
        $refProperty->setAccessible(true);
        $queries = $refProperty->getValue($repository);

        $this->assertEquals($query, $queries[0]);
    }

    public function testAddEntityToPool()
    {
        $repository = $this->getRepository();

        $t = new NestedSetEntity9();
        $t->setTitle('Iron Man');
        $t1 = new NestedSetEntity9();
        $t1->setTitle('X-men');
        $repository->save($t);
        $repository->save($t1);

        $expected[$t->getId()] = $t;
        $expected[$t1->getId()] = $t1;

        $property = new \ReflectionProperty(BaseNestedSetEntity9Repository::class, 'nestedSetEntityPool');
        $property->setAccessible(true);
        $entityPool = $property->getValue($repository);
        $this->assertEquals($expected, $entityPool, 'Entity is correctly added to pool');
        $this->assertSame($expected[$t->getId()], $t, 'EntityPool references the added object');
        $this->assertSame($expected[$t1->getId()], $t1, 'EntityPool references the added object');
    }

    public function testRemoveEntityFromPool()
    {
        $repository = $this->getRepository();

        $t = new NestedSetEntity9();
        $t->setTitle('Iron Man');
        $t1 = new NestedSetEntity9();
        $t1->setTitle('X-men');
        $repository->save($t);
        $repository->save($t1);
        $repository->remove($t);

        $expected[$t1->getId()] = $t1;

        $property = new \ReflectionProperty(BaseNestedSetEntity9Repository::class, 'nestedSetEntityPool');
        $property->setAccessible(true);
        $entityPool = $property->getValue($repository);
        $this->assertEquals($expected, $entityPool, 'Entity correctly removed from entity pool');
        $this->assertSame($expected[$t1->getId()], $t1, 'EntityPool references the object');
    }

    public function testShiftRLValuesDelta()
    {
        $repository = $this->getRepository();

        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 1);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(2, 15, 0),
            't2' => array(3, 4, 1),
            't3' => array(5, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );

        $this->assertEquals($expected, $this->dumpTree(), 'shiftRLValues shifts all nodes with a positive amount');
        $this->initTree();
        $repository->shiftRLValues($delta = -1, $left = 1);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(0, 13, 0),
            't2' => array(1, 2, 1),
            't3' => array(3, 12, 1),
            't4' => array(4, 5, 2),
            't5' => array(6, 11, 2),
            't6' => array(7, 8, 3),
            't7' => array(9, 10, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues can shift all nodes with a negative amount');
        $this->initTree();
        $repository->shiftRLValues($delta = 3, $left = 1);
        $this->clearEntityPool();
        $expected = array(
            't1'=> array(4, 17, 0),
            't2' => array(5, 6, 1),
            't3' => array(7, 16, 1),
            't4' => array(8, 9, 2),
            't5' => array(10, 15, 2),
            't6' => array(11, 12, 3),
            't7' => array(13, 14, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes several units to the right');
        $repository->shiftRLValues($delta = -3, $left = 1);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes several units to the left');
    }

    public function testShiftRLValuesLeftLimit()
    {
        $repository = $this->getRepository();
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 15);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues does not shift anything when the left parameter is higher than the highest right value');
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 5);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 15, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts only the nodes having a LR value higher than the given left parameter');
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 1);
        $this->clearEntityPool();
        $expected = array(
            't1'=> array(2, 15, 0),
            't2' => array(3, 4, 1),
            't3' => array(5, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes when the left parameter is 1');
    }

    public function testShiftRLValuesRightLimit()
    {
        $repository = $this->getRepository();
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 1, $right = 0);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues does not shift anything when the right parameter is 0');
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 1, $right = 5);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(2, 14, 0),
            't2' => array(3, 4, 1),
            't3' => array(5, 13, 1),
            't4' => array(6, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shiftRLValues shifts only the nodes having a LR value lower than the given right parameter');
        $this->initTree();
        $repository->shiftRLValues($delta = 1, $left = 1, $right = 15);
        $this->clearEntityPool();
        $expected = array(
            't1'=> array(2, 15, 0),
            't2' => array(3, 4, 1),
            't3' => array(5, 14, 1),
            't4' => array(6, 7, 2),
            't5' => array(8, 13, 2),
            't6' => array(9, 10, 3),
            't7' => array(11, 12, 3),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues shifts all nodes when the right parameter is higher than the highest right value');
    }

    public function testShiftRLValuesWithScope()
    {
        $repository = $this->getRepositoryWithScope();
        $this->initTreeWithScope();
        $repository->shiftRLValues(1, 100, null, 1);
        $this->clearEntityPool(true);
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
        $repository->shiftRLValues(1, 1, null, 1);
        $this->clearEntityPool(true);
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
        $repository->shiftRLValues(-1, 1, null, 1);
        $this->clearEntityPool(true);
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
        $repository->shiftRLValues(1, 5, null, 1);
        $this->clearEntityPool(true);
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
        $repository = $this->getRepository();
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
        $repository->shiftLevel($delta = 1, $first = 7, $last = 12);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 3),
            't6' => array(8, 9, 4),
            't7' => array(10, 11, 4),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'shiftLevel shifts all nodes with a left value between the first and last');
        $this->initTree();
        $repository->shiftLevel($delta = -1, $first = 7, $last = 12);
        $this->clearEntityPool();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 1),
            't6' => array(8, 9, 2),
            't7' => array(10, 11, 2),
        );
        $this->assertEquals($this->dumpTree(), $expected, 'shiftLevel shifts all nodes wit ha negative amount');
    }

    public function testShiftLevelWithScope()
    {
        $repository = $this->getRepositoryWithScope();
        $this->initTreeWithScope();
        $repository->shiftLevel($delta = 1, $first = 7, $last = 12, $scope = 1);
        $this->clearEntityPool(true);
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

    public function testUpdateLoadedNodes()
    {
        $repository = $this->getRepository();
        $fixtures = $this->initTree();
        $repository->shiftRLValues(1, 5);
        $expected = array(
            't1' => array(1, 14),
            't2' => array(2, 3),
            't3' => array(4, 13),
            't4' => array(5, 6),
            't5' => array(7, 12),
            't6' => array(8, 9),
            't7' => array(10, 11),
        );
        $actual = array();
        foreach ($fixtures as $t) {
            $actual[$t->getTitle()] = array($t->getLeftValue(), $t->getRightValue());
        }
        $this->assertEquals($actual, $expected, 'Loaded nodes are not in sync before calling updateLoadedNodes()');
        $repository->updateLoadedNodes();
        $expected = array(
            't1' => array(1, 15),
            't2' => array(2, 3),
            't3' => array(4, 14),
            't4' => array(6, 7),
            't5' => array(8, 13),
            't6' => array(9, 10),
            't7' => array(11, 12),
        );
        $actual = array();
        foreach ($fixtures as $t) {
            $actual[$t->getTitle()] = array($t->getLeftValue(), $t->getRightValue());
        }
        $this->assertEquals($actual, $expected, 'Loaded nodes are in sync after calling updateLoadedNodes()');
    }

    public function testMakeRoomForLeaf()
    {
        $repository = $this->getRepository();
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
        $t = $repository->makeRoomForLeaf(5); // first child of t3
        $expected = array(
            't1' => array(1, 16, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 15, 1),
            't4' => array(7, 8, 2),
            't5' => array(9, 14, 2),
            't6' => array(10, 11, 3),
            't7' => array(12, 13, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'makeRoomForLeaf() shifts the other nodes correctly');
    }

    public function testMakeRoomForLeafWithScope()
    {
        $repository = $this->getRepositoryWithScope();
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
        $t = $repository->makeRoomForLeaf(5, 1); // first child of t3
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

    public function testFixLevels()
    {
        $repository = $this->getRepository();
        $fixtures = $this->initTree();

        // reset the levels
        foreach ($fixtures as $node) {
            $node->setLevel(null);
            $repository->save($node);
        }

        // fix the levels
        $repository->fixLevels();
        $expected = array(
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'fixLevels() fixes the levels correctly');

        $repository->fixLevels();
        $this->assertEquals($expected, $this->dumpTree(), 'fixLevels() can be called several times');
    }

    public function testPreUpdate()
    {
        $repository = $this->getRepository();
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        $t3->setLeftValue(null);
        try {
            $repository->save($t3);
            $this->fail('Trying to save a node incorrectly updated throws an exception');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Trying to save a node incorrectly updated throws an exception');
        }
    }

    public function testDelete()
    {
        $repository = $this->getRepository();
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
        $repository->remove($t5);
        //$this->assertEquals(13, $t3->getRightValue(), 'delete() does not update existing nodes (because delete() clears the instance cache)');
        $expected = array(
            't1' => array(1, 8, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 7, 1),
            't4' => array(5, 6, 2),
        );
        $this->assertEquals($expected, $this->dumpTree(), 'delete() deletes all descendants and shifts the entire subtree correctly');
        list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
        try {
            $repository->remove($t1);
            $this->fail('delete() throws an exception when called on a root node');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'delete() throws an exception when called on a root node');
        }
        $this->assertNotEquals(array(), $repository->createQuery()->find(), 'delete() called on the root node does not delete the whole tree');
    }

    public function DeleteNotInTree()
    {
        $repository = $this->getRepository();
        $t1 = new NestedSetEntity9();
        $repository->save($t1);
        $repository->remove($t1);
        $this->assertTrue($this->getConfiguration()->getSession()->isRemoved(spl_object_hash($t1)));
    }

    protected function clearEntityPool($scope = false)
    {
        if ($scope) {
            $repository = $this->getRepositoryWithScope();
            $repositoryClassName = BaseNestedSetEntity10Repository::class;
        } else {
            $repository = $this->getRepository();
            $repositoryClassName = BaseNestedSetEntity9Repository::class;
        }
        $method = new \ReflectionMethod($repositoryClassName, 'clearEntityPool');
        $method->setAccessible(true);
        $method->invoke($repository);
    }

    protected function getRepository()
    {
        return $this->getConfiguration()->getRepository(NestedSetEntity9::class);
    }

    protected function getRepositoryWithScope()
    {
        return $this->getConfiguration()->getRepository(NestedSetEntity10::class);
    }
}
