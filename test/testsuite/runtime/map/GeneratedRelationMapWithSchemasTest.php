<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../fixtures/schemas/build/classes'));

/**
 * Test class for PHP5TableMapBuilder with schemas.
 *
 * @author     Ulf Hermann
 * @version    $Id$
 * @package    runtime.map
 */
class GeneratedRelationMapWithSchemasTest extends PHPUnit_Framework_TestCase
{
	protected $databaseMap;

	protected function setUp()
	{
		parent::setUp();
		Propel::init(dirname(__FILE__) . '/../../../fixtures/schemas/build/conf/bookstore-conf.php');
		$this->databaseMap = Propel::getDatabaseMap('bookstore-schemas');
	}

	protected function tearDown()
	{
		parent::tearDown();
		Propel::init(dirname(__FILE__) . '/../../../fixtures/bookstore/build/conf/bookstore-conf.php');
	}

	public function testGetRightTable()
	{
		$bookTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasBookstore');
		$contestTable = $this->databaseMap->getTableByPhpName('ContestBookstoreContest');
		$this->assertEquals(
			$bookTable->getName(),
			$contestTable->getRelation('BookstoreSchemasBookstore')->getRightTable()->getName(),
			'getRightTable() returns correct table when called on a many to one relationship'
		);
		$this->assertEquals(
			$contestTable->getName(),
			$bookTable->getRelation('ContestBookstoreContest')->getRightTable()->getName(),
			'getRightTable() returns correct table when called on a one to many relationship'
		);
		$bookCustomerTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomer');
		$bookCustomerAccTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomerAccount');
		$this->assertEquals(
			$bookCustomerAccTable->getName(),
			$bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getRightTable()->getName(),
			'getRightTable() returns correct table when called on a one to one relationship'
		);
		$this->assertEquals(
			$bookCustomerTable->getName(),
			$bookCustomerAccTable->getRelation('BookstoreSchemasCustomer')->getRightTable()->getName(),
			'getRightTable() returns correct table when called on a one to one relationship'
		);
	}

	public function testColumnMappings()
	{
		$contestTable = $this->databaseMap->getTableByPhpName('ContestBookstoreContest');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $contestTable->getRelation('BookstoreSchemasBookstore')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $contestTable->getRelation('BookstoreSchemasBookstore')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns local to foreign when asked left to right for a many to one relationship');

		$bookTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasBookstore');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $bookTable->getRelation('ContestBookstoreContest')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('bookstore_schemas.bookstore.ID' => 'contest.bookstore_contest.BOOKSTORE_ID'), $bookTable->getRelation('ContestBookstoreContest')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to many relationship');

		$bookCustomerTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomer');
		$this->assertEquals(array('bookstore_schemas.customer_account.CUSTOMER_ID' => 'bookstore_schemas.customer.ID'), $bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('bookstore_schemas.customer.ID' => 'bookstore_schemas.customer_account.CUSTOMER_ID'), $bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to one relationship');
	}

}
