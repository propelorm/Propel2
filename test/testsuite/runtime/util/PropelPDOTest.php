<?php
/*
 *  $Id: PropelDateTimeTest.php 784 2007-11-08 10:15:50Z heltem $
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
require_once 'propel/util/PropelPDO.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');

/**
 * Test for PropelPDO subclass.
 *
 * @package    runtime.util
 */
class PropelPDOTest extends PHPUnit_Framework_TestCase
{

	public function testSetAttribute()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$this->assertFalse($con->getAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES));
		$con->setAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES, true);
		$this->assertTrue($con->getAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES));

		$con->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		$this->assertEquals(PDO::CASE_LOWER, $con->getAttribute(PDO::ATTR_CASE));
	}
	
	public function testNestedTransactionCommit()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		$this->assertEquals(0, $con->getNestedTransactionCount(), 'nested transaction is equal to 0 before transaction');
		$this->assertFalse($con->isInTransaction(), 'PropelPDO is not in transaction by default');
		
		$con->beginTransaction();
		
		$this->assertEquals(1, $con->getNestedTransactionCount(), 'nested transaction is incremented after main transaction begin');
		$this->assertTrue($con->isInTransaction(), 'PropelPDO is in transaction after main transaction begin');
		
		try {
			
			$a = new Author();
			$a->setFirstName('Test');
			$a->setLastName('User');
			$a->save($con);
			$authorId = $a->getId();
			$this->assertNotNull($authorId, "Expected valid new author ID");
			
			$con->beginTransaction();

			$this->assertEquals(2, $con->getNestedTransactionCount(), 'nested transaction is incremented after nested transaction begin');
			$this->assertTrue($con->isInTransaction(), 'PropelPDO is in transaction after nested transaction begin');

			try {

				$a2 = new Author();
				$a2->setFirstName('Test2');
				$a2->setLastName('User2');
				$a2->save($con);
				$authorId2 = $a2->getId();
				$this->assertNotNull($authorId2, "Expected valid new author ID");
				
				$con->commit();
				
				$this->assertEquals(1, $con->getNestedTransactionCount(), 'nested transaction decremented after nested transaction commit');
				$this->assertTrue($con->isInTransaction(), 'PropelPDO is in transaction after main transaction commit');

			} catch (Exception $e) {
				$con->rollBack();
				throw $e;
			}
			
			$con->commit();
			
			$this->assertEquals(0, $con->getNestedTransactionCount(), 'nested transaction decremented after main transaction commit');
			$this->assertFalse($con->isInTransaction(), 'PropelPDO is not in transaction after main transaction commit');

		} catch (Exception $e) {
			$con->rollBack();
		}
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNotNull($at, "Committed transaction is persisted in database");
		$at2 = AuthorPeer::retrieveByPK($authorId2);
		$this->assertNotNull($at2, "Committed transaction is persisted in database");
	}

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/699
	 */
	public function testNestedTransactionRollBackRethrow()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		$con->beginTransaction();
		try {
			
			$a = new Author();
			$a->setFirstName('Test');
			$a->setLastName('User');
			$a->save($con);
			$authorId = $a->getId();
			
			$this->assertNotNull($authorId, "Expected valid new author ID");
			
			$con->beginTransaction();

			$this->assertEquals(2, $con->getNestedTransactionCount(), 'nested transaction is incremented after nested transaction begin');
			$this->assertTrue($con->isInTransaction(), 'PropelPDO is in transaction after nested transaction begin');

			try {
				$con->exec('INVALID SQL');
				$this->fail("Expected exception on invalid SQL");
			} catch (PDOException $x) {
				$con->rollBack();

				$this->assertEquals(1, $con->getNestedTransactionCount(), 'nested transaction decremented after nested transaction rollback');
				$this->assertTrue($con->isInTransaction(), 'PropelPDO is in transaction after main transaction rollback');

				throw $x;
			}
			
			$con->commit();
		} catch (Exception $x) {
			$con->rollBack();
		}
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNull($at, "Rolled back transaction is not persisted in database");
	}
	
	/**
	 * @link       http://propel.phpdb.org/trac/ticket/699
	 */
	public function testNestedTransactionRollBackSwallow()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		$con->beginTransaction();
		try {
			
			$a = new Author();
			$a->setFirstName('Test');
			$a->setLastName('User');
			$a->save($con);
			
			$authorId = $a->getId();	
			$this->assertNotNull($authorId, "Expected valid new author ID");
			
			$con->beginTransaction();
			try {

				$a2 = new Author();
				$a2->setFirstName('Test2');
				$a2->setLastName('User2');
				$a2->save($con);
				$authorId2 = $a2->getId();
				$this->assertNotNull($authorId2, "Expected valid new author ID");

				$con->exec('INVALID SQL');
				$this->fail("Expected exception on invalid SQL");
			} catch (PDOException $e) {
				$con->rollBack();
				// NO RETHROW
			}
			
			$a3 = new Author();
			$a3->setFirstName('Test2');
			$a3->setLastName('User2');
			$a3->save($con);
			
			$authorId3 = $a3->getId();
			$this->assertNotNull($authorId3, "Expected valid new author ID");
			 
			$con->commit();
			$this->fail("Commit fails after a nested rollback");
		} catch (PropelException $e) {
			$this->assertTrue(true, "Commit fails after a nested rollback");
			$con->rollback();
		}
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNull($at, "Rolled back transaction is not persisted in database");		
		$at2 = AuthorPeer::retrieveByPK($authorId2);
		$this->assertNull($at2, "Rolled back transaction is not persisted in database");
		$at3 = AuthorPeer::retrieveByPK($authorId3);
		$this->assertNull($at3, "Rolled back nested transaction is not persisted in database");
	}

	public function testNestedTransactionForceRollBack()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		// main transaction		
		$con->beginTransaction();
			
		$a = new Author();
		$a->setFirstName('Test');
		$a->setLastName('User');
		$a->save($con);
		$authorId = $a->getId();
		
		// nested transaction
		$con->beginTransaction();

		$a2 = new Author();
		$a2->setFirstName('Test2');
		$a2->setLastName('User2');
		$a2->save($con);
		$authorId2 = $a2->getId();
		
		// force rollback
		$con->forceRollback();
		
		$this->assertEquals(0, $con->getNestedTransactionCount(), 'nested transaction is null after nested transaction forced rollback');
		$this->assertFalse($con->isInTransaction(), 'PropelPDO is not in transaction after nested transaction force rollback');
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNull($at, "Rolled back transaction is not persisted in database");		
		$at2 = AuthorPeer::retrieveByPK($authorId2);
		$this->assertNull($at2, "Forced Rolled back nested transaction is not persisted in database");
	}
	
}
