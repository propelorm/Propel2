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
class NestedSetBehaviorObjectBuilderModifierWithScopeTest extends BookstoreNestedSetTestBase 
{	
	protected function getByTitle($title)
	{
		$c = new Criteria();
		$c->add(Table10Peer::TITLE, $title);
		return Table10Peer::doSelectOne($c);
	}
	
	public function testGetParent()
	{
		$this->initTreeWithScope();
		$t1 = $this->getByTitle('t1');
		$this->assertNull($t1->getParent($this->con), 'getParent() return null for root nodes');
		$t2 = $this->getByTitle('t2');
		$this->assertEquals($t2->getParent($this->con), $t1, 'getParent() correctly retrieves parent for leafs');
		$t3 = $this->getByTitle('t3');
		$this->assertEquals($t3->getParent($this->con), $t1, 'getParent() correctly retrieves parent for nodes');
		$t4 = $this->getByTitle('t4');
		$this->assertEquals($t4->getParent($this->con), $t3, 'getParent() retrieves the same parent for nodes');
		$count = $this->con->getQueryCount();
		$t4->getParent($this->con);
		$this->assertEquals($count, $this->con->getQueryCount(), 'getParent() uses an internal cache to avoid repeating queries');
	}

	public function testInsertAsFirstChildOf()
	{
		$this->assertTrue(method_exists('Table10', 'insertAsFirstChildOf'), 'nested_set adds a insertAsFirstChildOf() method');
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
		$t11 = new Table10();
		$t11->setTitle('t11');
		$t11->insertAsFirstChildOf($fixtures[2]); // first child of t3
		$this->assertEquals($t11->getLeftValue(), 5, 'insertAsFirstChildOf() sets the left value correctly');
		$this->assertEquals($t11->getRightValue(), 6, 'insertAsFirstChildOf() sets the right value correctly');
		$this->assertEquals($t11->getScopeValue(), 1, 'insertAsFirstChildOf() sets the scope value correctly');
		$expected = array(
			't1' => array(1, 16),
			't2' => array(2, 3),
			't3' => array(4, 15),
			't4' => array(7, 8),
			't5' => array(9, 14),
			't6' => array(10, 11),
			't7' => array(12, 13),
		);
		$this->assertEquals($this->dumpTreeWithScope(1), $expected, 'insertAsFirstChildOf() sets left and right value and shifts the other nodes correctly');
		$expected = array(
			't8' => array(1, 6),
			't9' => array(2, 3),
			't10' => array(4, 5),
		);
		$this->assertEquals($this->dumpTreeWithScope(2), $expected, 'insertAsFirstChildOf does not shift anything out of the scope');
	}
}

// we need this class to test protected methods
class PublicTable10 extends Table10
{
	public $hasParentNode = null;
	public $parentNode = null;
}