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
 * Tests for NestedSetBehaviorObjectBuilderModifier class
 *
 * @author		François Zaninotto
 * @version		$Revision: 1133 $
 * @package		generator.engine.behavior.nestedset
 */
class NestedSetBehaviorObjectBuilderModifierTest extends BookstoreNestedSetTestBase 
{
	public function testDefault()
	{
		$t = new Table9();
		$t->setTreeLeft('123');
		$this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
		$t->setTreeRight('456');
		$this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
	}
	
	public function testParameters()
	{
		$t = new Table10();
		$t->setMyLeftColumn('123');
		$this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
		$t->setMyRightColumn('456');
		$this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
		$t->setMyScopeColumn('789');
		$this->assertEquals($t->getScopeValue(), '789', 'nested_set adds a getScopeValue() method');
	}
	
	public function testObjectAttributes()
	{
		$expectedAttributes = array('level', 'hasPrevSibling', 'prevSibling', 'hasNextSibling', 'nextSibling', 'hasParentNode', 'parentNode', '_children');
		foreach ($expectedAttributes as $attribute) {
			$this->assertClassHasAttribute($attribute, 'Table9');
		}
	}
	
	public function testMakeRoot()
	{
		$t = new Table9();
		$t->makeRoot();
		$this->assertEquals($t->getLeftValue(), 1, 'makeRoot() initializes left_column to 1');
		$this->assertEquals($t->getRightValue(), 2, 'makeRoot() initializes right_column to 2');
		$t = new Table9();
		$t->setLeftValue(12);
		try {
			$t->makeRoot();
			$this->fail('makeRoot() throws an exception when called on an object with a left_column value');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'makeRoot() throws an exception when called on an object with a left_column value');
		}
		$t = new Table9();
		$t->createRoot();
		$this->assertEquals($t->getLeftValue(), 1, 'createRoot() is an alias for makeRoot()');
		$this->assertEquals($t->getRightValue(), 2, 'createRoot() is an alias for makeRoot()');
	}
	
	public function testSetParent()
	{
		Table9Peer::doDeleteAll();
		$t2 = new PublicTable9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->save();
		$this->assertNull($t2->parentNode, 'parentNode is null before calling setParent()');
		$t1 = new PublicTable9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2->setParent($t1);
		$this->assertEquals($t2->parentNode, $t1, 'setParent() writes parent node in cache');
	}
	
	public function testHasParent()
	{
		Table9Peer::doDeleteAll();
		$t0 = new Table9();	
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->save();
		$this->assertFalse($t0->hasParent(), 'empty node has no parent');	
		$this->assertFalse($t1->hasParent(), 'root node has no parent');
		$this->assertTrue($t2->hasParent(), 'not root node has a parent');
		$this->assertTrue($t3->hasParent(), 'leaf node has a parent');
	}
	
	public function testGetParent()
	{
		Table9Peer::doDeleteAll();
		$t0 = new Table9();
		$this->assertFalse($t0->hasParent(), 'empty node has no parent');		
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(8)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(7)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->save();
		$t4 = new Table9();
		$t4->setTitle('t4')->setLeftValue(5)->setRightValue(6)->save();
		$this->assertNull($t1->getParent($this->con), 'getParent() return null for root nodes');
		$this->assertEquals($t2->getParent($this->con), $t1, 'getParent() correctly retrieves parent for nodes');
		$this->assertEquals($t3->getParent($this->con), $t2, 'getParent() correctly retrieves parent for leafs');
		$this->assertEquals($t4->getParent($this->con), $t2, 'getParent() retrieves the same parent for two siblings');
		$count = $this->con->getQueryCount();
		$t4->getParent($this->con);
		$this->assertEquals($count, $this->con->getQueryCount(), 'getParent() uses an internal cache to avoid repeating queries');
	}

	public function testSetPrevSibling()
	{
		Table9Peer::doDeleteAll();
		$t2 = new PublicTable9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->save();
		$this->assertNull($t2->prevSibling, 'prevSibling is null before calling setPrevSibling()');
		$t1 = new PublicTable9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2->setPrevSibling($t1);
		$this->assertEquals($t2->prevSibling, $t1, 'setPrevSibling() writes previous sibling in cache');
	}
	
	public function testHasPrevSibling()
	{
		Table9Peer::doDeleteAll();
		$t0 = new Table9();	
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(3)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(4)->setRightValue(5)->save();
		$this->assertFalse($t0->hasPrevSibling(), 'empty node has no previous sibling');	
		$this->assertFalse($t1->hasPrevSibling(), 'root node has no previous sibling');
		$this->assertFalse($t2->hasPrevSibling(), 'first sibling has no previous sibling');
		$this->assertTrue($t3->hasPrevSibling(), 'not first sibling has a previous siblingt');
	}
	
	public function testGetPrevSibling()
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
		$this->assertNull($t1->getPrevSibling($this->con), 'getPrevSibling() returns null for root nodes');
		$this->assertNull($t2->getPrevSibling($this->con), 'getPrevSibling() returns null for first siblings');
		$this->assertEquals($t3->getPrevSibling($this->con), $t2, 'getPrevSibling() correctly retrieves prev sibling');
		$this->assertNull($t6->getPrevSibling($this->con), 'getPrevSibling() returns null for first siblings');
		$this->assertEquals($t7->getPrevSibling($this->con), $t6, 'getPrevSibling() correctly retrieves prev sibling');
		$count = $this->con->getQueryCount();
		$t7->getPrevSibling($this->con);
		$this->assertEquals($count, $this->con->getQueryCount(), 'getPrevSibling() uses an internal cache to avoid repeating queries');
	}

	public function testSetNextSibling()
	{
		Table9Peer::doDeleteAll();
		$t2 = new PublicTable9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->save();
		$this->assertNull($t2->nextSibling, 'nextSibling is null before calling setNextSibling()');
		$t1 = new PublicTable9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2->setNextSibling($t1);
		$this->assertEquals($t2->nextSibling, $t1, 'setNextSibling() writes next sibling in cache');
	}
	
	public function testHasNextSibling()
	{
		Table9Peer::doDeleteAll();
		$t0 = new Table9();	
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(3)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(4)->setRightValue(5)->save();
		$this->assertFalse($t0->hasNextSibling(), 'empty node has no next sibling');	
		$this->assertFalse($t1->hasNextSibling(), 'root node has no next sibling');
		$this->assertTrue($t2->hasNextSibling(), 'not last sibling has a next sibling');
		$this->assertFalse($t3->hasNextSibling(), 'last sibling has no next sibling');
	}
	
	public function testGetNextSibling()
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
		$this->assertNull($t1->getNextSibling($this->con), 'getNextSibling() returns null for root nodes');
		$this->assertEquals($t2->getNextSibling($this->con), $t3, 'getNextSibling() correctly retrieves next sibling');
		$this->assertNull($t3->getNextSibling($this->con), 'getNextSibling() returns null for last siblings');
		$this->assertEquals($t6->getNextSibling($this->con), $t7, 'getNextSibling() correctly retrieves next sibling');
		$this->assertNull($t7->getNextSibling($this->con), 'getNextSibling() returns null for last siblings');
		$count = $this->con->getQueryCount();
		$t6->getNextSibling($this->con);
		$this->assertEquals($count, $this->con->getQueryCount(), 'getNextSibling() uses an internal cache to avoid repeating queries');
	}

	public function testInsertAsFirstChildOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsFirstChildOf'), 'nested_set adds a insertAsFirstChildOf() method');
		$fixtures = $this->initTree();
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
		$t = $t8->insertAsFirstChildOf($fixtures[2]); // first child of t3
		$this->assertEquals($t, $t8, 'insertAsFirstChildOf() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 5, 'insertAsFirstChildOf() sets the left value correctly');
		$this->assertEquals($t8->getRightValue(), 6, 'insertAsFirstChildOf() sets the right value correctly');
		$this->assertEquals($t8->parentNode, $fixtures[2], 'insertAsFirstChildOf() sets the parent correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertAsFirstChildOf() shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertAsFirstChildOf($fixtures[4]);
			$this->fail('insertAsFirstChildOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsFirstChildOf() throws an exception when called on a saved object');
		}
	}
	
	public function testInsertAsLastChildOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsLastChildOf'), 'nested_set adds a insertAsLastChildOf() method');
		$fixtures = $this->initTree();
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
		$t = $t8->insertAsLastChildOf($fixtures[2]); // last child of t3
		$this->assertEquals($t, $t8, 'insertAsLastChildOf() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 13, 'insertAsLastChildOf() sets the left value correctly');
		$this->assertEquals($t8->getRightValue(), 14, 'insertAsLastChildOf() sets the right value correctly');
		$this->assertEquals($t8->parentNode, $fixtures[2], 'insertAsLastChildOf() sets the parent correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(5, 6),
			't5' => array(7, 12),
			't6' => array(8, 9),
			't7' => array(10, 11),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertAsLastChildOf() shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertAsLastChildOf($fixtures[4]);
			$this->fail('insertAsLastChildOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsLastChildOf() throws an exception when called on a saved object');
		}
	}
	
	public function testInsertAsPrevSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsPrevSiblingOf'), 'nested_set adds a insertAsPrevSiblingOf() method');
		$fixtures = $this->initTree();
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
		$t = $t8->insertAsPrevSiblingOf($fixtures[2]); // prev sibling of t3
		$this->assertEquals($t, $t8, 'insertAsPrevSiblingOf() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 4, 'insertAsPrevSiblingOf() sets the left value correctly');
		$this->assertEquals($t8->getRightValue(), 5, 'insertAsPrevSiblingOf() sets the right value correctly');
		$this->assertEquals($t8->nextSibling, $fixtures[2], 'insertAsPrevSiblingOf() sets the next sibling correctly');
		$this->assertEquals($fixtures[2]->prevSibling, $t8, 'insertAsPrevSiblingOf() sets the prev sibling correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(6, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertAsPrevSiblingOf() shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertAsPrevSiblingOf($fixtures[4]);
			$this->fail('insertAsPrevSiblingOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsPrevSiblingOf() throws an exception when called on a saved object');
		}
	}

	public function testInsertAsNextSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsNextSiblingOf'), 'nested_set adds a insertAsNextSiblingOf() method');
		$fixtures = $this->initTree();
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
		$t = $t8->insertAsNextSiblingOf($fixtures[2]); // next sibling of t3
		$this->assertEquals($t, $t8, 'insertAsNextSiblingOf() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 14, 'insertAsNextSiblingOf() sets the left value correctly');
		$this->assertEquals($t8->getRightValue(), 15, 'insertAsNextSiblingOf() sets the right value correctly');
		$this->assertEquals($t8->prevSibling, $fixtures[2], 'insertAsNextSiblingOf() sets the prev sibling correctly');
		$this->assertEquals($fixtures[2]->nextSibling, $t8, 'insertAsNextSiblingOf() sets the next sibling correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 13),
			't4' => array(5, 6),
			't5' => array(7, 12),
			't6' => array(8, 9),
			't7' => array(10, 11),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertAsPrevSiblingOf() shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertAsNextSiblingOf($fixtures[4]);
			$this->fail('insertAsNextSiblingOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsNextSiblingOf() throws an exception when called on a saved object');
		}
	}
	
	public function testInsertLeafAtPosition()
	{
		$this->assertTrue(method_exists('Table9', 'insertLeafAtPosition'), 'nested_set adds a insertLeafAtPosition() method');
		$fixtures = $this->initTree();
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
		$t = $t8->insertLeafAtPosition(5); // first child of t3
		$this->assertEquals($t, $t8, 'insertLeafAtPosition() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 5, 'insertLeafAtPosition() sets the left value from the first parameter');
		$this->assertEquals($t8->getRightValue(), 6, 'insertLeafAtPosition() sets the right value from the first parameter + 1');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertLeafAtPosition() shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertLeafAtPosition(6);
			$this->fail('insertLeafAtPosition() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertLeafAtPosition() throws an exception when called on a saved object');
		}
	}
}