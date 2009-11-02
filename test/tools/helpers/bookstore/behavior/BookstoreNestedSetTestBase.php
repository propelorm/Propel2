<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

class BookstoreNestedSetTestBase extends BookstoreTestBase
{
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
		$t1 = new Table9();
		$t1->setTitle('t1')->setLeftValue(1)->setRightValue(14)->save();
		$t2 = new Table9();
		$t2->setTitle('t2')->setLeftValue(2)->setRightValue(3)->save();
		$t3 = new Table9();
		$t3->setTitle('t3')->setLeftValue(4)->setRightValue(13)->save();
		$t4 = new Table9();
		$t4->setTitle('t4')->setLeftValue(5)->setRightValue(6)->save();
		$t5 = new Table9();
		$t5->setTitle('t5')->setLeftValue(7)->setRightValue(12)->save();
		$t6 = new Table9();
		$t6->setTitle('t6')->setLeftValue(8)->setRightValue(9)->save();
		$t7 = new Table9();
		$t7->setTitle('t7')->setLeftValue(10)->setRightValue(11)->save();
		return array($t1, $t2, $t3, $t4, $t5, $t6, $t7);
	}
	
	protected function dumpTree()
	{
		Table9Peer::clearInstancePool();
		$c = new Criteria();
		$c->addAscendingOrderBycolumn(Table9Peer::TITLE);
		$nodes = Table9Peer::doSelect($c);
		$tree = array();
		foreach ($nodes as $node)
		{
			$tree[$node->getTitle()] = array($node->getLeftValue(), $node->getRightValue());
		}
		return $tree;
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
		$t1 = new Table10();
		$t1->setTitle('t1')->setScopeValue(1)->setLeftValue(1)->setRightValue(14)->save();
		$t2 = new Table10();
		$t2->setTitle('t2')->setScopeValue(1)->setLeftValue(2)->setRightValue(3)->save();
		$t3 = new Table10();
		$t3->setTitle('t3')->setScopeValue(1)->setLeftValue(4)->setRightValue(13)->save();
		$t4 = new Table10();
		$t4->setTitle('t4')->setScopeValue(1)->setLeftValue(5)->setRightValue(6)->save();
		$t5 = new Table10();
		$t5->setTitle('t5')->setScopeValue(1)->setLeftValue(7)->setRightValue(12)->save();
		$t6 = new Table10();
		$t6->setTitle('t6')->setScopeValue(1)->setLeftValue(8)->setRightValue(9)->save();
		$t7 = new Table10();
		$t7->setTitle('t7')->setScopeValue(1)->setLeftValue(10)->setRightValue(11)->save();
		$t8 = new Table10();
		$t8->setTitle('t8')->setScopeValue(2)->setLeftValue(1)->setRightValue(6)->save();
		$t9 = new Table10();
		$t9->setTitle('t9')->setScopeValue(2)->setLeftValue(2)->setRightValue(3)->save();
		$t10 = new Table10();
		$t10->setTitle('t10')->setScopeValue(2)->setLeftValue(4)->setRightValue(5)->save();
		return array($t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10);
	}
	
	protected function dumpTreeWithScope($scope)
	{
		Table10Peer::clearInstancePool();
		$c = new Criteria();
		$c->add(Table10Peer::SCOPE_COL, $scope);
		$c->addAscendingOrderBycolumn(Table10Peer::TITLE);
		$nodes = Table10Peer::doSelect($c);
		$tree = array();
		foreach ($nodes as $node)
		{
			$tree[$node->getTitle()] = array($node->getLeftValue(), $node->getRightValue());
		}
		return $tree;
	}
}