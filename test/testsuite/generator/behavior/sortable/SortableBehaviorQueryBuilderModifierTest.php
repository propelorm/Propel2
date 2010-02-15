<?php

/*
 *	$Id: SortableBehaviorTest.php 1356 2009-12-11 16:36:55Z francois $
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

require_once 'tools/helpers/bookstore/behavior/BookstoreSortableTestBase.php';

/**
 * Tests for SortableBehavior class query modifier
 *
 * @author		Francois Zaninotto
 * @version		$Revision$
 * @package		generator.behavior.sortable
 */
class SortableBehaviorQueryBuilderModifierTest extends BookstoreSortableTestBase
{
	protected function setUp()
	{
		parent::setUp();
		$this->populateTable11();
	}
	
	public function testFilterByRank()
	{
		$this->assertTrue(Table11Query::create()->filterByRank(1) instanceof Table11Query, 'filterByRank() returns the current query object');
		$this->assertEquals('row1', Table11Query::create()->filterByRank(1)->findOne()->getTitle(), 'filterByRank() filters on the rank');
		$this->assertEquals('row4', Table11Query::create()->filterByRank(4)->findOne()->getTitle(), 'filterByRank() filters on the rank');
		$this->assertNull(Table11Query::create()->filterByRank(5)->findOne(), 'filterByRank() filters on the rank, which makes the query return no result on a non-existent rank');
	}

	public function testOrderByRank()
	{
		$this->assertTrue(Table11Query::create()->orderByRank() instanceof Table11Query, 'orderByRank() returns the current query object');
		// default order
		$query = Table11Query::create()->orderByRank();
		$expectedQuery = Table11Query::create()->addAscendingOrderByColumn(Table11Peer::SORTABLE_RANK);
		$this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank asc');
		// asc order
		$query = Table11Query::create()->orderByRank(Criteria::ASC);
		$expectedQuery = Table11Query::create()->addAscendingOrderByColumn(Table11Peer::SORTABLE_RANK);
		$this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
		// desc order
		$query = Table11Query::create()->orderByRank(Criteria::DESC);
		$expectedQuery = Table11Query::create()->addDescendingOrderByColumn(Table11Peer::SORTABLE_RANK);
		$this->assertEquals($expectedQuery, $query, 'orderByRank() orders the query by rank, using the argument as sort direction');
	}

	/**
	 * @expectedException PropelException
	 */
	public function testOrderByRankIncorrectDirection()
	{
		Table11Query::create()->orderByRank('foo');
	}
	
	public function testFindList()
	{
		$ts = Table11Query::create()->findList();
		$this->assertTrue($ts instanceof PropelObjectCollection, 'findList() returns a collection of objects');
		$this->assertEquals(4, count($ts), 'findList() does not filter the query');
		$this->assertEquals('row1', $ts[0]->getTitle(), 'findList() returns an ordered list');
		$this->assertEquals('row2', $ts[1]->getTitle(), 'findList() returns an ordered list');
		$this->assertEquals('row3', $ts[2]->getTitle(), 'findList() returns an ordered list');
		$this->assertEquals('row4', $ts[3]->getTitle(), 'findList() returns an ordered list');
	}

	public function testFindOneByRank()
	{
		$this->assertTrue(Table11Query::create()->findOneByRank(1) instanceof Table11, 'findOneByRank() returns an instance of the model object');
		$this->assertEquals('row1', Table11Query::create()->findOneByRank(1)->getTitle(), 'findOneByRank() returns a single item based on the rank');
		$this->assertEquals('row4', Table11Query::create()->findOneByRank(4)->getTitle(), 'findOneByRank() returns a single item based on the rank');
		$this->assertNull(Table11Query::create()->findOneByRank(5), 'findOneByRank() returns no result on a non-existent rank');
	}
		
	public function testGetMaxRank()
	{
		$this->assertEquals(4, Table11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
		// delete one
		$t4 = Table11Query::create()->findOneByRank(4);
		$t4->delete();
		$this->assertEquals(3, Table11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
		// add one
		$t = new Table11();
		$t->save();
		$this->assertEquals(4, Table11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
		// delete all
		Table11Query::create()->deleteAll();
		$this->assertNull(Table11Query::create()->getMaxRank(), 'getMaxRank() returns null for empty tables');
		// add one
		$t = new Table11();
		$t->save();
		$this->assertEquals(1, Table11Query::create()->getMaxRank(), 'getMaxRank() returns the maximum rank');
	}

	public function testReorder()
	{
		$objects = Table11Query::create()->find();
		$ids = array();
		foreach ($objects as $object) {
			$ids[]= $object->getPrimaryKey();
		}
		$ranks = array(4, 3, 2, 1);
		$order = array_combine($ids, $ranks);
		Table11Query::create()->reorder($order);
		$expected = array(1 => 'row3', 2 => 'row2', 3 => 'row4', 4 => 'row1');
		$this->assertEquals($expected, $this->getFixturesArray(), 'reorder() reorders the suite');
	}

}