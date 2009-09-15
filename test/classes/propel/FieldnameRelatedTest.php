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

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'bookstore/BookstoreTestBase.php';

/**
 * Tests some of the methods of generated Object classes. These are:
 *
 * - Base[Object]Peer::getFieldNames()
 * - Base[Object]Peer::translateFieldName()
 * - BasePeer::getFieldNames()
 * - BasePeer::translateFieldName()
 * - Base[Object]::getByName()
 * - Base[Object]::setByName()
 * - Base[Object]::fromArray()
 * - Base[Object]::toArray()
 *
 * I've pulled these tests from the GeneratedObjectTest because the don't
 * need the BookstoreTestBase's setUp and tearDown (database de/population)
 * behaviour. The tests will run faster this way.
 *
 * @author     Sven Fuchs <svenfuchs@artweb-design.de>
 */
class FieldnameRelatedTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests if fieldname type constants are defined
	 */
	public function testFieldNameTypeConstants () {

		$result = defined('BasePeer::TYPE_PHPNAME');
		$this->assertTrue($result);
	}

	/**
	 * Tests the Base[Object]Peer::getFieldNames() method
	 */
	public function testGetFieldNames () {

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);
		$expecteds = array (
			BasePeer::TYPE_PHPNAME => array(
				0 => 'Id',
				1 => 'Title',
				2 => 'ISBN',
				3 => 'Price',
				4 => 'PublisherId',
				5 => 'AuthorId'
			),
			BasePeer::TYPE_STUDLYPHPNAME => array(
				0 => 'id',
				1 => 'title',
				2 => 'iSBN',
				3 => 'price',
				4 => 'publisherId',
				5 => 'authorId'
			),
			BasePeer::TYPE_COLNAME => array(
				0 => 'book.ID',
				1 => 'book.TITLE',
				2 => 'book.ISBN',
				3 => 'book.PRICE',
				4 => 'book.PUBLISHER_ID',
				5 => 'book.AUTHOR_ID'
			),
			BasePeer::TYPE_FIELDNAME => array(
				0 => 'id',
				1 => 'title',
				2 => 'isbn',
				3 => 'price',
				4 => 'publisher_id',
				5 => 'author_id'
			),
			BasePeer::TYPE_NUM => array(
				0 => 0,
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5
			)
		);

		foreach ($types as $type) {
			$results[$type] = BookPeer::getFieldnames($type);
			$this->assertEquals(
				$expecteds[$type],
				$results[$type],
				'expected was: ' . print_r($expecteds[$type], 1) .
				'but getFieldnames() returned ' . print_r($results[$type], 1)
			);
		}
	}

	/**
	 * Tests the Base[Object]Peer::translateFieldName() method
	 */
	public function testTranslateFieldName () {

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_STUDLYPHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);
		$expecteds = array (
			BasePeer::TYPE_PHPNAME => 'AuthorId',
			BasePeer::TYPE_STUDLYPHPNAME => 'authorId',
			BasePeer::TYPE_COLNAME => 'book.AUTHOR_ID',
			BasePeer::TYPE_FIELDNAME => 'author_id',
			BasePeer::TYPE_NUM => 5,
		);
		foreach ($types as $fromType) {
			foreach ($types as $toType) {
				$name = $expecteds[$fromType];
				$expected = $expecteds[$toType];
				$result = BookPeer::translateFieldName($name, $fromType, $toType);
				$this->assertEquals($expected, $result);
			}
		}
	}

	/**
	 * Tests the BasePeer::getFieldNames() method
	 */
	public function testGetFieldNamesStatic () {

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_STUDLYPHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);
		$expecteds = array (
			BasePeer::TYPE_PHPNAME => array(
				0 => 'Id',
				1 => 'Title',
				2 => 'ISBN',
				3 => 'Price',
				4 => 'PublisherId',
				5 => 'AuthorId'
			),
			BasePeer::TYPE_STUDLYPHPNAME => array(
				0 => 'id',
				1 => 'title',
				2 => 'iSBN',
				3 => 'price',
				4 => 'publisherId',
				5 => 'authorId'
			),
			BasePeer::TYPE_COLNAME => array(
				0 => 'book.ID',
				1 => 'book.TITLE',
				2 => 'book.ISBN',
				3 => 'book.PRICE',
				4 => 'book.PUBLISHER_ID',
				5 => 'book.AUTHOR_ID'
			),
			BasePeer::TYPE_FIELDNAME => array(
				0 => 'id',
				1 => 'title',
				2 => 'isbn',
				3 => 'price',
				4 => 'publisher_id',
				5 => 'author_id'
			),
			BasePeer::TYPE_NUM => array(
				0 => 0,
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5
			)
		);

		foreach ($types as $type) {
			$results[$type] = BasePeer::getFieldnames('Book', $type);
			$this->assertEquals(
				$expecteds[$type],
				$results[$type],
				'expected was: ' . print_r($expecteds[$type], 1) .
				'but getFieldnames() returned ' . print_r($results[$type], 1)
			);
		}
	}

	/**
	 * Tests the BasePeer::translateFieldName() method
	 */
	public function testTranslateFieldNameStatic () {

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_STUDLYPHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);
		$expecteds = array (
			BasePeer::TYPE_PHPNAME => 'AuthorId',
			BasePeer::TYPE_STUDLYPHPNAME => 'authorId',
			BasePeer::TYPE_COLNAME => 'book.AUTHOR_ID',
			BasePeer::TYPE_FIELDNAME => 'author_id',
			BasePeer::TYPE_NUM => 5,
		);
		foreach ($types as $fromType) {
			foreach ($types as $toType) {
				$name = $expecteds[$fromType];
				$expected = $expecteds[$toType];
				$result = BasePeer::translateFieldName('Book', $name, $fromType, $toType);
				$this->assertEquals($expected, $result);
			}
		}
	}

	/**
	 * Tests the Base[Object]::getByName() method
	 */
	public function testGetByName() {

		$types = array(
			BasePeer::TYPE_PHPNAME => 'Title',
			BasePeer::TYPE_STUDLYPHPNAME => 'title',
			BasePeer::TYPE_COLNAME => 'book.TITLE',
			BasePeer::TYPE_FIELDNAME => 'title',
			BasePeer::TYPE_NUM => 1
		);

		$book = new Book();
		$book->setTitle('Harry Potter and the Order of the Phoenix');

		$expected = 'Harry Potter and the Order of the Phoenix';
		foreach ($types as $type => $name) {
			$result = $book->getByName($name, $type);
			$this->assertEquals($expected, $result);
		}
	}

	/**
	 * Tests the Base[Object]::setByName() method
	 */
	public function testSetByName() {

		$book = new Book();
		$types = array(
			BasePeer::TYPE_PHPNAME => 'Title',
			BasePeer::TYPE_STUDLYPHPNAME => 'title',
			BasePeer::TYPE_COLNAME => 'book.TITLE',
			BasePeer::TYPE_FIELDNAME => 'title',
			BasePeer::TYPE_NUM => 1
		);

		$title = 'Harry Potter and the Order of the Phoenix';
		foreach ($types as $type => $name) {
			$book->setByName($name, $title, $type);
			$result = $book->getTitle();
			$this->assertEquals($title, $result);
		}
	}

	/**
	 * Tests the Base[Object]::fromArray() method
	 *
	 * this also tests populateFromArray() because that's an alias
	 */
	public function testFromArray(){

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_STUDLYPHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);
		$expecteds = array (
			BasePeer::TYPE_PHPNAME => array (
				'Title' => 'Harry Potter and the Order of the Phoenix',
				'ISBN' => '043935806X'
			),
			BasePeer::TYPE_STUDLYPHPNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'iSBN' => '043935806X'
			),
			BasePeer::TYPE_COLNAME => array (
				'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
				'book.ISBN' => '043935806X'
			),
			BasePeer::TYPE_FIELDNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'isbn' => '043935806X'
			),
			BasePeer::TYPE_NUM => array (
				'1' => 'Harry Potter and the Order of the Phoenix',
				'2' => '043935806X'
			)
		);

		$book = new Book();

		foreach ($types as $type) {
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

	/**
	 * Tests the Base[Object]::toArray() method
	 */
	public function testToArray(){

		$types = array(
			BasePeer::TYPE_PHPNAME,
			BasePeer::TYPE_STUDLYPHPNAME,
			BasePeer::TYPE_COLNAME,
			BasePeer::TYPE_FIELDNAME,
			BasePeer::TYPE_NUM
		);

		$book = new Book();
		$book->fromArray(array (
			'Title' => 'Harry Potter and the Order of the Phoenix',
			'ISBN' => '043935806X'
		));

		$expecteds = array (
			BasePeer::TYPE_PHPNAME => array (
				'Title' => 'Harry Potter and the Order of the Phoenix',
				'ISBN' => '043935806X'
			),
			BasePeer::TYPE_STUDLYPHPNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'iSBN' => '043935806X'
			),
			BasePeer::TYPE_COLNAME => array (
				'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
				'book.ISBN' => '043935806X'
			),
			BasePeer::TYPE_FIELDNAME => array (
				'title' => 'Harry Potter and the Order of the Phoenix',
				'isbn' => '043935806X'
			),
			BasePeer::TYPE_NUM => array (
				'1' => 'Harry Potter and the Order of the Phoenix',
				'2' => '043935806X'
			)
		);

		foreach ($types as $type) {
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
}
