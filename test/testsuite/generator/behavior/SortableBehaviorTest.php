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

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
/**
 * Tests for SortableBehavior class
 *
 * @author		Massimiliano Arione
 * @version		$Revision$
 * @package		generator.engine.behavior
 */
class SortableBehaviorTest extends BookstoreTestBase
{

	public function testParameters()
	{
		$table11 = Table11Peer::getTableMap();
		$this->assertEquals(count($table11->getColumns()), 3, 'Sortable adds one columns by default');
		$this->assertTrue(method_exists('Table11', 'getRank'), 'Sortable adds a rank column by default');
		$table12 = Table12Peer::getTableMap();
		$this->assertEquals(count($table12->getColumns()), 3, 'Sortable does not add a column when add_column is false');
		$this->assertTrue(method_exists('Table12', 'getPosition'), 'Sortable allows customization of rank_column name');
	}

	public function testPreSave()
	{
		Table11Peer::doDeleteAll();
		$t1 = new Table11();
		$this->assertNull($t1->getRank());
		$t1->setTitle('row1');
		$t1->save();
		$this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
		$t2 = new Table11();
		$this->assertNull($t2->getRank());
		$t2->setTitle('row2');
		$t2->save();
		$this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
	}

	public function testRetrieveByPosition()
	{
		$t3 = new Table11();
		$t3->setTitle('row3');
		$t3->save();
		$t4 = new Table11();
		$t4->setTitle('row4');
		$t4->save();
		$t5 = new Table11();
		$t5->setTitle('row5');
		$t5->save();
		$t4 = Table11Peer::retrieveByPosition(4);
		$this->assertEquals($t4->getRank(), 4, 'Sortable get an object by its position');
	}

	public function testGetNextPrevious()
	{
		$t1 = Table11Peer::retrieveByPosition(3);
		$next = $t1->getNext();
		$prev = $t1->getPrevious();
		$this->assertEquals($next->getRank(), 4, 'Sortable get next object');
		$this->assertEquals($prev->getRank(), 2, 'Sortable get previous object');
	}

	public function testIsFirstLast()
	{
		$first = Table11Peer::retrieveByPosition(1);
		$last = Table11Peer::retrieveByPosition(5);
		$this->assertTrue($first->isFirst(), 'Sortable get first object');
		$this->assertTrue($last->isLast(), 'Sortable get last object');
	}

	public function testMoveToPosition()
	{
		$t1 = Table11Peer::retrieveByPosition(1);
		$t2 = Table11Peer::retrieveByPosition(2);
		$t3 = Table11Peer::retrieveByPosition(3);
		$old = $t1->moveToPosition(3);
		$this->assertEquals($old, 1, 'Sortable move an object from and old position');
		$this->assertEquals($t1->getRank(), 3, 'Sortable move an object to a new position');
		$nt2 = Table11Peer::retrieveByPK($t2->getId());
		$nt3 = Table11Peer::retrieveByPK($t3->getId());
		$nt2 = Table11Peer::retrieveByPosition(1);
		$nt3 = Table11Peer::retrieveByPosition(2);
		$this->assertEquals($nt2->getTitle(), 'row2', 'Sortable rearrange positions after moving - 1');
		$this->assertEquals($nt3->getTitle(), 'row3', 'Sortable rearrange positions after moving - 2');
	}

	public function testPreDelete()
	{
		$t3 = Table11Peer::retrieveByPosition(3);
		$t3->delete();
		$this->assertEquals(Table11Peer::getMaxPosition(), 4, 'Sortable rearrange subsequent rows on delete');
	}

}
