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
 * Test class for MultiExtensionQueryBuilder.
 *
 * @author     François Zaninotto
 * @version    $Id: QueryBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    generator.builder.om
 */
class QueryBuilderInheritanceTest extends BookstoreTestBase 
{ 
  
	public function testConstruct()
	{
		$query = BookstoreCashierQuery::create();
		$this->assertTrue($query instanceof BookstoreCashierQuery, 'the create() factory returns an instance of the correct class');
	}
	
	public function testFindFilter()
	{
		BookstoreDataPopulator::depopulate($this->con);
		$employee = new BookstoreEmployee();
		$employee->save($this->con);
		$manager = new BookstoreManager();
		$manager->save($this->con);
		$cashier1 = new BookstoreCashier();
		$cashier1->save($this->con);
		$cashier2 = new BookstoreCashier();
		$cashier2->save($this->con);
		$nbEmp = BookstoreEmployeeQuery::create()->count($this->con);
		$this->assertEquals(4, $nbEmp, 'find() in main query returns all results');
		$nbMan = BookstoreManagerQuery::create()->count($this->con);
		$this->assertEquals(1, $nbMan, 'find() in sub query returns only child results');
		$nbCash = BookstoreCashierQuery::create()->count($this->con);
		$this->assertEquals(2, $nbCash, 'find() in sub query returns only child results');
	}

	public function testUpdateFilter()
	{
		BookstoreDataPopulator::depopulate($this->con);
		$manager = new BookstoreManager();
		$manager->save($this->con);
		$cashier1 = new BookstoreCashier();
		$cashier1->save($this->con);
		$cashier2 = new BookstoreCashier();
		$cashier2->save($this->con);
		BookstoreManagerQuery::create()->update(array('Name' => 'foo'), $this->con);
		$nbMan = BookstoreEmployeeQuery::create()
		  ->filterByName('foo')
		  ->count($this->con);
		$this->assertEquals(1, $nbMan, 'Update in sub query affects only child results');
	}

	public function testDeleteFilter()
	{
		BookstoreDataPopulator::depopulate($this->con);
		$manager = new BookstoreManager();
		$manager->save($this->con);
		$cashier1 = new BookstoreCashier();
		$cashier1->save($this->con);
		$cashier2 = new BookstoreCashier();
		$cashier2->save($this->con);
		BookstoreManagerQuery::create()
			->filterByName()
			->delete();
		$nbCash = BookstoreEmployeeQuery::create()->count();
		$this->assertEquals(2, $nbCash, 'Delete in sub query affects only child results');
	}
	
	public function testDeleteAllFilter()
	{
		BookstoreDataPopulator::depopulate($this->con);
		$manager = new BookstoreManager();
		$manager->save($this->con);
		$cashier1 = new BookstoreCashier();
		$cashier1->save($this->con);
		$cashier2 = new BookstoreCashier();
		$cashier2->save($this->con);
		BookstoreManagerQuery::create()->deleteAll();
		$nbCash = BookstoreEmployeeQuery::create()->count();
		$this->assertEquals(2, $nbCash, 'Delete in sub query affects only child results');
	}
}

