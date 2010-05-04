<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'tools/helpers/bookstore/BookstoreDataPopulator.php';

/**
 * Test class for ModelWith.
 *
 * @author     François Zaninotto
 * @version    $Id: ModelJoinTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    runtime.query
 */
class ModelWithTest extends BookstoreTestBase 
{
	
	public function testModelNameManyToOne()
	{
		$q = BookQuery::create()
		 ->joinAuthor();
		$joins = $q->getJoins();
		$join = $joins['Author'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getModelName(), 'Author', 'A ModelWith computes the model name from the join');
		$this->assertEquals($with->getModelPeerName(), 'AuthorPeer', 'A ModelWith computes the model peer name from the join');
	}

	public function testModelNameOneToMany()
	{
		$q = AuthorQuery::create()
		 ->joinBook();
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getModelName(), 'Book', 'A ModelWith computes the model peer name from the join');
		$this->assertEquals($with->getModelPeerName(), 'BookPeer', 'A ModelWith computes the model peer name from the join');
	}
	
	public function testModelNameAlias()
	{
		$q = BookQuery::create()
		 ->joinAuthor('a');
		$joins = $q->getJoins();
		$join = $joins['a'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getModelName(), 'Author', 'A ModelWith computes the model peer name from the join');
		$this->assertEquals($with->getModelPeerName(), 'AuthorPeer', 'A ModelWith computes the model peer name from the join');
	}

	public function testRelationManyToOne()
	{
		$q = BookQuery::create()
		 ->joinAuthor();
		$joins = $q->getJoins();
		$join = $joins['Author'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelationMethod(), 'setAuthor', 'A ModelWith computes the relation method from the join');
		$this->assertEquals($with->getRelationName(), 'Author', 'A ModelWith computes the relation name from the join');
		$this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
	}

	public function testRelationOneToMany()
	{
		$q = AuthorQuery::create()
		 ->joinBook();
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelationMethod(), 'addBook', 'A ModelWith computes the relation method from the join');
		$this->assertEquals($with->getRelationName(), 'Books', 'A ModelWith computes the relation name from the join');
		$this->assertTrue($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
	}

	public function testRelationOneToOne()
	{
		$q = BookstoreEmployeeQuery::create()
		 ->joinBookstoreEmployeeAccount();
		$joins = $q->getJoins();
		$join = $joins['BookstoreEmployeeAccount'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelationMethod(), 'setBookstoreEmployeeAccount', 'A ModelWith computes the relation method from the join');
		$this->assertEquals($with->getRelationName(), 'BookstoreEmployeeAccount', 'A ModelWith computes the relation name from the join');
		$this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
	}
	
	public function testIsPrimary()
	{
		$q = AuthorQuery::create()
		 ->joinBook();
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');

		$q = BookQuery::create()
		 ->joinAuthor()
		 ->joinReview();
		$joins = $q->getJoins();
		$join = $joins['Review'];
		$with = new ModelWith($join);
		$this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');
		
		$q = AuthorQuery::create()
		 ->join('Author.Book')
		 ->join('Book.Publisher');
		$joins = $q->getJoins();
		$join = $joins['Publisher'];
		$with = new ModelWith($join);
		$this->assertFalse($with->isPrimary(), 'A ModelWith initialized from a non-primary join is not primary');
	}
	
	public function testGetRelatedClass()
	{
		$q = AuthorQuery::create()
		 ->joinBook();
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertNull($with->getRelatedClass(), 'A ModelWith initialized from a primary join has a null related class');

		$q = AuthorQuery::create('a')
		 ->joinBook();
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertNull($with->getRelatedClass(), 'A ModelWith initialized from a primary join with alias has a null related class');

		$q = AuthorQuery::create()
		 ->joinBook('b');
		$joins = $q->getJoins();
		$join = $joins['b'];
		$with = new ModelWith($join);
		$this->assertNull($with->getRelatedClass(), 'A ModelWith initialized from a primary join with alias has a null related class');
		
		$q = AuthorQuery::create()
		 ->join('Author.Book')
		 ->join('Book.Publisher');
		$joins = $q->getJoins();
		$join = $joins['Publisher'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'Book', 'A ModelWith uses the previous join relation name as related class');

		$q = ReviewQuery::create()
		 ->join('Review.Book')
		 ->join('Book.Author')
		 ->join('Book.Publisher');
		$joins = $q->getJoins();
		$join = $joins['Publisher'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'Book', 'A ModelWith uses the previous join relation name as related class');

		$q = ReviewQuery::create()
		 ->join('Review.Book')
		 ->join('Book.BookOpinion')
		 ->join('BookOpinion.BookReader');
		$joins = $q->getJoins();
		$join = $joins['BookOpinion'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'Book', 'A ModelWith uses the previous join relation name as related class');
		$join = $joins['BookReader'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'BookOpinion', 'A ModelWith uses the previous join relation name as related class');

		$q = BookReaderQuery::create()
		 ->join('BookReader.BookOpinion')
		 ->join('BookOpinion.Book')
		 ->join('Book.Author');
		$joins = $q->getJoins();
		$join = $joins['Book'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'BookOpinion', 'A ModelWith uses the previous join relation name as related class');
		$join = $joins['Author'];
		$with = new ModelWith($join);
		$this->assertEquals($with->getRelatedClass(), 'Book', 'A ModelWith uses the previous join relation name as related class');
	}
}
