<?php

/*
 *	$Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'tools/helpers/bookstore/behavior/BookstoreNestedSetTestBase.php';

/**
 * Tests for NestedSetBehaviorPeerBuilderModifier class
 *
 * @author		François Zaninotto
 * @version		$Revision: 1133 $
 * @package		generator.engine.behavior.nestedset
 */
class NestedSetBehaviorPeerBuilderModifierTest extends BookstoreNestedSetTestBase 
{	
	public function testConstants()
	{
		$this->assertEquals(Table9Peer::LEFT_COL, 'table9.TREE_LEFT', 'nested_set adds a LEFT_COL constant');
		$this->assertEquals(Table9Peer::RIGHT_COL, 'table9.TREE_RIGHT', 'nested_set adds a RIGHT_COL constant');
	}
	
	public function testRetrieveRoot()
	{
		$this->assertTrue(method_exists('Table9Peer', 'retrieveRoot'), 'nested_set adds a retrieveRoot() method');
		Table9Peer::doDeleteAll();
		$this->assertNull(Table9Peer::retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');		
		$t1 = new Table9();
		$t1->setLeftValue(123);
		$t1->setRightValue(456);
		$t1->save();
		$this->assertNull(Table9Peer::retrieveRoot(), 'retrieveRoot() returns null as long as no root node is defined');
		$t2 = new Table9();
		$t2->setLeftValue(1);
		$t2->setRightValue(2);
		$t2->save();
		$this->assertEquals(Table9Peer::retrieveRoot(), $t2, 'retrieveRoot() retrieves the root node');
	}
	
	public function testIsValid()
	{
		$this->assertTrue(method_exists('Table9Peer', 'isValid'), 'nested_set adds an isValid() method');
		$this->assertFalse(Table9Peer::isValid(null), 'isValid() returns false when passed null ');
		$t1 = new Table9();
		$this->assertFalse(Table9Peer::isValid($t1), 'isValid() returns false when passed an empty node object');
		$t2 = new Table9();
		$t2->setLeftValue(5)->setRightValue(2);
		$this->assertFalse(Table9Peer::isValid($t2), 'isValid() returns false when passed a node object with left > right');
		$t3 = new Table9();
		$t3->setLeftValue(5)->setRightValue(5);
		$this->assertFalse(Table9Peer::isValid($t3), 'isValid() returns false when passed a node object with left = right');
		$t4 = new Table9();
		$t4->setLeftValue(2)->setRightValue(5);
		$this->assertTrue(Table9Peer::isValid($t4), 'isValid() returns true when passed a node object with left < right');
	}
	
	public function testShiftRLValues()
	{
		$this->assertTrue(method_exists('Table9Peer', 'shiftRLValues'), 'nested_set adds a shiftRLValues() method');
		$this->initTree();
		Table9Peer::shiftRLValues(100, 1);
		$expected = array(
			't1' => array(1, 14),
			't2' => array(2, 3),
			't3' => array(4, 13),
			't4' => array(5, 6),
			't5' => array(7, 12),
			't6' => array(8, 9),
			't7' => array(10, 11),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues does not shift anything when the first parameter is higher than the highest right value');
		$this->initTree();
		Table9Peer::shiftRLValues(1, 1);
		$expected = array(
			't1'=> array(2, 15),
			't2' => array(3, 4),
			't3' => array(5, 14),
			't4' => array(6, 7),
			't5' => array(8, 13),
			't6' => array(9, 10),
			't7' => array(11, 12),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues can shift all nodes to the right');
		$this->initTree();
		Table9Peer::shiftRLValues(1, -1);
		$expected = array(
			't1' => array(0, 13),
			't2' => array(1, 2),
			't3' => array(3, 12),
			't4' => array(4, 5),
			't5' => array(6, 11),
			't6' => array(7, 8),
			't7' => array(9, 10),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues can shift all nodes to the left');
		$this->initTree();
		Table9Peer::shiftRLValues(5, 1);
		$expected = array(
			't1' => array(1, 15),
			't2' => array(2, 3),
			't3' => array(4, 14),
			't4' => array(6, 7),
			't5' => array(8, 13),
			't6' => array(9, 10),
			't7' => array(11, 12),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'shiftRLValues can shift some nodes to the right');
	}
	
	public function testUpdateLoadedNodes()
	{
		$this->assertTrue(method_exists('Table9Peer', 'updateLoadedNodes'), 'nested_set adds a updateLoadedNodes() method');
		$fixtures = $this->initTree();
		Table9Peer::shiftRLValues(5, 1);
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
		Table9Peer::updateLoadedNodes();
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
		$this->assertTrue(method_exists('Table9Peer', 'makeRoomForLeaf'), 'nested_set adds a makeRoomForLeaf() method');
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
		$t = Table9Peer::makeRoomForLeaf(5); // first child of t3
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($expected, $this->dumpTree(), 'makeRoomForLeaf() shifts the other nodes correctly');
		foreach ($expected as $key => $values)
		{
			$this->assertEquals($values, array($$key->getLeftValue(), $$key->getRightValue()), 'makeRoomForLeaf() updates nodes already in memory');
		}
	}
}
