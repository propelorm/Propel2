<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

class BookstoreNestedSetTestBase extends BookstoreTestBase
{
	public function dumpNodes($nodes)
	{
		$tree = array();
		foreach ($nodes as $node) {
			$tree[$node->getTitle()] = array($node->getLeftValue(), $node->getRightValue());
		}
		return $tree;
	}
	
	/**
	 * Tree used for tests
	 * t1
	 * |  \
	 * t2 t3
	 *    |  \
	 *    t4 t5
	 *       |  \
	 *       t6 t7
	 */
	protected function initTree()
	{
		Table9Peer::doDeleteAll();
		$ret = array();
		$fixtures = array(
			't1' => array(1, 14),
			't2' => array(2, 3), 
			't3' => array(4, 13), 
			't4' => array(5, 6), 
			't5' => array(7, 12), 
			't6' => array(8, 9), 
			't7' => array(10, 11),
		);
		foreach ($fixtures as $key => $data) {
			$t = new PublicTable9();
			$t->setTitle($key);
			$t->setLeftValue($data[0]);
			$t->setRightValue($data[1]);
			$t->save();
			$ret []= $t;
		}
		return $ret;
	}
	
	protected function dumpTree()
	{
		Table9Peer::clearInstancePool();
		$c = new Criteria();
		$c->addAscendingOrderBycolumn(Table9Peer::TITLE);
		return $this->dumpNodes(Table9Peer::doSelect($c));
	}
	
	/**
	 * Tree used for tests
	 * Scope 1
	 * t1
	 * |  \
	 * t2 t3
	 *    |  \
	 *    t4 t5
	 *       |  \
	 *       t6 t7
	 * Scope 2
	 * t8
	 * | \
	 * t9 t10
	 */
	protected function initTreeWithScope()
	{
		Table10Peer::doDeleteAll();
		$ret = array();
		$fixtures = array(
			't1' => array(1, 14, 1),
			't2' => array(2, 3, 1), 
			't3' => array(4, 13, 1), 
			't4' => array(5, 6, 1), 
			't5' => array(7, 12, 1), 
			't6' => array(8, 9, 1), 
			't7' => array(10, 11, 1),
			't8' => array(1, 6, 2),
			't9' => array(2, 3, 2), 
			't10' => array(4, 5, 2), 
		);
		foreach ($fixtures as $key => $data) {
			$t = new PublicTable10();
			$t->setTitle($key);
			$t->setLeftValue($data[0]);
			$t->setRightValue($data[1]);
			$t->setScopeValue($data[2]);
			$t->save();
			$ret []= $t;
		}
		return $ret;
	}
	
	protected function dumpTreeWithScope($scope)
	{
		Table10Peer::clearInstancePool();
		$c = new Criteria();
		$c->add(Table10Peer::SCOPE_COL, $scope);
		$c->addAscendingOrderBycolumn(Table10Peer::TITLE);
		return $this->dumpNodes(Table10Peer::doSelect($c));
	}
}

// we need this class to test protected methods
class PublicTable9 extends Table9
{
	public $hasParentNode = null;
	public $parentNode = null;
	public $hasPrevSibling = null;
	public $prevSibling = null;
	public $hasNextSibling = null;
	public $nextSibling = null;	
}

class PublicTable10 extends Table10
{
	public $hasParentNode = null;
	public $parentNode = null;
}