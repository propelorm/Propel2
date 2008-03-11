<?php
/*
 *  $Id$
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

/**
 * Tests the BasePeer classes.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class BasePeerTest extends BookstoreTestBase {

	protected function setUp()
	{
		parent::setUp();
	}

	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/425
	 */
	public function testMultipleFunctionInCriteria()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		try {
			$c = new Criteria();
			$c->setDistinct();
			if ($db instanceof DBPostgres) {
				$c->addSelectColumn("substring(".BookPeer::TITLE." from position('Potter' in ".BookPeer::TITLE.")) AS col");
			} else {
				$this->markTestSkipped();
			}
			$stmt = BookPeer::doSelectStmt( $c );
		} catch (PropelException $x) {
			$this->fail("Paring of nested functions failed: " . $x->getMessage());
		}
	}

	/**
	 *
	 */
	public function testBigIntIgnoreCaseOrderBy()
	{
		// Some sample data
		$b = new Bookstore();
		$b->setStoreName("SortTest1")->setPopulationServed(2000)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest2")->setPopulationServed(201)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest3")->setPopulationServed(302)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest4")->setPopulationServed(10000000)->save();

		$c = new Criteria();
		$c->setIgnoreCase(true);
		$c->add(BookstorePeer::STORE_NAME, 'SortTest%', Criteria::LIKE);
		$c->addAscendingOrderByColumn(BookstorePeer::POPULATION_SERVED);

		$rows = BookstorePeer::doSelect($c);
		$this->assertEquals('SortTest2', $rows[0]->getStoreName());
		$this->assertEquals('SortTest3', $rows[1]->getStoreName());
		$this->assertEquals('SortTest1', $rows[2]->getStoreName());
		$this->assertEquals('SortTest4', $rows[3]->getStoreName());
	}

	/**
	 *
	 */
	public function testMixedJoinOrder()
	{
		$this->markTestIncomplete();
		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);

		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);
		$c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID);

		$params = array();
		$sql = BasePeer::createSelectSql($c, $params);

		$expectedSql = "SELECT book.ID, book.TITLE FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID), author WHERE book.AUTHOR_ID=author.ID";
		// print $sql . "\n";
	}
}
