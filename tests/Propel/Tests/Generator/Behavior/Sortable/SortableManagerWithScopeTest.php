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
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class SortableManagerWithScopeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateEntity12();
    }

    public function testIsFirst()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $first = $repository->createQuery()->findOneByRank(1, 1);
        $middle = $repository->createQuery()->findOneByRank(2, 1);
        $last = $repository->createQuery()->findOneByRank(4, 1);
        $this->assertTrue($manager->isFirst($first), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($manager->isFirst($middle), 'isFirst() returns false for a middle rank');
        $this->assertFalse($manager->isFirst($last), 'isFirst() returns false for the last in the rank');
        $first = $repository->createQuery()->findOneByRank(1, 2);
        $last = $repository->createQuery()->findOneByRank(2, 2);
        $this->assertTrue($manager->isFirst($first), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($manager->isFirst($middle), 'isFirst() returns false for the last in the rank');
    }

    public function testIsLast()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();
        
        $first = $repository->createQuery()->findOneByRank(1, 1);
        $middle = $repository->createQuery()->findOneByRank(2, 1);
        $last = $repository->createQuery()->findOneByRank(4, 1);
        $this->assertFalse($manager->isLast($first), 'isLast() returns false for the first in the rank');
        $this->assertFalse($manager->isLast($middle), 'isLast() returns false for a middle rank');
        $this->assertTrue($manager->isLast($last), 'isLast() returns true for the last in the rank');
        $first = $repository->createQuery()->findOneByRank(1, 2);
        $last = $repository->createQuery()->findOneByRank(2, 2);
        $this->assertFalse($manager->isLast($first), 'isLast() returns false for the first in the rank');
        $this->assertTrue($manager->isLast($last), 'isLast() returns true for the last in the rank');
    }

    public function testGetNext()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();
        
        $t = $repository->createQuery()->findOneByRank(1, 1);
        $this->assertEquals('row2', $manager->getNext($t)->getTitle(), 'getNext() returns the next object in rank in the same suite');
        $t = $repository->createQuery()->findOneByRank(1, 2);
        $this->assertEquals('row6', $manager->getNext($t)->getTitle(), 'getNext() returns the next object in rank in the same suite');

        $t = $repository->createQuery()->findOneByRank(3, 1);
        $this->assertEquals(4, $manager->getNext($t)->getRank(), 'getNext() returns the next object in rank');

        $t = $repository->createQuery()->findOneByRank(4, 1);
        $this->assertNull($manager->getNext($t), 'getNext() returns null for the last object');
    }

    public function testGetPrevious()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = $repository->createQuery()->findOneByRank(2, 1);
        $this->assertEquals('row1', $manager->getPrevious($t)->getTitle(), 'getPrevious() returns the previous object in rank in the same suite');
        $t = $repository->createQuery()->findOneByRank(2, 2);
        $this->assertEquals('row5', $manager->getPrevious($t)->getTitle(), 'getPrevious() returns the previous object in rank in the same suite');

        $t = $repository->createQuery()->findOneByRank(3, 1);
        $this->assertEquals(2, $manager->getPrevious($t)->getRank(), 'getPrevious() returns the previous object in rank');

        $t = $repository->createQuery()->findOneByRank(1, 1);
        $this->assertNull($manager->getPrevious($t), 'getPrevious() returns null for the first object');
    }

    public function testInsertAtRank()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $manager->insertAtRank($t, 2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() shifts the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testInsertAtRankNoScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $manager->insertAtRank($t, 2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($repository->getConfiguration()->getSession()->isNew($t), 'insertAtRank() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row7', 2 => 'new', 3 => 'row8', 4 => 'row9', 5 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'insertAtRank() shifts the entire suite');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtNegativeRank()
    {
        $t = new \SortableEntity12();
        $t->setScopeValue(1);
        $this->getRepository('\SortableEntity12')->getSortableManager()->insertAtRank($t, 0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtOverMaxRank()
    {
        $t = new \SortableEntity12();
        $t->setScopeValue(1);
        $this->getRepository('\SortableEntity12')->getSortableManager()->insertAtRank($t, 6);
    }

    public function testInsertAtBottom()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $manager->insertAtBottom($t);
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtBottom() does not shift the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtBottom() leaves other suites unchanged');
    }

    public function testInsertAtBottomNoScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $manager->insertAtBottom($t);
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row10', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'insertAtBottom() does not shift the entire suite');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testInsertAtTop()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $manager->insertAtTop($t);
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtTop() shifts the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtTop() leaves other suites unchanged');
    }

    public function testInsertAtTopNoScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = new \SortableEntity12();
        $t->setTitle('new');
        $manager->insertAtTop($t);
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($t), 'insertAtTop() doesn\'t save the object');
        $repository->save($t);
        $expected = array(1 => 'new', 2 => 'row7', 3 => 'row8', 4 => 'row9', 5 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'insertAtTop() shifts the entire suite');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testMoveToRank()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $manager->moveToRank($t2, 3);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move up');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToRank() leaves other suites unchanged');
        $manager->moveToRank($t2, 1);
        $expected = array(1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move to the first rank');
        $manager->moveToRank($t2, 4);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move to the last rank');
        $manager->moveToRank($t2, 2);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move down');
    }

    public function testMoveToRankNoScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2);
        $manager->moveToRank($t2, 3);
        $expected = array(1 => 'row7', 2 => 'row9', 3 => 'row8', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move up');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
        $manager->moveToRank($t2, 1);
        $expected = array(1 => 'row8', 2 => 'row7', 3 => 'row9', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move to the first rank');
        $manager->moveToRank($t2, 4);
        $expected = array(1 => 'row7', 2 => 'row9', 3 => 'row10', 4 => 'row8');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move to the last rank');
        $manager->moveToRank($t2, 2);
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move down');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNewObject()
    {
        $t = new \SortableEntity12();
        $this->getRepository('\SortableEntity12')->getSortableManager()->moveToRank($t, 2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNegativeRank()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = $repository->createQuery()->findOneByRank(2, 1);
        $manager->moveToRank($t, 0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToOverMaxRank()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t = $repository->createQuery()->findOneByRank(2, 1);
        $manager->moveToRank($t, 5);
    }

    public function testSwapWith()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $t4 = $repository->createQuery()->findOneByRank(4, 1);
        $manager->swapWith($t2, $t4);
        $expected = array(1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'swapWith() leaves other suites unchanged');
    }

    public function testSwapWithBetweenScopes()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $t4 = $repository->createQuery()->findOneByRank(4);
        $manager->swapWith($t2, $t4);
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'swapWith() swaps ranks of the two objects between scopes and leaves the other ranks unchanged');
        $expected = array(1 => 'row1', 2 => 'row10', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'swapWith() swaps ranks of the two objects between scopes and leaves the other ranks unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'swapWith() leaves rest of suites unchanged');
    }

    public function testMoveUp()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t3 = $repository->createQuery()->findOneByRank(3, 1);
        $manager->moveUp($t3);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() swaps ranks with the object of higher rank');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveUp() leaves other suites unchanged');
        $manager->moveUp($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() swaps ranks with the object of higher rank');
        $manager->moveUp($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() changes nothing when called on the object at the top');
    }

    public function testMoveDown()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() swaps ranks with the object of lower rank');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveDown() leaves other suites unchanged');
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() swaps ranks with the object of lower rank');
        $manager->moveDown($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() changes nothing when called on the object at the bottom');
    }

    public function testMoveToTop()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t3 = $repository->createQuery()->findOneByRank(3, 1);
        $manager->moveToTop($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToTop() moves to the top');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToTop() leaves other suites unchanged');
        $manager->moveToTop($t3);
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToTop() changes nothing when called on the top node');
    }

    public function testMoveToBottom()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $manager->moveToBottom($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToBottom() moves to the bottom');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToBottom() leaves other suites unchanged');
        $manager->moveToBottom($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToBottom() changes nothing when called on the bottom node');
    }

    public function testRemoveFromList()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();
        
        $t2 = $repository->createQuery()->findOneByRank(2, 1);
        $manager->removeFromList($t2);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'removeFromList() does not change the list until the object is saved');
        $repository->save($t2);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'removeFromList() changes the list and moves object to null scope once the object is saved');
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row10', 5 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'removeFromList() moves object to the end of null scope');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'removeFromList() leaves other suites unchanged');
    }

    /**
     * @expectedException Propel\Runtime\Exception\PropelException
     */
    public function testRemoveFromListNoScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $manager = $repository->getSortableManager();

        $t2 = $repository->createQuery()->findOneByRank(2);
        $manager->removeFromList($t2);
    }

    /**
     * @return SortableMultiScopes[]
     */
    private function generateMultipleScopeEntries()
    {
        $repository = $this->getRepository('\SortableMultiScopes');
        $repository->deleteAll();

        $items = array(
            //    cat scat title
            array(  1,  1,  'item 1'),  //1
            array(  2,  1,  'item 2'),  //1
            array(  3,  1,  'item 3'),  //1
            array(  3,  1,  'item 3.1'),//2
            array(  1,  1,  'item 1.1'),//2
            array(  1,  1,  'item 1.2'),//3
            array(  1,  2,  'item 1.3'),//1
            array(  1,  2,  'item 1.4'),//2
        );

        $result = array();
        foreach ($items as $value) {
            $item = new \SortableMultiScopes();
            $item->setCategoryId($value[0]);
            $item->setSubCategoryId($value[1]);
            $item->setTitle($value[2]);
            $repository->save($item);
            $result[] = $item;
        }

        return $result;
    }
    /**
     * @return SortableMultiCommaScopes[]
     */
    private function generateMultipleCommaScopeEntries()
    {
        $repository = $this->getRepository('\SortableMultiCommaScopes');
        $repository->deleteAll();

        $items = array(
            //    cat scat title
            array(  1,  1,  'item 1'),  //1
            array(  2,  1,  'item 2'),  //1
            array(  3,  1,  'item 3'),  //1
            array(  3,  1,  'item 3.1'),//2
            array(  1,  1,  'item 1.1'),//2
            array(  1,  1,  'item 1.2'),//3
            array(  1,  2,  'item 1.3'),//1
            array(  1,  2,  'item 1.4'),//2
        );

        $result = array();
        foreach ($items as $value) {
            $item = new \SortableMultiCommaScopes();
            $item->setCategoryId($value[0]);
            $item->setSubCategoryId($value[1]);
            $item->setTitle($value[2]);
            $repository->save($item);
            $result[] = $item;
        }

        return $result;
    }

    public function testMultipleScopes()
    {
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t2->getRank(), 1);

        $this->assertEquals($t3->getRank(), 1);
        $this->assertEquals($t3_1->getRank(), 2);

        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);
        $this->assertEquals($t1_3->getRank(), 1);
        $this->assertEquals($t1_4->getRank(), 2);

    }

    public function testMoveMultipleScopes()
    {
        $repository = $this->getRepository('\SortableMultiScopes');
        $manager = $repository->getSortableManager();

        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $manager->moveDown($t1);
        $this->assertEquals($t1->getRank(), 2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 3);

        $manager->moveDown($t1);
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $manager->moveUp($t1_1); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $manager->moveUp($t1_2); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 1);
    }

    public function testDeleteMultipleScopes()
    {
        $repository = $this->getRepository('\SortableMultiScopes');

        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $repository->remove($t1);

        $repository->getEntityMap()->load($t1_1);
        $repository->getEntityMap()->load($t1_2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);
    }

    public function testMultipleCommaScopes()
    {
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleCommaScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t2->getRank(), 1);

        $this->assertEquals($t3->getRank(), 1);
        $this->assertEquals($t3_1->getRank(), 2);

        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);
        $this->assertEquals($t1_3->getRank(), 1);
        $this->assertEquals($t1_4->getRank(), 2);
    }

    public function testMoveMultipleCommaScopes()
    {
        $repository = $this->getRepository('\SortableMultiCommaScopes');
        $manager = $repository->getSortableManager();

        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleCommaScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $manager->moveDown($t1);
        $this->assertEquals($t1->getRank(), 2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 3);

        $manager->moveDown($t1);
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $manager->moveUp($t1_1); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $manager->moveUp($t1_2); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 1);
    }

    public function testDeleteMultipleCommaScopes()
    {
        $repository = $this->getRepository('\SortableMultiCommaScopes');

        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleCommaScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $repository->remove($t1);

        $repository->getEntityMap()->load($t1_1);
        $repository->getEntityMap()->load($t1_2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);
    }
}
