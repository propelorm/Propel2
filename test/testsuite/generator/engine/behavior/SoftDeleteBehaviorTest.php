<?php

/*
 *	$Id: SoftDeleteBehaviorTest.php 1133 2009-09-16 13:35:12Z francois $
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

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Tests for SoftDeleteBehavior class
 *
 * @author		 François Zaninotto
 * @version		$Revision: 1133 $
 * @package		generator.engine.behavior
 */
class SoftDeleteBehaviorTest extends PHPUnit_Framework_TestCase 
{
	
	protected function setUp()
	{
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
	
	public function testSoftDeleteStatus()
	{
		$t = new Table4();
		$this->assertTrue($t->isSoftDeleteEnabled(), 'The soft delete is enabled by default');
		$t->disableSoftDelete();
		$this->assertFalse($t->isSoftDeleteEnabled(), 'disableSoftDelete() disables the soft delete');
		$t->enableSoftDelete();
		$this->assertTrue($t->isSoftDeleteEnabled(), 'enableSoftDelete() enables the static soft delete');
	}
	
	public function testEnableSoftDelete()
	{
		$t = new Table4();
		$t->save();
		Table4Peer::enableSoftDelete();
		$t->enableSoftDelete();
		$t->delete();
		$this->assertFalse($t->isDeleted(), 'soft delete is enabled when both the static and local soft_delete settings are enabled');
		$t = new Table4();
		$t->save();
		Table4Peer::disableSoftDelete();
		$t->enableSoftDelete();
		$t->delete();
		$this->assertTrue($t->isDeleted(), 'soft delete is disabled when at least the static soft_delete setting is disabled');
		$t = new Table4();
		$t->save();
		Table4Peer::enableSoftDelete();
		$t->disableSoftDelete();
		$t->delete();
		$this->assertTrue($t->isDeleted(), 'soft delete is disabled when at least the local soft_delete setting is disabled');
		$t = new Table4();
		$t->save();
		Table4Peer::disableSoftDelete();
		$t->disableSoftDelete();
		$t->delete();
		$this->assertTrue($t->isDeleted(), 'soft delete is disabled when both the static and local soft_delete settings are disabled');
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