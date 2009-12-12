<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

class BookstoreSortableTestBase extends BookstoreTestBase
{
	protected function setUp()
	{
		parent::setUp();
		Table11Peer::doDeleteAll();
		$t1 = new Table11();
		$t1->setRank(1);
		$t1->setTitle('row1');
		$t1->save();
		$t2 = new Table11();
		$t2->setRank(4);
		$t2->setTitle('row4');
		$t2->save();
		$t3 = new Table11();
		$t3->setRank(2);
		$t3->setTitle('row2');
		$t3->save();
		$t4 = new Table11();
		$t4->setRank(3);
		$t4->setTitle('row3');
		$t4->save();
	}
	
	protected function getFixturesArray()
	{
		$c = new Criteria();
		$c->addAscendingOrderByColumn(Table11Peer::RANK_COL);
		$ts = Table11Peer::doSelect($c);
		$ret = array();
		foreach ($ts as $t) {
			$ret[$t->getRank()] = $t->getTitle();
		}
		return $ret;
	}
}