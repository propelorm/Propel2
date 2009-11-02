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
		$t8 = new Table9();
		$t8->setTitle('t8');
		$t = $t8->insertAsFirstChildOf($fixtures[2]); // first child of t3
		$this->assertEquals($t, $t8, 'insertAsFirstChildOf() returns the object it was called on');
		$this->assertEquals($t8->getLeftValue(), 5, 'insertAsFirstChildOf() sets the left value correctly');
		$this->assertEquals($t8->getRightValue(), 6, 'insertAsFirstChildOf() sets the right value correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($this->dumpTree(), $expected, 'insertAsFirstChildOf() sets left and right value and shifts the other nodes correctly');
		$t8->save();
		try {
			$t8->insertAsFirstChildOf($fixtures[4]); // first child of t3
			$this->fail('insertAsFirstChildOf() throws an exception when called on a saved object');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'insertAsFirstChildOf() throws an exception when called on a saved object');
		}
	}
}

// we need this class to test protected methods
class PublicTable9 extends Table9
{
	public $hasParentNode = null;
	public $parentNode = null;
}