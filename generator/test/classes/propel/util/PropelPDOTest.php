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

require_once 'bookstore/BookstoreTestBase.php';
require_once 'propel/util/PropelPDO.php';

/**
 * Test for PropelPDO subclass.
 *
 * @package    propel.util
 */
class PropelPDOTest extends BookstoreTestBase
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
	
	/**
	 * @link       http://propel.phpdb.org/trac/ticket/699
	 */
	public function testRollBack_NestedRethrow()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == "mysql") {
			$this->markTestSkipped();
		}
		
		$con->beginTransaction();
		try {
			
			$a = new Author();
			$a->setFirstName('Test');
			$a->setLastName('User');
			$a->save($con);
			$authorId = $a->getId();
			
			$this->assertTrue($authorId !== null, "Expected valid new author ID");
			
			$con->beginTransaction();
			try {
				$con->exec('INVALID SQL');
				$this->fail("Expected exception on invalid SQL");
			} catch (Exception $x) {
				$con->rollBack();
				throw $x;
			}
			
			$con->commit();
		} catch (Exception $x) {
			$con->rollBack();
		}
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNull($at, "Expected no author result for rolled-back save.");
	}
	
	/**
	 * @link       http://propel.phpdb.org/trac/ticket/699
	 */
	public function testRollBack_NestedSwallow()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == "mysql") {
			$this->markTestSkipped();
		}
		
		$con->beginTransaction();
		try {
			
			$a = new Author();
			$a->setFirstName('Test');
			$a->setLastName('User');
			$a->save($con);
			$authorId = $a->getId();
			
			$this->assertTrue($authorId !== null, "Expected valid new author ID");
			
			$con->beginTransaction();
			try {
				$con->exec('INVALID SQL');
				$this->fail("Expected exception on invalid SQL");
			} catch (Exception $x) {
				$con->rollBack();
				// NO RETHROW
			}
			
			$a2 = new Author();
			$a2->setFirstName('Test2');
			$a2->setLastName('User2');
			$authorId2 = $a2->save($con);
			 
			$con->commit(); // this should not do anything!
			
		} catch (Exception $x) {
			$this->fail("No outside rollback expected.");
		}
		
		AuthorPeer::clearInstancePool();
		$at = AuthorPeer::retrieveByPK($authorId);
		$this->assertNull($at, "Expected no author result for rolled-back save.");
		
		$at2 = AuthorPeer::retrieveByPK($authorId2);
		$this->assertNull($at2, "Expected no author2 result for rolled-back save.");
	}
	
}
