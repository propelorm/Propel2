<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

/**
 * Tests for SortableManager class
 *
 * @author Massimiliano Arione
 * @author Cristiano Cinotti
 *
 */
class SortableManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateEntity11();
    }

    public function testIsFirst()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $first = $repository->createQuery()->findOneByRank(1);
        $middle = $repository->createQuery()->findOneByRank(2);
        $last = $repository->createQuery()->findOneByRank(4);
        $this->assertTrue($manager->isFirst($first), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($manager->isFirst($middle), 'isFirst() returns false for a middle rank');
        $this->assertFalse($manager->isFirst($last), 'isFirst() returns false for the last in the rank');
    }

    public function testIsLast()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $first = $repository->createQuery()->findOneByRank(1);
        $middle = $repository->createQuery()->findOneByRank(2);
        $last = $repository->createQuery()->findOneByRank(4);
        $this->assertFalse($manager->isLast($first), 'isLast() returns false for the first in the rank');
        $this->assertFalse($manager->isLast($middle), 'isLast() returns false for a middle rank');
        $this->assertTrue($manager->isLast($last), 'isLast() returns true for the last in the rank');
    }

    public function testGetNext()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = $repository->createQuery()->findOneByRank(3);
        $this->assertEquals(4, $manager->getNext($t)->getRank(), 'getNext() returns the next object in rank');

        $t = $repository->createQuery()->findOneByRank(4);
        $this->assertNull($manager->getNext($t), 'getNext() returns null for the last object');
    }

    public function testGetPrevious()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = $repository->createQuery()->findOneByRank(3);
        $this->assertEquals(2, $manager->getPrevious($t)->getRank(), 'getPrevious() returns the previous object in rank');

        $t = $repository->createQuery()->findOneByRank(1);
        $this->assertNull($manager->getPrevious($t), 'getPrevious() returns null for the first object');
    }

    public function testInsertAtRank()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity11();
        $t->setTitle('new');
        $manager->insertAtRank($t, 2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtRank() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() shifts the entire suite');
    }

    public function testInsertAtMaxRankPlusOne()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity11();
        $t->setTitle('new');
        $manager->insertAtRank($t, 5);
        $this->assertEquals(5, $t->getRank(), 'insertAtRank() sets the position');
        $repository->save($t);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() can insert an object at the end of the list');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtNegativeRank()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t = new \SortableEntity11();
        $manager->insertAtRank($t, 0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtOverMaxRank()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t = new \SortableEntity11();
        $manager->insertAtRank($t, 6);
    }

    public function testInsertAtBottom()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity11();
        $t->setTitle('new');
        $manager->insertAtBottom($t);
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($repository->getConfiguration()->getSession()->isNew($t), 'insertAtBottom() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtBottom() does not shift the entire suite');
    }

    public function testInsertAtTop()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity11();
        $t->setTitle('new');
        $manager->insertAtTop($t);
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($repository->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtTop() shifts the entire suite');
    }

    public function testMoveToRank()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2);
        $manager->moveToRank($t2, 3);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move up');
        $manager->moveToRank($t2, 1);
        $expected = array(1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the first rank');
        $manager->moveToRank($t2, 4);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the last rank');
        $manager->moveToRank($t2, 2);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move down');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNewObject()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t = new \SortableEntity11();
        $manager->moveToRank($t, 2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNegativeRank()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t = \SortableEntity11Query::create()->findOneByRank(2);
        $manager->moveToRank($t, 0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToOverMaxRank()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t = \SortableEntity11Query::create()->findOneByRank(2);
        $manager->moveToRank($t, 5);
    }

    public function testSwapWith()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $t4 = \SortableEntity11Query::create()->findOneByRank(4);
        $manager->swapWith($t2, $t4);
        $expected = array(1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
    }

    public function testMoveUp()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t3 = \SortableEntity11Query::create()->findOneByRank(3);
        $manager->moveUp($t3);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $manager->moveUp($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $manager->moveUp($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() changes nothing when called on the object at the top');
    }

    public function testMoveDown()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() changes nothing when called on the object at the bottom');
    }

    public function testMoveToTop()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t3 = \SortableEntity11Query::create()->findOneByRank(3);
        $manager->moveToTop($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() moves to the top');
        $manager->moveToTop($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() changes nothing when called on the top node');
    }

    public function testMoveToBottom()
    {
        $manager = $this->getRepository('\SortableEntity11')->getSortableManager();

        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $manager->moveToBottom($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() moves to the bottom');
        $manager->moveToBottom($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() changes nothing when called on the bottom node');
    }

    public function testRemoveFromList()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $manager = $repository->getSortableManager();

        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $manager->removeFromList($t2);
        $this->assertNull($t2->getRank(), 'removeFromList() resets the object\'s rank');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $repository->getConfiguration()->getSession()->clearFirstLevelCache();
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() does not change the list until the object is saved');
        $repository->save($t2);
        $expected = array(null => 'row2', 1 => 'row1', 2 => 'row3', 3 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() changes the list once the object is saved');
    }
}
