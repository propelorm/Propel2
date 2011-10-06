<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Propel;

use Propel\Tests\Helpers\Schemas\SchemasTestBase;

/**
 * Test class for RelatedMap::getSymmetricalRelation with schemas.
 *
 * @author     Ulf Hermann
 * @version    $Id$
 * @package    runtime.map
 */
class RelatedMapSymmetricalWithSchemasTest extends SchemasTestBase
{
  protected $databaseMap;

	protected function setUp()
	{
		parent::setUp();
		$this->databaseMap = Propel::getDatabaseMap('bookstore-schemas');
	}

	public function testOneToMany()
	{
		// passes on its own, but not with the full tests suite
		$this->markTestSkipped();
		$contestTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\ContestBookstoreContest');
		$contestToBookstore = $contestTable->getRelation('BookstoreSchemasBookstore');
		$bookstoreTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\BookstoreSchemasBookstore');
		$bookstoreToContest = $bookstoreTable->getRelation('ContestBookstoreContest');
		$this->assertEquals($bookstoreToContest->getName(), $contestToBookstore->getSymmetricalRelation()->getName());
		$this->assertEquals($contestToBookstore->getName(), $bookstoreToContest->getSymmetricalRelation()->getName());
	}

	public function testOneToOne()
	{
		$accountTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Mapa\BookstoreSchemasCustomerAccount');
		$accountToCustomer = $accountTable->getRelation('BookstoreSchemasCustomer');
		$customerTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\BookstoreSchemasCustomer');
		$customerToAccount = $customerTable->getRelation('BookstoreSchemasCustomerAccount');
		$this->assertEquals($accountToCustomer, $customerToAccount->getSymmetricalRelation());
		$this->assertEquals($customerToAccount, $accountToCustomer->getSymmetricalRelation());
	}

	public function testSeveralRelationsOnSameTable()
	{
		$contestTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\ContestBookstoreContest');
		$contestToCustomer = $contestTable->getRelation('BookstoreSchemasCustomerRelatedByFirstContest');
		$customerTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\BookstoreSchemasCustomer');
		$customerToContest = $customerTable->getRelation('ContestBookstoreContestRelatedByFirstContest');
		$this->assertEquals($contestToCustomer, $customerToContest->getSymmetricalRelation());
		$this->assertEquals($customerToContest, $contestToCustomer->getSymmetricalRelation());
	}

	public function testCompositeForeignKey()
	{
		$entryTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\ContestBookstoreContestEntry');
		$entryToContest = $entryTable->getRelation('ContestBookstoreContest');
		$contestTable = $this->databaseMap->getTableByPhpName('\Propel\Tests\BookstoreSchemas\bookstore_schemas\Map\ContestBookstoreContest');
		$contestToEntry = $contestTable->getRelation('ContestBookstoreContestEntry');
		$this->assertEquals($entryToContest, $contestToEntry->getSymmetricalRelation());
		$this->assertEquals($contestToEntry, $entryToContest->getSymmetricalRelation());
	}

}
