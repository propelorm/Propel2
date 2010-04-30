<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/bookstore/BookstoreEmptyTestBase.php';

/**
 * Test class for PropelObjectCollection.
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelObjectCollectionTest.php 1348 2009-12-03 21:49:00Z francois $
 * @package    runtime.collection
 */
class PropelObjectCollectionTest extends BookstoreEmptyTestBase
{
	protected function setUp()
	{
		parent::setUp();
		BookstoreDataPopulator::populate($this->con);
	}
	
	public function testSave()
	{
		$books = PropelQuery::from('Book')->find();
		foreach ($books as $book) {
			$book->setTitle('foo');
		}
		$books->save();
		// check that all the books are saved
		foreach ($books as $book) {
			$this->assertFalse($book->isModified());
		}
		// check that the modifications are persisted
		BookPeer::clearInstancePool();
		$books = PropelQuery::from('Book')->find();
		foreach ($books as $book) {
			$this->assertEquals('foo', $book->getTitle('foo'));
		}
	}

	public function testDelete()
	{
		$books = PropelQuery::from('Book')->find();
		$books->delete();
		// check that all the books are deleted
		foreach ($books as $book) {
			$this->assertTrue($book->isDeleted());
		}
		// check that the modifications are persisted
		BookPeer::clearInstancePool();
		$books = PropelQuery::from('Book')->find();
		$this->assertEquals(0, count($books));
	}
	
	public function testGetPrimaryKeys()
	{
		$books = PropelQuery::from('Book')->find();
		$pks = $books->getPrimaryKeys();
		$this->assertEquals(4, count($pks));
		
		$keys = array('Book_0', 'Book_1', 'Book_2', 'Book_3');
		$this->assertEquals($keys, array_keys($pks));
		
		$pks = $books->getPrimaryKeys(false);
		$keys = array(0, 1, 2, 3);
		$this->assertEquals($keys, array_keys($pks));
		
		foreach ($pks as $key => $value) {
			$this->assertEquals($books[$key]->getPrimaryKey(), $value);
		}
	}

	public function testFromArray()
	{
		$author = new Author();
		$author->setFirstName('Jane');
		$author->setLastName('Austen');
		$author->save();
		$books = array(
			array('Title' => 'Mansfield Park', 'AuthorId' => $author->getId()),
			array('Title' => 'Pride And PRejudice', 'AuthorId' => $author->getId())
		);
		$col = new PropelObjectCollection();
		$col->setModel('Book');
		$col->fromArray($books);
		$col->save();
		
		$nbBooks = PropelQuery::from('Book')->count();
		$this->assertEquals(6, $nbBooks);
		
		$booksByJane = PropelQuery::from('Book b')
			->join('b.Author a')
			->where('a.LastName = ?', 'Austen')
			->count();
		$this->assertEquals(2, $booksByJane);
	}
	
	public function testToArray()
	{
		$books = PropelQuery::from('Book')->find();
		$booksArray = $books->toArray();
		$this->assertEquals(4, count($booksArray));
		
		foreach ($booksArray as $key => $book) {
			$this->assertEquals($books[$key]->toArray(), $book);
		}
		
		$booksArray = $books->toArray();
		$keys = array(0, 1, 2, 3);
		$this->assertEquals($keys, array_keys($booksArray));
		
		$booksArray = $books->toArray(null, true);
		$keys = array('Book_0', 'Book_1', 'Book_2', 'Book_3');
		$this->assertEquals($keys, array_keys($booksArray));

		$booksArray = $books->toArray('Title');
		$keys = array('Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum');
		$this->assertEquals($keys, array_keys($booksArray));

		$booksArray = $books->toArray('Title', true);
		$keys = array('Book_Harry Potter and the Order of the Phoenix', 'Book_Quicksilver', 'Book_Don Juan', 'Book_The Tin Drum');
		$this->assertEquals($keys, array_keys($booksArray));
	}

