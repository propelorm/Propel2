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
 * Tests the generated Object classes.
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
 * @see BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedObjectTest extends BookstoreTestBase {

	/**
	 * Test saving an object after setting default values for it.
	 */
	public function testSaveWithDefaultValues() {

		// From the schema.xml, I am relying on the following:
		//  - that 'Penguin' is the default Name for a Publisher
		//  - that 01/01/2001 is the default ReviewDate for a Review

		// 1) check regular values (VARCHAR)
		$pub = new Publisher();
		$pub->setName('Penguin');
		$pub->save();
		$this->assertTrue($pub->getId() !== null, "Expect Publisher to have been saved when default value set.");

		// 2) check date/time values
		$review = new Review();
		// note that this is different from how it's represented in schema, but should resolve to same unix timestamp
		$review->setReviewDate('2001-01-01');
		$this->assertTrue($review->isModified(), "Expect Review to have been marked 'modified' after default date/time value set.");

	}

	/**
	 * Test deleting an object using the delete() method.
	 */
	public function testDelete() {

		// 1) grab an arbitrary object
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) delete it
		$book->delete();

		// 3) make sure it can't be save()d now that it's deleted
		try {
			$book->setTitle("Will Fail");
			$book->save();
			$this->fail("Expect an exception to be thrown when attempting to save() a deleted object.");
		} catch (PropelException $e) {}

		// 4) make sure that it doesn't exist in db
		$book = BookPeer::retrieveByPK($bookId);
		$this->assertNull($book, "Expect NULL from retrieveByPK on deleted Book.");

	}

	/**
	 * Tests the getFieldNames() method
	 *
	 * @author Sven Fuchs <svenfuchs@artweb-design.de>
	 */
	public function testGetFieldNames (){

		if (!defined('Book::TYPE_FIELDNAME')) {
			return;
		}

		$defaultType = Book::TYPE_FIELDNAME;
		$types = array(
			Book::TYPE_PHPNAME,
			Book::TYPE_COLNAME,
			Book::TYPE_FIELDNAME,
			Book::TYPE_NUM
		);
		$expecteds = array (
			Book::TYPE_PHPNAME => array(
				0 => 'Id',
				1 => 'Title',
				2 => 'ISBN',
				3 => 'PublisherId',
				4 => 'AuthorId'
			),
			Book::TYPE_COLNAME => array(
				0 => 'book.ID',
				1 => 'book.TITLE',
				2 => 'book.ISBN',
				3 => 'book.PUBLISHER_ID',
				4 => 'book.AUTHOR_ID'
			),
			Book::TYPE_FIELDNAME => array(
				0 => 'id',
				1 => 'title',
				2 => 'isbn',
				3 => 'publisher_id',
				4 => 'author_id'
			),
			Book::TYPE_NUM => array(
				0 => 0,
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4
			)
		);

		foreach($types as $type) {
			$results[$type] = Book::getFieldnames($type);
			$this->assertEquals(
				$expecteds[$type],
				$results[$type],
				'expected was: ' . print_r($expected, 1) .
				'but getFieldnames() returned ' . print_r($result, 1)
			);
		}
	}

	/**
	 * Tests the translateFieldName() method
	 *
	 * @author Sven Fuchs <svenfuchs@artweb-design.de>
	 */
	public function testTranslateFieldName (){

		if (!defined('Book::TYPE_FIELDNAME')) {
			return;
		}

		$types = array(
			Book::TYPE_PHPNAME,
			Book::TYPE_COLNAME,
			Book::TYPE_FIELDNAME,
			Book::TYPE_NUM
		);
		$expecteds = array (
			Book::TYPE_PHPNAME => 'AuthorId',
			Book::TYPE_COLNAME => 'book.AUTHOR_ID',
			Book::TYPE_FIELDNAME => 'author_id',
			Book::TYPE_NUM => 4,
		);
		foreach($types as $fromType) {
			foreach($types as $toType) {
				$name = $expecteds[$fromType];
				$expected = $expecteds[$toType];
				$result = Book::translateFieldName($name, $fromType, $toType);
				$this->assertEquals($expected, $result);
			}
		}
	}

	/**
	 * Tests the getByName() method
	 *
	 * @author Sven Fuchs <svenfuchs@artweb-design.de>
	 */
	public function testGetByName(){

		if (!defined('Book::TYPE_FIELDNAME')) {
			return;
		}

		$types = array(
			Book::TYPE_PHPNAME => 'Title',
			Book::TYPE_COLNAME => 'book.TITLE',
			Book::TYPE_FIELDNAME => 'title',
			Book::TYPE_NUM => 1
		);
		$criteria = new Criteria();
		$criteria->add(BookPeer::ISBN, '043935806X');
		$book = BookPeer::doSelectOne($criteria);

		$expected = 'Harry Potter and the Order of the Phoenix';
		foreach($types as $type => $name) {
			$result = $book->getByName($name, $type);
			$this->assertEquals($expected, $result);
		}
	}

	/**
	 * Tests the toArray() method
	 *
	 * @author Sven Fuchs <svenfuchs@artweb-design.de>
	 */
	public function testToArray(){

		if (!defined('Book::TYPE_FIELDNAME')) {
			return;
		}

		$types = array(
			Book::TYPE_PHPNAME,
			Book::TYPE_COLNAME,
			Book::TYPE_FIELDNAME,
			Book::TYPE_NUM
		);
		$expecteds = array (
			Book::TYPE_PHPNAME => array (
				'Title' => 'Harry Potter and the Order of the Phoenix',
				'ISBN' => '043935806X'
			),
			Book::TYPE_COLNAME => array (
				'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
				'book.ISBN' => '043935806X'
			),
			Book::TYPE_FIELDNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'isbn' => '043935806X'
			),
			Book::TYPE_NUM => array (
				'1' => 'Harry Potter and the Order of the Phoenix',
				'2' => '043935806X'
			)
		);

		$criteria = new Criteria();
		$criteria->add(BookPeer::ISBN, '043935806X');
		$book = BookPeer::doSelectOne($criteria);

		foreach($types as $type) {
			$expected = $expecteds[$type];
			$result = $book->toArray($type);
			// remove ID since its autoincremented at each test iteration
			$result = array_slice($result, 1, 2, true);
			$this->assertEquals(
				$expected,
				$result,
				'expected was: ' . print_r($expected, 1) .
				'but toArray() returned ' . print_r($result, 1)
			);
		}
	}

	/**
	 * Tests the fromArray() method
	 *
	 * this also tests populateFromArray() because that's an alias
	 *
	 * @author Sven Fuchs <svenfuchs@artweb-design.de>
	 */
	public function testFromArray(){

		if (!defined('Book::TYPE_FIELDNAME')) {
			return;
		}

		$types = array(
			Book::TYPE_PHPNAME,
			Book::TYPE_COLNAME,
			Book::TYPE_FIELDNAME,
			Book::TYPE_NUM
		);
		$expecteds = array (
			Book::TYPE_PHPNAME => array (
				'Title' => 'Harry Potter and the Order of the Phoenix',
				'ISBN' => '043935806X'
			),
			Book::TYPE_COLNAME => array (
				'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
				'book.ISBN' => '043935806X'
			),
			Book::TYPE_FIELDNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'isbn' => '043935806X'
			),
			Book::TYPE_NUM => array (
				'1' => 'Harry Potter and the Order of the Phoenix',
				'2' => '043935806X'
			)
		);

		$book = new Book();

		foreach($types as $type) {
			$expected = $expecteds[$type];
			$book->fromArray($expected, $type);
			$result = array();
			foreach (array_keys($expected) as $key) {
				$result[$key] = $book->getByName($key, $type);
			}
			$this->assertEquals(
				$expected,
				$result,
				'expected was: ' . print_r($expected, 1) .
				'but fromArray() returned ' . print_r($result, 1)
			);
		}
	}

}
