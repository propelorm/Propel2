<?php

/*
 *	$Id: NestedSetBehaviorQueryBuilderModifierTest.php 1347 2009-12-03 21:06:36Z francois $
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
 * Tests for NestedSetBehaviorQueryBuilderModifier class with scope enabled
 *
 * @author		François Zaninotto
 * @version		$Revision$
 * @package		generator.behavior.nestedset
 */
class NestedSetBehaviorQueryBuilderModifierWithScopeTest extends BookstoreNestedSetTestBase 
{
	public function testTreeRoots()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->treeRoots()
			->find();
		$coll = $this->buildCollection(array($t1, $t8));
		$this->assertEquals($coll, $objs, 'treeRoots() filters by roots');
	}

	public function testInTree()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$tree = Table10Query::create()
			->inTree(1)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t1, $t2, $t3, $t4, $t5, $t6, $t7));
		$this->assertEquals($coll, $tree, 'inTree() filters by node');
		$tree = Table10Query::create()
			->inTree(2)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t8, $t9, $t10));
		$this->assertEquals($coll, $tree, 'inTree() filters by node');
	}
	
	public function testDescendantsOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->descendantsOf($t1)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t2, $t3, $t4, $t5, $t6, $t7));
		$this->assertEquals($coll, $objs, 'decendantsOf() filters by descendants of the same scope');
	}

	public function testBranchOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->branchOf($t1)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t1, $t2, $t3, $t4, $t5, $t6, $t7));
		$this->assertEquals($coll, $objs, 'branchOf() filters by branch of the same scope');

	}

	public function testChildrenOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->childrenOf($t1)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t2, $t3));
		$this->assertEquals($coll, $objs, 'childrenOf() filters by children of the same scope');
	}

	public function testSiblingsOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$desc = Table10Query::create()
			->siblingsOf($t3)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t2));
		$this->assertEquals($coll, $desc, 'siblingsOf() returns filters by siblings of the same scope');
	}

	public function testAncestorsOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->ancestorsOf($t5)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t1, $t3), 'ancestorsOf() filters by ancestors of the same scope');
	}

	public function testRootsOf()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$objs = Table10Query::create()
			->rootsOf($t5)
			->orderByBranch()
			->find();
		$coll = $this->buildCollection(array($t1, $t3, $t5), 'rootsOf() filters by ancestors of the same scope');
	}
	
	public function testFindRoot()
	{
		$this->assertTrue(method_exists('Table10Query', 'findRoot'), 'nested_set adds a findRoot() method');
		Table10Query::create()->deleteAll();
		$this->assertNull(Table10Query::create()->findRoot(1), 'findRoot() returns null as long as no root node is defined');
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$this->assertEquals($t1, Table10Query::create()->findRoot(1), 'findRoot() returns a tree root');
		$this->assertEquals($t8, Table10Query::create()->findRoot(2), 'findRoot() returns a tree root');
	}
	
	public function testFindTree()
	{
		list($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) = $this->initTreeWithScope();
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
		$tree = Table10Query::create()->findTree(1);
		$coll = $this->buildCollection(array($t1, $t2, $t3, $t4, $t5, $t6, $t7));
		$this->assertEquals($coll, $tree, 'findTree() retrieves the tree of a scope, ordered by branch');
		$tree = Table10Query::create()->findTree(2);
		$coll = $this->buildCollection(array($t8, $t9, $t10));
		$this->assertEquals($coll, $tree, 'findTree() retrieves the tree of a scope, ordered by branch');
	}
	
	protected function buildCollection($arr)
	{
		$coll = new PropelObjectCollection();
		$coll->setData($arr);
		$coll->setModel('Table10');
		
		return $coll;
	}
	
}
