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
 * Tests for SortableBehavior class
 *
 * @author		Massimiliano Arione
 * @version		$Revision$
 * @package		generator.engine.behavior
 */
class SortableBehaviorPeerBuilderModifierTest extends BookstoreSortableTestBase
{
	protected function setUp()
	{
		parent::setUp();
		$this->populateTable11();
	}
	
	public function testStaticAttributes()
	{
		$this->assertEquals(Table11Peer::RANK_COL, 'table11.SORTABLE_RANK');
	}
	
	public function testGetMaxRank()
	{
		$this->assertEquals(4, Table11Peer::getMaxRank(), 'getMaxRank() returns the maximum rank');
		$t4 = Table11Peer::retrieveByRank(4);
		$t4->delete();
		$this->assertEquals(3, Table11Peer::getMaxRank(), 'getMaxRank() returns the maximum rank');
		Table11Peer::doDeleteAll();
		$this->assertNull(Table11Peer::getMaxRank(), 'getMaxRank() returns null for empty tables');
	}
	public function testRetrieveByRank()
	{
		$t = Table11Peer::retrieveByRank(5);
		$this->assertNull($t, 'retrieveByRank() returns null for an unknown rank');
		$t3 = Table11Peer::retrieveByRank(3);
		$this->assertEquals(3, $t3->getRank(), 'retrieveByRank() returns the object with the required rank');
		$this->assertEquals('row3', $t3->getTitle(), 'retrieveByRank() returns the object with the required rank');
	}
	
	public function testReorder()
	{
		$objects = Table11Peer::doSelect(new Criteria());
		$ids = array();
		foreach ($objects as $object) {
			$ids[]= $object->getPrimaryKey();
		}
		$ranks = array(4, 3, 2, 1);
		$order = array_combine($ids, $ranks);
		Table11Peer::reorder($order);
		$expected = array(1 => 'row3', 2 => 'row2', 3 => 'row4', 4 => 'row1');
		$this->assertEquals($expected, $this->getFixturesArray(), 'reorder() reorders the suite');
	}
	
	public function testDoSelectOrderByRank()
	{
		$objects = Table11Peer::doSelectOrderByRank();
		$oldRank = 0;
		while ($object = array_shift($objects)) {
			$this->assertTrue($object->getRank() > $oldRank);
			$oldRank = $object->getRank();
		}
		$objects = Table11Peer::doSelectOrderByRank(null, Criteria::DESC);
		$oldRank = 10;
		while ($object = array_shift($objects)) {
			$this->assertTrue($object->getRank() < $oldRank);
			$oldRank = $object->getRank();
		}
	}
	

}