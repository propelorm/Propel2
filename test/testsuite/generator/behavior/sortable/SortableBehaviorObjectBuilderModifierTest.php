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

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests for SortableBehavior class
 *
 * @author		Massimiliano Arione
 * @version		$Revision$
 * @package		generator.engine.behavior
 */
class SortableBehaviorObjectBuilderModifierTest extends BookstoreTestBase
{
	protected function populateTable11()
	{
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
	
	public function testPreInsert()
	{
		Table11Peer::doDeleteAll();
		$t1 = new Table11();
		$t1->save();
		$this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
		$t2 = new Table11();
		$t2->setTitle('row2');
		$t2->save();
		$this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
	}
	
	public function testPreDelete()
	{
		$this->populateTable11();
		$max = Table11Peer::getMaxPosition();
		$t3 = Table11Peer::retrieveByPosition(3);
		$t3->delete();
		$this->assertEquals($max - 1, Table11Peer::getMaxPosition(), 'Sortable rearrange subsequent rows on delete');
		$c = new Criteria();
		$c->add(Table11Peer::TITLE, 'row4');
		$t4 = Table11Peer::doSelectOne($c);
		$this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
	}
	
	public function testIsFirst()
	{
		$this->populateTable11();
		$first = Table11Peer::retrieveByPosition(1);
		$middle = Table11Peer::retrieveByPosition(2);
		$last = Table11Peer::retrieveByPosition(4);
		$this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
		$this->assertFalse($middle->isFirst(), 'isFirst() returns false for a middle rank');
		$this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
	}

	public function testIsLast()
	{
		$this->populateTable11();
		$first = Table11Peer::retrieveByPosition(1);
		$middle = Table11Peer::retrieveByPosition(2);
		$last = Table11Peer::retrieveByPosition(4);
		$this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
		$this->assertFalse($middle->isLast(), 'isLast() returns false for a middle rank');
		$this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
	}

	public function testGetNext()
	{
		$this->populateTable11();
		$t = Table11Peer::retrieveByPosition(3);
		$this->assertEquals(4, $t->getNext()->getRank(), 'getNext() returns the next object in rank');
	}

	public function testGetPrevious()
	{
		$this->populateTable11();
		$t = Table11Peer::retrieveByPosition(3);
		$this->assertEquals(2, $t->getPrevious()->getRank(), 'getPrevious() returns the previous object in rank');
	}

	public function testMoveToPosition()
	{
		$this->populateTable11();
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

}