	public function testGetArrayCopy()
	{
		$books = PropelQuery::from('Book')->find();
		$booksArray = $books->getArrayCopy();
		$this->assertEquals(4, count($booksArray));
		
		foreach ($booksArray as $key => $book) {
			$this->assertEquals($books[$key], $book);
		}
		
		$booksArray = $books->getArrayCopy();
		$keys = array(0, 1, 2, 3);
		$this->assertEquals($keys, array_keys($booksArray));
		
		$booksArray = $books->getArrayCopy(null, true);
		$keys = array('Book_0', 'Book_1', 'Book_2', 'Book_3');
		$this->assertEquals($keys, array_keys($booksArray));

		$booksArray = $books->getArrayCopy('Title');
		$keys = array('Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum');
		$this->assertEquals($keys, array_keys($booksArray));

		$booksArray = $books->getArrayCopy('Title', true);
		$keys = array('Book_Harry Potter and the Order of the Phoenix', 'Book_Quicksilver', 'Book_Don Juan', 'Book_The Tin Drum');
		$this->assertEquals($keys, array_keys($booksArray));
	}

	public function testToKeyValue()
	{
		$books = PropelQuery::from('Book')->find();
		
		$expected = array();
		foreach ($books as $book) {
			$expected[$book->getTitle()] = $book->getISBN();
		}
		$booksArray = $books->toKeyValue('Title', 'ISBN');
		$this->assertEquals(4, count($booksArray));
		$this->assertEquals($expected, $booksArray, 'toKeyValue() turns the collection to an associative array');

		$expected = array();
		foreach ($books as $book) {
			$expected[$book->getISBN()] = $book->getTitle();
		}
		$booksArray = $books->toKeyValue('ISBN');
		$this->assertEquals($expected, $booksArray, 'toKeyValue() uses __toString() for the value if no second field name is passed');
		
		$expected = array();
		foreach ($books as $book) {
			$expected[$book->getId()] = $book->getTitle();
		}
		$booksArray = $books->toKeyValue();
		$this->assertEquals($expected, $booksArray, 'toKeyValue() uses primary key for the key and __toString() for the value if no field name is passed');
	}

	public function testPopulateRelation()
	{
		AuthorPeer::clearInstancePool();
		BookPeer::clearInstancePool();
		$authors = AuthorQuery::create()->find();
		$books = $authors->populateRelation('Book');
		$this->assertTrue($books instanceof PropelObjectCollection, 'populateRelation() returns a PropelCollection instance');
		$this->assertEquals('Book', $books->getModel(), 'populateRelation() returns a collection of the related objects');
		$this->assertEquals(4, count($books), 'populateRelation() the list of related objects');
	}

	public function testPopulateRelationCriteria()
	{
		AuthorPeer::clearInstancePool();
		BookPeer::clearInstancePool();
		$authors = AuthorQuery::create()->find();
		$c = new Criteria();
		$c->setLimit(3);
		$books = $authors->populateRelation('Book', $c);
		$this->assertEquals(3, count($books), 'populateRelation() accepts an optional criteria object to filter the query');
	}
	
	public function testPopulateRelationOneToMany()
	{
		$con = Propel::getConnection();
		AuthorPeer::clearInstancePool();
		BookPeer::clearInstancePool();
		$authors = AuthorQuery::create()->find($con);
		$count = $con->getQueryCount();
		$books = $authors->populateRelation('Book', null, $con);
		foreach ($authors as $author) {
			foreach ($author->getBooks() as $book) {
				$this->assertEquals($author, $book->getAuthor());
			}
		}
		$this->assertEquals($count + 1, $con->getQueryCount(), 'populateRelation() populates a one-to-many relationship with a single supplementary query');
	}
	
	public function testPopulateRelationManyToOne()
	{
		$con = Propel::getConnection();
		AuthorPeer::clearInstancePool();
		BookPeer::clearInstancePool();
		$books = BookQuery::create()->find($con);
		$count = $con->getQueryCount();
		$books->populateRelation('Author', null, $con);
		foreach ($books as $book) {
			$author = $book->getAuthor();
		}
		$this->assertEquals($count + 1, $con->getQueryCount(), 'populateRelation() populates a many-to-one relationship with a single supplementary query');
	}
}