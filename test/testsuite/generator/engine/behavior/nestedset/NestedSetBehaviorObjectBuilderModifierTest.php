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
		$t->setLevel('789');
		$this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
	}
	
	public function testParameters()
	{
		$t = new Table10();
		$t->setMyLeftColumn('123');
		$this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
		$t->setMyRightColumn('456');
		$this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
		$t->setMyLevelColumn('789');
		$this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
		$t->setMyScopeColumn('012');
		$this->assertEquals($t->getScopeValue(), '012', 'nested_set adds a getScopeValue() method');
	}
	
	public function testObjectAttributes()
	{
		$expectedAttributes = array('hasPrevSibling', 'prevSibling', 'hasNextSibling', 'nextSibling', 'parentNode');
		foreach ($expectedAttributes as $attribute) {
			$this->assertClassHasAttribute($attribute, 'Table9');
		}
	}
	
	public function testSaveOutOfTree()
	{
		Table9Peer::doDeleteAll();
		$t1 = new Table9();
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
		$t2 = new Table9();
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
	
	public function testPreUpdate()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		$t3->setLeftValue(null);
		try {
			$t3->save();
			$this->fail('Trying to save a node incorrectly updated throws an exception');
		} catch (Exception $e) {
			$this->assertTrue(true, 'Trying to save a node incorrectly updated throws an exception');
		}
	}
	
	public function testDelete()
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
		$t5->delete();
		$this->assertEquals(13, $t3->getRightValue(), 'delete() does not update existing nodes (because delete() clears the instance cache)');
		$expected = array(
			't1' => array(1, 8, 0),
			't2' => array(2, 3, 1),
			't3' => array(4, 7, 1),
			't4' => array(5, 6, 2),
		);
		$this->assertEquals($expected, $this->dumpTree(), 'delete() deletes all descendants and shifts the entire subtree correctly');
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		try {
			$t1->delete();
			$this->fail('delete() throws an exception when called on a root node');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'delete() throws an exception when called on a root node');
		}
		$this->assertNotEquals(array(), Table9Peer::doSelect(new Criteria()), 'delete() called on the root node does not delete the whole tree');
	}
	
	public function testMakeRoot()
	{
		$t = new Table9();
		$t->makeRoot();
		$this->assertEquals($t->getLeftValue(), 1, 'makeRoot() initializes left_column to 1');
		$this->assertEquals($t->getRightValue(), 2, 'makeRoot() initializes right_column to 2');
		$this->assertEquals($t->getLevel(), 0, 'makeRoot() initializes right_column to 0');
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
		$this->assertEquals($t->getLevel(), 0, 'createRoot() is an alias for makeRoot()');
	}

	public function testIsInTree()
	{
		$t1 = new Table9();
		$this->assertFalse($t1->isInTree(), 'inInTree() returns false for nodes with no left and right value');
		$t1->save();
		$this->assertFalse($t1->isInTree(), 'inInTree() returns false for saved nodes with no left and right value');
		$t1->setLeftValue(1)->setRightValue(O);
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

	public function testIsRoot()
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
		$this->assertTrue($t1->isRoot(), 'root is seen as root');
		$this->assertFalse($t2->isRoot(), 'leaf is not seen as root');
		$this->assertFalse($t3->isRoot(), 'node is not seen as root');
	}
	
	public function testIsLeaf()
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
		$this->assertFalse($t1->isLeaf(), 'root is not seen as leaf');
		$this->assertTrue($t2->isLeaf(), 'leaf is seen as leaf');
		$this->assertFalse($t3->isLeaf(), 'node is not seen as leaf');
	}

	public function testIsDescendantOf()
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
		$this->assertFalse($t1->isDescendantOf($t1), 'root is not seen as a descendant of root');
		$this->assertTrue($t2->isDescendantOf($t1), 'direct child is seen as a descendant of root');
		$this->assertFalse($t1->isDescendantOf($t2), 'root is not seen as a descendant of leaf');
		$this->assertTrue($t5->isDescendantOf($t1), 'grandchild is seen as a descendant of root');
		$this->assertTrue($t5->isDescendantOf($t3), 'direct child is seen as a descendant of node');
		$this->assertFalse($t3->isDescendantOf($t5), 'node is not seen as a descendant of its parent');
	}
	
	public function testIsAncestorOf()
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
		$this->assertFalse($t1->isAncestorOf($t1), 'root is not seen as an ancestor of root');
		$this->assertTrue($t1->isAncestorOf($t2), 'root is seen as an ancestor of direct child');
		$this->assertFalse($t2->isAncestorOf($t1), 'direct child is not seen as an ancestor of root');
		$this->assertTrue($t1->isAncestorOf($t5), 'root is seen as an ancestor of grandchild');
		$this->assertTrue($t3->isAncestorOf($t5), 'parent is seen as an ancestor of node');
		$this->assertFalse($t5->isAncestorOf($t3), 'child is not seen as an ancestor of its parent');
	}
	
	public function testSetParent()
	{
		Table9Peer::doDeleteAll();
		$t2 = new PublicTable9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->setLevel(1)->save();
		$this->assertNull($t2->parentNode, 'parentNode is null before calling setParent()');
		$t1 = new PublicTable9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->setLevel(0)->save();
		$t2->setParent($t1);
		$this->assertEquals($t2->parentNode, $t1, 'setParent() writes parent node in cache');
	}
	
	public function testHasParent()
	{
		Table9Peer::doDeleteAll();
		$t0 = new Table9();	
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(6)->setLevel(0)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(5)->setLevel(1)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->setLevel(2)->save();
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
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(8)->setLevel(0)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(7)->setLevel(1)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(3)->setRightValue(4)->setLevel(2)->save();
		$t4 = new Table9();
		$t4->setTitle('t4')->setLeftValue(5)->setRightValue(6)->setLevel(2)->save();
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
	
	public function testHasChildren()
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
		$this->assertTrue($t1->hasChildren(), 'root has children');
		$this->assertFalse($t2->hasChildren(), 'leaf has no children');
		$this->assertTrue($t3->hasChildren(), 'node has children');
	}

	public function testGetChildren()
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
		$this->assertEquals(array(), $t2->getChildren(), 'getChildren() returns an empty array for leafs');
		$descendants = $t3->getChildren();
		$expected = array(
			't4' => array(5, 6, 2), 
			't5' => array(7, 12, 2), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants, true), 'getChildren() returns an array of children');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't5');
		$descendants = $t3->getDescendants($c);
		$expected = array(
			't5' => array(7, 12, 2), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants, true), 'getChildren() accepts a criteria as parameter');
	}
	
	public function testGetNumberOfChildren()
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
		$this->assertEquals(0, $t2->getNumberOfChildren(), 'getNumberOfChildren() returns 0 for leafs');
		$this->assertEquals(2, $t3->getNumberOfChildren(), 'getNumberOfChildren() returns the number of children');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't5');
		$this->assertEquals(1, $t3->getNumberOfChildren($c), 'getNumberOfChildren() accepts a criteria as parameter');
	}
	
	public function testGetFirstChild()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		$t5->moveToNextSiblingOf($t3);
		/* Results in
		 t1
		 | \   \
		 t2 t3  t5
		    |   | \
		    t4  t6 t7
		*/
		$this->assertEquals($t2, $t1->getFirstChild(), 'getFirstChild() returns the first child');
	}

	public function testGetLastChild()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		$t5->moveToNextSiblingOf($t3);
		/* Results in
		 t1
		 | \   \
		 t2 t3  t5
		    |   | \
		    t4  t6 t7
		*/
		$this->assertEquals($t5, $t1->getLastChild(), 'getLastChild() returns the last child');
	}
	
	public function testGetSiblings()
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
		$this->assertEquals(array(), $t1->getSiblings(), 'getSiblings() returns an empty array for root');
		$descendants = $t5->getSiblings();
		$expected = array(
			't4' => array(5, 6, 2), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getSiblings() returns an array of siblings');
		$descendants = $t5->getSiblings(true);
		$expected = array(
			't4' => array(5, 6, 2), 
			't5' => array(7, 12, 2), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getSiblings(true) includes the current node');
		$t5->moveToNextSiblingOf($t3);
		/* Results in
		 t1
		 | \   \
		 t2 t3  t5
		    |   | \
		    t4  t6 t7
		*/
		$this->assertEquals(array(), $t4->getSiblings(), 'getSiblings() returns an empty array for lone children');
		$descendants = $t3->getSiblings();
		$expected = array(
			't2' => array(2, 3, 1), 
			't5' => array(8, 13, 1), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getSiblings() returns all siblings');
	}
	
	public function testGetDescendants()
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
		$this->assertEquals(array(), $t2->getDescendants(), 'getDescendants() returns an empty array for leafs');
		$descendants = $t3->getDescendants();
		$expected = array(
			't4' => array(5, 6, 2), 
			't5' => array(7, 12, 2), 
			't6' => array(8, 9, 3), 
			't7' => array(10, 11, 3),
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() returns an array of descendants');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't5');
		$descendants = $t3->getDescendants($c);
		$expected = array(
			't5' => array(7, 12, 2), 
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getDescendants() accepts a criteria as parameter');
	}
	
	public function testGetNumberOfDescendants()
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
		$this->assertEquals(0, $t2->getNumberOfDescendants(), 'getNumberOfDescendants() returns 0 for leafs');
		$this->assertEquals(4, $t3->getNumberOfDescendants(), 'getNumberOfDescendants() returns the number of descendants');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't5');
		$this->assertEquals(1, $t3->getNumberOfDescendants($c), 'getNumberOfDescendants() accepts a criteria as parameter');
	}
	
	public function testGetBranch()
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
		$this->assertEquals(array($t2), $t2->getBranch(), 'getBranch() returns the current node for leafs');
		$descendants = $t3->getBranch();
		$expected = array(
			't3' => array(4, 13, 1),
			't4' => array(5, 6, 2), 
			't5' => array(7, 12, 2), 
			't6' => array(8, 9, 3), 
			't7' => array(10, 11, 3),
		);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() returns an array of descendants, uncluding the current node');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't3', Criteria::NOT_EQUAL);
		$descendants = $t3->getBranch($c);
		unset($expected['t3']);
		$this->assertEquals($expected, $this->dumpNodes($descendants), 'getBranch() accepts a criteria as first parameter');
	}
	
	public function testGetAncestors()
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
		$this->assertEquals(array(), $t1->getAncestors(), 'getAncestors() returns an empty array for roots');
		$ancestors = $t5->getAncestors();
		$expected = array(
			't1' => array(1, 14, 0),
			't3' => array(4, 13, 1),
		);
		$this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() returns an array of ancestors');
		$c = new Criteria();
		$c->add(Table9Peer::TITLE, 't3');
		$ancestors = $t5->getAncestors($c);
		$expected = array(
			't3' => array(4, 13, 1),
		);
		$this->assertEquals($expected, $this->dumpNodes($ancestors), 'getAncestors() accepts a criteria as parameter');
	}
	
	public function testAddChild()
	{
		Table9Peer::doDeleteAll();
		$t1 = new Table9();
		$t1->setTitle('t1');
		$t1->makeRoot();
		$t1->save();
		$t2 = new Table9();
		$t2->setTitle('t2');
		$t1->addChild($t2);
		$t2->save();
		$t3 = new Table9();
		$t3->setTitle('t3');
		$t1->addChild($t3);
		$t3->save();
		$t4 = new Table9();
		$t4->setTitle('t4');
		$t2->addChild($t4);
		$t4->save();
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
		$this->assertTrue(method_exists('Table9', 'insertAsFirstChildOf'), 'nested_set adds a insertAsFirstChildOf() method');
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
		$t8 = new PublicTable9();
		$t8->setTitle('t8');
		$t = $t8->insertAsFirstChildOf($t3);
		$this->assertEquals($t8, $t, 'insertAsFirstChildOf() returns the object it was called on');
		$this->assertEquals(5, $t4->getLeftValue(), 'insertAsFirstChildOf() does not modify the tree until the object is saved');
		$t8->save();
		$this->assertEquals(5, $t8->getLeftValue(), 'insertAsFirstChildOf() sets the left value correctly');
		$this->assertEquals(6, $t8->getRightValue(), 'insertAsFirstChildOf() sets the right value correctly');
		$this->assertEquals(2, $t8->getLevel(), 'insertAsFirstChildOf() sets the level correctly');
		$this->assertEquals($t3, $t8->parentNode, 'insertAsFirstChildOf() sets the parent correctly');
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
			$t8->insertAsFirstChildOf($t4);
			$this->fail('insertAsFirstChildOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsFirstChildOf() throws an exception when called on a saved object');
		}
	}
	
	public function testInsertAsLastChildOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsLastChildOf'), 'nested_set adds a insertAsLastChildOf() method');
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
		$t8 = new PublicTable9();
		$t8->setTitle('t8');
		$t = $t8->insertAsLastChildOf($t3);
		$this->assertEquals($t8, $t, 'insertAsLastChildOf() returns the object it was called on');
		$this->assertEquals(13, $t3->getRightValue(), 'insertAsLastChildOf() does not modify the tree until the object is saved');
		$t8->save();
		$this->assertEquals(13, $t8->getLeftValue(), 'insertAsLastChildOf() sets the left value correctly');
		$this->assertEquals(14, $t8->getRightValue(), 'insertAsLastChildOf() sets the right value correctly');
		$this->assertEquals(2, $t8->getLevel(), 'insertAsLastChildOf() sets the level correctly');
		$this->assertEquals($t3, $t8->parentNode, 'insertAsLastChildOf() sets the parent correctly');
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
			$t8->insertAsLastChildOf($t4);
			$this->fail('insertAsLastChildOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsLastChildOf() throws an exception when called on a saved object');
		}
	}
	
	public function testInsertAsPrevSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsPrevSiblingOf'), 'nested_set adds a insertAsPrevSiblingOf() method');
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
		$t8 = new PublicTable9();
		$t8->setTitle('t8');
		$t = $t8->insertAsPrevSiblingOf($t3);
		$this->assertEquals($t8, $t, 'insertAsPrevSiblingOf() returns the object it was called on');
		$this->assertEquals(4, $t3->getLeftValue(), 'insertAsPrevSiblingOf() does not modify the tree until the object is saved');
		$t8->save();
		$this->assertEquals(4, $t8->getLeftValue(), 'insertAsPrevSiblingOf() sets the left value correctly');
		$this->assertEquals(5, $t8->getRightValue(), 'insertAsPrevSiblingOf() sets the right value correctly');
		$this->assertEquals(1, $t8->getLevel(), 'insertAsPrevSiblingOf() sets the level correctly');
		$this->assertEquals($t3, $t8->nextSibling, 'insertAsPrevSiblingOf() sets the next sibling correctly');
		$this->assertEquals($t8, $t3->prevSibling, 'insertAsPrevSiblingOf() sets the prev sibling correctly');
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
			$t8->insertAsPrevSiblingOf($t4);
			$this->fail('insertAsPrevSiblingOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsPrevSiblingOf() throws an exception when called on a saved object');
		}
	}

	public function testInsertAsNextSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'insertAsNextSiblingOf'), 'nested_set adds a insertAsNextSiblingOf() method');
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
		$t8 = new PublicTable9();
		$t8->setTitle('t8');
		$t = $t8->insertAsNextSiblingOf($t3);
		$this->assertEquals($t8, $t, 'insertAsNextSiblingOf() returns the object it was called on');
		$this->assertEquals(14, $t1->getRightValue(), 'insertAsNextSiblingOf() does not modify the tree until the object is saved');
		$t8->save();
		$this->assertEquals(14, $t8->getLeftValue(), 'insertAsNextSiblingOf() sets the left value correctly');
		$this->assertEquals(15, $t8->getRightValue(), 'insertAsNextSiblingOf() sets the right value correctly');
		$this->assertEquals(1, $t8->getLevel(), 'insertAsNextSiblingOf() sets the level correctly');
		$this->assertEquals($t3, $t8->prevSibling, 'insertAsNextSiblingOf() sets the prev sibling correctly');
		$this->assertEquals($t8, $t3->nextSibling, 'insertAsNextSiblingOf() sets the next sibling correctly');
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
			$t8->insertAsNextSiblingOf($t4);
			$this->fail('insertAsNextSiblingOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsNextSiblingOf() throws an exception when called on a saved object');
		}
	}
	
	public function testMoveToFirstChildOf()
	{
		$this->assertTrue(method_exists('Table9', 'moveToFirstChildOf'), 'nested_set adds a moveToFirstChildOf() method');
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
			$t3->moveToFirstChildOf($t5);
			$this->fail('moveToFirstChildOf() throws an exception when the target is a child node');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'moveToFirstChildOf() throws an exception when the target is a child node');
		}
		// moving down
		$t = $t3->moveToFirstChildOf($t2);
		$this->assertEquals($t3, $t, 'moveToFirstChildOf() returns the object it was called on');
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
		$this->assertEquals($t2, $t3->parentNode, 'moveToFirstChildOf() sets the parent correctly');
		// moving up
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		$t5->moveToFirstChildOf($t1);
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
		$t5->moveToFirstChildOf($t3);
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

	public function testMoveToLastChildOf()
	{
		$this->assertTrue(method_exists('Table9', 'moveToLastChildOf'), 'nested_set adds a moveToLastChildOf() method');
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
			$t3->moveToLastChildOf($t5);
			$this->fail('moveToLastChildOf() throws an exception when the target is a child node');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'moveToLastChildOf() throws an exception when the target is a child node');
		}
		// moving up
		$t = $t5->moveToLastChildOf($t1);
		$this->assertEquals($t5, $t, 'moveToLastChildOf() returns the object it was called on');
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
		$this->assertEquals($t1, $t5->parentNode, 'moveToFirstChildOf() sets the parent correctly');
		// moving down
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7) = $this->initTree();
		$t3->moveToLastChildOf($t2);
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
		$t4->moveToLastChildOf($t3);
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

	public function testMoveToPrevSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'moveToPrevSiblingOf'), 'nested_set adds a moveToPrevSiblingOf() method');
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
		$expected = array(
			't1' => array(1, 14, 0),
			't2' => array(2, 3, 1),
			't3' => array(10, 13, 1),
			't4' => array(11, 12, 2),
			't5' => array(4, 9, 1),
			't6' => array(5, 6, 2),
			't7' => array(7, 8, 2),
		);
		$this->assertEquals($t3, $t5->nextSibling, 'moveToPrevSiblingOf() sets the next sibling correctly');
		$this->assertEquals($t5, $t3->prevSibling, 'moveToPrevSiblingOf() sets the prev sibling correctly');
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
	
	public function testMoveToNextSiblingOf()
	{
		$this->assertTrue(method_exists('Table9', 'moveToNextSiblingOf'), 'nested_set adds a moveToNextSiblingOf() method');
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
		$this->assertEquals($t3, $t5->prevSibling, 'moveToNextSiblingOf() sets the prev sibling correctly');
		$this->assertEquals($t5, $t3->nextSibling, 'moveToNextSiblingOf() sets the next sibling correctly');
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
	
	public function testDeleteDescendants()
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
		$this->assertNull($t2->deleteDescendants(), 'deleteDescendants() returns null leafs');
		$this->assertEquals(4, $t3->deleteDescendants(), 'deleteDescendants() returns the number of deleted nodes');
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
		$this->assertEquals(6, $t1->deleteDescendants(), 'deleteDescendants() can be called on the root node');
		$expected = array(
			't1' => array(1, 2, 0),
		);
		$this->assertEquals($expected, $this->dumpTree(), 'deleteDescendants() can delete all descendants of the root node');
	}
}