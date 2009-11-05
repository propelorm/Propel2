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
class NestedSetBehaviorPeerBuilderModifierWithScopeTest extends BookstoreNestedSetTestBase 
{
	public function testConstants()
	{
		$this->assertEquals(Table10Peer::LEFT_COL, 'table10.MY_LEFT_COLUMN', 'nested_set adds a LEFT_COL constant using the custom left_column parameter');
		$this->assertEquals(Table10Peer::RIGHT_COL, 'table10.MY_RIGHT_COLUMN', 'nested_set adds a RIGHT_COL constant using the custom right_column parameter');
		$this->assertEquals(Table10Peer::SCOPE_COL, 'table10.MY_SCOPE_COLUMN', 'nested_set adds a SCOPE_COL constant when the use_scope parameter is true');
	}

	public function testRetrieveRoot()
	{
		$this->assertTrue(method_exists('Table10Peer', 'retrieveRoot'), 'nested_set adds a retrieveRoot() method');
		Table10Peer::doDeleteAll();	
		$t1 = new Table10();
		$t1->setLeftValue(1);
		$t1->setRightValue(2);
		$t1->setScopeValue(2);
		$t1->save();
		$this->assertNull(Table10Peer::retrieveRoot(1), 'retrieveRoot() returns null as long as no root node is defined in the required scope');
		$t2 = new Table10();
		$t2->setLeftValue(1);
		$t2->setRightValue(2);
		$t2->setScopeValue(1);
		$t2->save();
		$this->assertEquals(Table10Peer::retrieveRoot(1), $t2, 'retrieveRoot() retrieves the root node in the required scope');
	}
	
	public function testDeleteTree()
	{
		$this->initTreeWithScope();
		Table10Peer::deleteTree(1);
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'deleteTree() does not delete anything out of the scope');
	}

	public function testShiftRLValues()
	{
		$this->assertTrue(method_exists('Table10Peer', 'shiftRLValues'), 'nested_set adds a shiftRLValues() method');
		$this->initTreeWithScope();
		Table10Peer::shiftRLValues(1, 100, null, 1);
		$expected = array(
			't1' => array(1, 14),
			't2' => array(2, 3),
			't3' => array(4, 13),
			't4' => array(5, 6),
			't5' => array(7, 12),
			't6' => array(8, 9),
			't7' => array(10, 11),
		);
		$this->assertEquals($this->dumpTreeWithScope(1), $expected, 'shiftRLValues does not shift anything when the first parameter is higher than the highest right value');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'shiftRLValues does not shift anything out of the scope');
		$this->initTreeWithScope();
		Table10Peer::shiftRLValues(1, 1, null, 1);
		$expected = array(
			't1' => array(2, 15),
			't2' => array(3, 4),
			't3' => array(5, 14),
			't4' => array(6, 7),
			't5' => array(8, 13),
			't6' => array(9, 10),
			't7' => array(11, 12),
		);
		$this->assertEquals($this->dumpTreeWithScope(1), $expected, 'shiftRLValues can shift all nodes to the right');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'shiftRLValues does not shift anything out of the scope');
		$this->initTreeWithScope();
		Table10Peer::shiftRLValues(-1, 1, null, 1);
		$expected = array(
			't1' => array(0, 13),
			't2' => array(1, 2),
			't3' => array(3, 12),
			't4' => array(4, 5),
			't5' => array(6, 11),
			't6' => array(7, 8),
			't7' => array(9, 10),
		);
		$this->assertEquals($this->dumpTreeWithScope(1), $expected, 'shiftRLValues can shift all nodes to the left');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'shiftRLValues does not shift anything out of the scope');
		$this->initTreeWithScope();
		Table10Peer::shiftRLValues(1, 5, null, 1);
		$expected = array(
			't1' => array(1, 15),
			't2' => array(2, 3),
			't3' => array(4, 14),
			't4' => array(6, 7),
			't5' => array(8, 13),
			't6' => array(9, 10),
			't7' => array(11, 12),
		);
		$this->assertEquals($this->dumpTreeWithScope(1), $expected, 'shiftRLValues can shift some nodes to the right');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'shiftRLValues does not shift anything out of the scope');
	}

	public function testMakeRoomForLeaf()
	{
		$this->assertTrue(method_exists('Table10Peer', 'makeRoomForLeaf'), 'nested_set adds a makeRoomForLeaf() method');
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
		$t = Table10Peer::makeRoomForLeaf(5, 1); // first child of t3
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($expected, $this->dumpTreeWithScope(1), 'makeRoomForLeaf() shifts the other nodes correctly');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($expected, $this->dumpTreeWithScope(2), 'makeRoomForLeaf() does not shift anything out of the scope');
	}
}