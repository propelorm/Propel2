<?php
/*
 *  $Id: GeneratedObjectTest.php 793 2007-11-09 02:43:08Z hans $
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
 * Tests relationships between generated Object classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class GeneratedObjectRelTest extends BookstoreTestBase {

	/**
	 * Tests bi-directional setting of many-to-many relationships.
	 * @link http://propel.phpdb.org/trac/ticket/508
	 */
	public function testManyToMany_Dir1()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');
		$list->save();

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');
		
		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		$xref = new BookListRel();
		$xref->setBook($book);
		$list->addBookListRel($xref);
		
		$this->assertEquals(1, count($list->getBookListRels()));
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );
		
		$list->save();
		
		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(1, count(BookListRelPeer::doSelect(new Criteria())) );

	}
	
	/**
	 * Tests bi-directional setting of many-to-many relationships.
	 * @link http://propel.phpdb.org/trac/ticket/508
	 */
	public function testManyToMany_Dir2()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');
		$list->save();

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');

		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		// Now set the relationship from the opposite direction.
		
		$xref = new BookListRel();
		$xref->setBookClubList($list);
		$book->addBookListRel($xref);
		
		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );
		$book->save();
		
		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(1, count(BookListRelPeer::doSelect(new Criteria())) );

	}

}
