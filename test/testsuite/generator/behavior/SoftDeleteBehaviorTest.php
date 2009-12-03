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
 * Tests for SoftDeleteBehavior class
 *
 * @author		 François Zaninotto
 * @version		$Revision$
 * @package		generator.behavior
 */
class SoftDeleteBehaviorTest extends BookstoreTestBase 
{
	
	protected function setUp()
	{
		parent::setUp();
		Table4Peer::disableSoftDelete();
		Table4Peer::doDeleteAll();
		Table4Peer::enableSoftDelete();
	}
	
	public function testParameters()
	{
		$table2 = Table4Peer::getTableMap();
		$this->assertEquals(count($table2->getColumns()), 3, 'SoftDelete adds one columns by default');
		$this->assertTrue(method_exists('Table4', 'getDeletedAt'), 'SoftDelete adds an updated_at column by default');
		$table1 = Table5Peer::getTableMap();
		$this->assertEquals(count($table1->getColumns()), 3, 'SoftDelete does not add a column when add_column is false');
		$this->assertTrue(method_exists('Table5', 'getDeletedOn'), 'SoftDelete allows customization of deleted_column name');
	}
	
	public function testStaticSoftDeleteStatus()
	{
		$this->assertTrue(Table4Peer::isSoftDeleteEnabled(), 'The static soft delete is enabled by default');
		Table4Peer::disableSoftDelete();
		$this->assertFalse(Table4Peer::isSoftDeleteEnabled(), 'disableSoftDelete() disables the static soft delete');
		Table4Peer::enableSoftDelete();
		$this->assertTrue(Table4Peer::isSoftDeleteEnabled(), 'enableSoftDelete() enables the static soft delete');		
	}
	
	public function testStaticDoForceDelete()
	{
		$t1 = new Table4();
		$t1->save();
		Table4Peer::doForceDelete($t1);
		Table4Peer::disableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doForceDelete() actually deletes records');
	}
	
	public function testStaticDoSoftDelete()
	{
		$t1 = new Table4();
		$t1->save();
		$t2 = new Table4();
		$t2->save();
		$t3 = new Table4();
		$t3->save();
		// softDelete with a criteria
		$c = new Criteria();
		$c->add(Table4Peer::ID, $t1->getId());
		Table4Peer::doSoftDelete($c);
		Table4Peer::disableSoftDelete();
		$this->assertEquals(3, Table4Peer::doCount(new Criteria()), 'doSoftDelete() keeps deleted record in the database');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(2, Table4Peer::doCount(new Criteria()), 'doSoftDelete() marks deleted record as deleted');
		// softDelete with a value
		Table4Peer::doSoftDelete(array($t2->getId()));
		Table4Peer::disableSoftDelete();
		$this->assertEquals(3, Table4Peer::doCount(new Criteria()), 'doSoftDelete() keeps deleted record in the database');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(1, Table4Peer::doCount(new Criteria()), 'doSoftDelete() marks deleted record as deleted');
		// softDelete with an object
		Table4Peer::doSoftDelete($t3);
		Table4Peer::disableSoftDelete();
		$this->assertEquals(3, Table4Peer::doCount(new Criteria()), 'doSoftDelete() keeps deleted record in the database');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doSoftDelete() marks deleted record as deleted');
	}
	
	public function testStaticDoDelete()
	{
		$t1 = new Table4();
		$t1->save();
		$t2 = new Table4();
		$t2->save();
		Table4Peer::disableSoftDelete();
		Table4Peer::doDelete($t1);
		Table4Peer::disableSoftDelete();
		$this->assertEquals(1, Table4Peer::doCount(new Criteria()), 'doDelete() calls doForceDelete() when soft delete is disabled');
		Table4Peer::enableSoftDelete();
		Table4Peer::doDelete($t2);
		Table4Peer::disableSoftDelete();
		$this->assertEquals(1, Table4Peer::doCount(new Criteria()), 'doDelete() calls doSoftDelete() when soft delete is enabled');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doDelete() calls doSoftDelete() when soft delete is enabled');
	}
	
	public function testStaticDoForceDeleteAll()
	{
		$t1 = new Table4();
		$t1->save();
		Table4Peer::doForceDeleteAll();
		Table4Peer::disableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doForceDeleteAll() actually deletes records');
	}
	
