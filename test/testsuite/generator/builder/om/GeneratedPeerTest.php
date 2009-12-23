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

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests the generated Peer classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * peer operations.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    generator.builder.om
 */
class GeneratedPeerTest extends BookstoreTestBase
{
	public function testAlias()
	{
		$this->assertEquals('foo.ID', BookPeer::alias('foo', BookPeer::ID), 'alias() returns a column name using the table alias');
		$this->assertEquals('book.ID', BookPeer::alias('book', BookPeer::ID), 'alias() returns a column name using the table alias');
		$this->assertEquals('foo.COVER_IMAGE', MediaPeer::alias('foo', MediaPeer::COVER_IMAGE), 'alias() also works for lazy-loaded columns');
		$this->assertEquals('foo.SUBTITLE', EssayPeer::alias('foo', EssayPeer::SUBTITLE), 'alias() also works for columns with custom phpName');
	}
	
	public function testAddSelectColumns()
	{
		$c = new Criteria();
		BookPeer::addSelectColumns($c);
		$expected = array(
			BookPeer::ID,
			BookPeer::TITLE,
			BookPeer::ISBN,
			BookPeer::PRICE,
			BookPeer::PUBLISHER_ID,
			BookPeer::AUTHOR_ID
		);
		$this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() adds the columns of the model to the criteria');
	}
	
	public function testAddSelectColumnsLazyLoad()
	{
		$c = new Criteria();
		MediaPeer::addSelectColumns($c);
		$expected = array(
			MediaPeer::ID,
			MediaPeer::BOOK_ID
		);
		$this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() does not add lazy loaded columns');
	}
	
	public function testAddSelectColumnsAlias()
	{
		$c = new Criteria();
		BookPeer::addSelectColumns($c, 'foo');
		$expected = array(
			'foo.ID',
			'foo.TITLE',
			'foo.ISBN',
			'foo.PRICE',
			'foo.PUBLISHER_ID',
			'foo.AUTHOR_ID'
		);
		$this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() uses the second parameter as a table alias');
	}
	
	public function testAddSelectColumnsAliasLazyLoad()
	{
		$c = new Criteria();
		MediaPeer::addSelectColumns($c, 'bar');
		$expected = array(
			'bar.ID',
			'bar.BOOK_ID'
		);
		$this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() does not add lazy loaded columns but uses the second parameter as an alias');
	}

}
