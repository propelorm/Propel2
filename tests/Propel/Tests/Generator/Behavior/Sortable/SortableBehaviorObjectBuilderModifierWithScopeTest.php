<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Tests\Bookstore\Behavior\SortableMultiScopes;
use Propel\Tests\Bookstore\Behavior\Map\SortableMultiScopesTableMap;
use Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopes;
use Propel\Tests\Bookstore\Behavior\Map\SortableMultiCommaScopesTableMap;

use Propel\Tests\Bookstore\Behavior\SortableTable12 as Table12;
use Propel\Tests\Bookstore\Behavior\SortableTable12Query;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable12TableMap;

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 *
 * @group database
 */
class SortableBehaviorObjectBuilderModifierWithScopeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateTable12();
    }

    public function testPreInsert()
    {
        SortableTable12TableMap::doDeleteAll();
        $t1 = new Table12();
        $t1->setScopeValue(1);
        $t1->save();
        $this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
        $t2 = new Table12();
        $t2->setScopeValue(1);
        $t2->save();
        $this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
        $t2 = new Table12();
        $t2->setScopeValue(2);
        $t2->save();
        $this->assertEquals($t2->getRank(), 1, 'Sortable inserts new line in last position');
    }

    public function testPreDelete()
    {
        $max = SortableTable12Query::create()->getMaxRank(1);
        $t3 = SortableTable12Query::retrieveByRank(3, 1);
        $t3->delete();
        $this->assertEquals($max - 1, SortableTable12Query::create()->getMaxRank(1), 'Sortable rearrange subsequent rows on delete');
        $t4 = SortableTable12Query::create()->filterByTitle('row4')->findOne();
        $this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'delete() leaves other suites unchanged');
    }

    public function testIsFirst()
    {
        $first = SortableTable12Query::retrieveByRank(1, 1);
        $middle = SortableTable12Query::retrieveByRank(2, 1);
        $last = SortableTable12Query::retrieveByRank(4, 1);
        $this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($middle->isFirst(), 'isFirst() returns false for a middle rank');
        $this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
        $first = SortableTable12Query::retrieveByRank(1, 2);
        $last = SortableTable12Query::retrieveByRank(2, 2);
        $this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
    }

    public function testIsLast()
    {
        $first = SortableTable12Query::retrieveByRank(1, 1);
        $middle = SortableTable12Query::retrieveByRank(2, 1);
        $last = SortableTable12Query::retrieveByRank(4, 1);
        $this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
        $this->assertFalse($middle->isLast(), 'isLast() returns false for a middle rank');
        $this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
        $first = SortableTable12Query::retrieveByRank(1, 2);
        $last = SortableTable12Query::retrieveByRank(2, 2);
        $this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
        $this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
    }

    public function testGetNext()
    {
        $t = SortableTable12Query::retrieveByRank(1, 1);
        $this->assertEquals('row2', $t->getNext()->getTitle(), 'getNext() returns the next object in rank in the same suite');
        $t = SortableTable12Query::retrieveByRank(1, 2);
        $this->assertEquals('row6', $t->getNext()->getTitle(), 'getNext() returns the next object in rank in the same suite');

        $t = SortableTable12Query::retrieveByRank(3, 1);
        $this->assertEquals(4, $t->getNext()->getRank(), 'getNext() returns the next object in rank');

        $t = SortableTable12Query::retrieveByRank(4, 1);
        $this->assertNull($t->getNext(), 'getNext() returns null for the last object');
    }

    public function testGetPrevious()
    {
        $t = SortableTable12Query::retrieveByRank(2, 1);
        $this->assertEquals('row1', $t->getPrevious()->getTitle(), 'getPrevious() returns the previous object in rank in the same suite');
        $t = SortableTable12Query::retrieveByRank(2, 2);
        $this->assertEquals('row5', $t->getPrevious()->getTitle(), 'getPrevious() returns the previous object in rank in the same suite');

        $t = SortableTable12Query::retrieveByRank(3, 1);
        $this->assertEquals(2, $t->getPrevious()->getRank(), 'getPrevious() returns the previous object in rank');

        $t = SortableTable12Query::retrieveByRank(1, 1);
        $this->assertNull($t->getPrevious(), 'getPrevious() returns null for the first object');
    }

    public function testInsertAtRank()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $t->insertAtRank(2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = array(1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() shifts the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testInsertAtRankNoScope()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->insertAtRank(2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($t->isNew(), 'insertAtRank() doesn\'t save the object');
        $t->save();
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
        $t = new Table12();
        $t->setScopeValue(1);
        $t->insertAtRank(0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtOverMaxRank()
    {
        $t = new Table12();
        $t->setScopeValue(1);
        $t->insertAtRank(6);
    }

    public function testInsertAtBottom()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $t->insertAtBottom();
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtBottom() does not shift the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtBottom() leaves other suites unchanged');
    }

    public function testInsertAtBottomNoScope()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->insertAtBottom();
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row10', 5 => 'new');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'insertAtBottom() does not shift the entire suite');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testInsertAtTop()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->setScopeValue(1);
        $t->insertAtTop();
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = array(1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtTop() shifts the entire suite');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtTop() leaves other suites unchanged');
    }

    public function testInsertAtTopNoScope()
    {
        $t = new Table12();
        $t->setTitle('new');
        $t->insertAtTop();
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = array(1 => 'new', 2 => 'row7', 3 => 'row8', 4 => 'row9', 5 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'insertAtTop() shifts the entire suite');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
    }

    public function testMoveToRank()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $t2->moveToRank(3);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move up');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToRank() leaves other suites unchanged');
        $t2->moveToRank(1);
        $expected = array(1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move to the first rank');
        $t2->moveToRank(4);
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move to the last rank');
        $t2->moveToRank(2);
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToRank() can move down');
    }

    public function testMoveToRankNoScope()
    {
        $t2 = SortableTable12Query::retrieveByRank(2);
        $t2->moveToRank(3);
        $expected = array(1 => 'row7', 2 => 'row9', 3 => 'row8', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move up');
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'insertAtRank() leaves other suites unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'insertAtRank() leaves other suites unchanged');
        $t2->moveToRank(1);
        $expected = array(1 => 'row8', 2 => 'row7', 3 => 'row9', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move to the first rank');
        $t2->moveToRank(4);
        $expected = array(1 => 'row7', 2 => 'row9', 3 => 'row10', 4 => 'row8');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move to the last rank');
        $t2->moveToRank(2);
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row10');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'moveToRank() can move down');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNewObject()
    {
        $t = new Table12();
        $t->moveToRank(2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNegativeRank()
    {
        $t = SortableTable12Query::retrieveByRank(2, 1);
        $t->moveToRank(0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToOverMaxRank()
    {
        $t = SortableTable12Query::retrieveByRank(2, 1);
        $t->moveToRank(5);
    }

    public function testSwapWith()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $t4 = SortableTable12Query::retrieveByRank(4, 1);
        $t2->swapWith($t4);
        $expected = array(1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'swapWith() leaves other suites unchanged');
    }

    public function testSwapWithBetweenScopes()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $t4 = SortableTable12Query::retrieveByRank(4);
        $t2->swapWith($t4);
        $expected = array(1 => 'row7', 2 => 'row8', 3 => 'row9', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(), 'swapWith() swaps ranks of the two objects between scopes and leaves the other ranks unchanged');
        $expected = array(1 => 'row1', 2 => 'row10', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'swapWith() swaps ranks of the two objects between scopes and leaves the other ranks unchanged');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'swapWith() leaves rest of suites unchanged');
    }

    public function testMoveUp()
    {
        $t3 = SortableTable12Query::retrieveByRank(3, 1);
        $res = $t3->moveUp();
        $this->assertEquals($t3, $res, 'moveUp() returns the current object');
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() swaps ranks with the object of higher rank');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveUp() leaves other suites unchanged');
        $t3->moveUp();
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() swaps ranks with the object of higher rank');
        $res = $t3->moveUp();
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveUp() changes nothing when called on the object at the top');
    }

    public function testMoveDown()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $res = $t2->moveDown();
        $this->assertEquals($t2, $res, 'moveDown() returns the current object');
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() swaps ranks with the object of lower rank');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveDown() leaves other suites unchanged');
        $t2->moveDown();
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() swaps ranks with the object of lower rank');
        $res = $t2->moveDown();
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveDown() changes nothing when called on the object at the bottom');
    }

    public function testMoveToTop()
    {
        $t3 = SortableTable12Query::retrieveByRank(3, 1);
        $res = $t3->moveToTop();
        $this->assertEquals($t3, $res, 'moveToTop() returns the current object');
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToTop() moves to the top');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToTop() leaves other suites unchanged');
        $res = $t3->moveToTop();
        $expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToTop() changes nothing when called on the top node');
    }

    public function testMoveToBottom()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $res = $t2->moveToBottom();
        $this->assertEquals($t2, $res, 'moveToBottom() returns the current object');
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToBottom() moves to the bottom');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'moveToBottom() leaves other suites unchanged');
        $res = $t2->moveToBottom();
        $expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'moveToBottom() changes nothing when called on the bottom node');
    }

    public function testRemoveFromList()
    {
        $t2 = SortableTable12Query::retrieveByRank(2, 1);
        $res = $t2->removeFromList();
        $this->assertTrue($res instanceof Table12, 'removeFromList() returns the current object');
        SortableTable12TableMap::clearInstancePool();
        $expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(1), 'removeFromList() does not change the list until the object is saved');
        $t2->save();
        SortableTable12TableMap::clearInstancePool();
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
        $t2 = SortableTable12Query::retrieveByRank(2);
        $t2->removeFromList();
    }

    /**
     * @return SortableMultiScopes[]
     */
    private function generateMultipleScopeEntries()
    {
        SortableMultiScopesTableMap::doDeleteAll();

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
            $item = new SortableMultiScopes();
            $item->setCategoryId($value[0]);
            $item->setSubCategoryId($value[1]);
            $item->setTitle($value[2]);
            $item->save();
            $result[] = $item;
        }

        return $result;
    }
    /**
     * @return SortableMultiCommaScopes[]
     */
    private function generateMultipleCommaScopeEntries()
    {
        SortableMultiCommaScopesTableMap::doDeleteAll();

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
            $item = new SortableMultiCommaScopes();
            $item->setCategoryId($value[0]);
            $item->setSubCategoryId($value[1]);
            $item->setTitle($value[2]);
            $item->save();
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
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->moveDown();
        $this->assertEquals($t1->getRank(), 2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->moveDown();
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $t1_1->moveUp(); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $t1_2->moveUp(); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 1);
    }

    public function testDeleteMultipleScopes()
    {
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->delete();

        $t1_1->reload();
        $t1_2->reload();
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
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleCommaScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->moveDown();
        $this->assertEquals($t1->getRank(), 2);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->moveDown();
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $t1_1->moveUp(); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);

        $t1_2->moveUp(); //no changes
        $this->assertEquals($t1->getRank(), 3);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 1);
    }

    public function testDeleteMultipleCommaScopes()
    {
        list($t1, $t2, $t3, $t3_1, $t1_1, $t1_2, $t1_3, $t1_4) = $this->generateMultipleCommaScopeEntries();

        $this->assertEquals($t1->getRank(), 1);
        $this->assertEquals($t1_1->getRank(), 2);
        $this->assertEquals($t1_2->getRank(), 3);

        $t1->delete();

        $t1_1->reload();
        $t1_2->reload();
        $this->assertEquals($t1_1->getRank(), 1);
        $this->assertEquals($t1_2->getRank(), 2);
    }

}