	public function testStaticDoSoftDeleteAll()
	{
		$t1 = new Table4();
		$t1->save();
		$t2 = new Table4();
		$t2->save();
		Table4Peer::enableSoftDelete();
		Table4Peer::doSoftDeleteAll();
		Table4Peer::disableSoftDelete();
		$this->assertEquals(2, Table4Peer::doCount(new Criteria()), 'doSoftDeleteAll() keeps deleted record in the database');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doSoftDeleteAll() marks deleted record as deleted');
	}
	
	public function testStaticDoDeleteAll()
	{
		$t1 = new Table4();
		$t1->save();
		$t2 = new Table4();
		$t2->save();
		Table4Peer::disableSoftDelete();
		Table4Peer::doDeleteAll();
		Table4Peer::disableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doDeleteAll() calls doForceDeleteAll() when soft delete is disabled');
		$t1 = new Table4();
		$t1->save();
		$t2 = new Table4();
		$t2->save();
		Table4Peer::enableSoftDelete();
		Table4Peer::doDeleteAll();
		Table4Peer::disableSoftDelete();
		$this->assertEquals(2, Table4Peer::doCount(new Criteria()), 'doDeleteAll() calls doSoftDeleteAll() when soft delete is disabled');
		Table4Peer::enableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria()), 'doDeleteAll() calls doSoftDeleteAll() when soft delete is disabled');
	}
	
	public function testSelect()
	{
		$t = new Table4();
		$t->setDeletedAt(123);
		$t->save();
		Table4Peer::enableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria), 'rows with a deleted_at date are hidden for select queries');
		Table4Peer::disableSoftDelete();
		$this->assertEquals(1, Table4Peer::doCount(new Criteria), 'rows with a deleted_at date are visible for select queries once the static soft_delete is enabled');
		$this->assertTrue(Table4Peer::isSoftDeleteEnabled(), 'Executing a select query enables the static soft delete again');
	}
	
	public function testDelete()
	{
		$t = new Table4();
		$t->save();
		$this->assertNull($t->getDeletedAt(), 'deleted_column is null by default');
		$t->delete();
		$this->assertNotNull($t->getDeletedAt(), 'deleted_column is not null after a soft delete');
		$this->assertEquals(0, Table4Peer::doCount(new Criteria), 'soft deleted rows are hidden for select queries');
		Table4Peer::disableSoftDelete();
		$this->assertEquals(1, Table4Peer::doCount(new Criteria), 'soft deleted rows are still present in the database');
	}

	public function testDeleteUndeletable()
	{
		$t = new UndeletableTable4();
		$t->save();
		$t->delete();
		$this->assertNull($t->getDeletedAt(), 'soft_delete is not triggered for objects wit ha preDelete hook returning false');
		$this->assertEquals(1, Table4Peer::doCount(new Criteria), 'soft_delete is not triggered for objects wit ha preDelete hook returning false');
	}

	public function testUnDelete()
	{
		$t = new Table4();
		$t->save();
		$t->delete();
		$t->undelete();
		$this->assertNull($t->getDeletedAt(), 'deleted_column is null again after an undelete');
		$this->assertEquals(1, Table4Peer::doCount(new Criteria), 'undeleted rows are visible for select queries');
	}
	
	public function testForceDelete()
	{
		$t = new Table4();
		$t->save();
		$t->forceDelete();
		$this->assertTrue($t->isDeleted(), 'forceDelete() actually deletes a row');
		Table4Peer::disableSoftDelete();
		$this->assertEquals(0, Table4Peer::doCount(new Criteria), 'forced deleted rows are not present in the database');
	}	

	public function testCustomization()
	{
		Table5Peer::disableSoftDelete();
		Table5Peer::doDeleteAll();
		Table5Peer::enableSoftDelete();
		$t = new Table5();
		$t->save();
		$this->assertNull($t->getDeletedOn(), 'deleted_column is null by default');
		$t->delete();
		$this->assertNotNull($t->getDeletedOn(), 'deleted_column is not null after a soft delete');
		$this->assertEquals(0, Table5Peer::doCount(new Criteria), 'soft deleted rows are hidden for select queries');
		Table5Peer::disableSoftDelete();
		$this->assertEquals(1, Table5Peer::doCount(new Criteria), 'soft deleted rows are still present in the database');		
	}
}

class UndeletableTable4 extends Table4 
{
	public function preDelete(PropelPDO $con)
	{
		parent::preDelete($con);
		$this->setTitle('foo');
		return false;
	}
}