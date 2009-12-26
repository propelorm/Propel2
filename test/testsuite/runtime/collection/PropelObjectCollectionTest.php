<?php

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
		
		$keys = array('Book_0', 'Book_1', 'Book_2', 'Book_3');
		$this->assertEquals($keys, array_keys($booksArray));
		
		$booksArray = $books->toArray(false);
		$keys = array(0, 1, 2, 3);
		$this->assertEquals($keys, array_keys($booksArray));
		
		foreach ($booksArray as $key => $book) {
			$this->assertEquals($books[$key]->toArray(), $book);
		}
	}

}