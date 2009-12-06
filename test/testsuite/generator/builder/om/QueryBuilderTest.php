<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'tools/helpers/bookstore/BookstoreDataPopulator.php';

/**
 * Test class for QueryBuilder.
 *
 * @author     François Zaninotto
 * @version    $Id: QueryBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    generator.builder.om
 */
class QueryBuilderTest extends BookstoreTestBase 
{ 
  
	public function testExtends()
	{
		$q = new BookQuery();
		$this->assertTrue($q instanceof ModelCriteria, 'Model query extends ModelCriteria');
	}
	
	public function testConstructor()
	{
		$query = new BookQuery();
		$this->assertEquals($query->getDbName(), 'bookstore', 'Constructor sets dabatase name');
		$this->assertEquals($query->getModelName(), 'Book', 'Constructor sets model name');
	}
	
	public function testBasePreSelect()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreSelect');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreSelect()');
	}

	public function testBasePreDelete()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreDelete');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreDelete()');
	}

	public function testBasePreUpdate()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreUpdate');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreUpdate()');
	}

	public function testQuery()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$q = new BookQuery();
		$book = $q
			->setModelAlias('b')
			->where('b.Title like ?', 'Don%')
			->orderBy('b.ISBN', 'desc')
			->findOne();
		$this->assertTrue($book instanceof Book);
		$this->assertEquals('Don Juan', $book->getTitle());
	}

}
