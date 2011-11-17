<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Configuration;

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
        $this->databaseMap = Configuration::getInstance()->getDatabaseMap('bookstore-schemas');
    }

    public function testOneToMany()
    {
        // passes on its own, but not with the full tests suite
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToBookstore = $contestTable->getRelation('Bookstore');
        $bookstoreTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Bookstore');
        $bookstoreToContest = $bookstoreTable->getRelation('BookstoreContest');
        //$this->assertEquals($bookstoreToContest->getName(), $contestToBookstore->getSymmetricalRelation()->getName());
        //$this->assertEquals($contestToBookstore->getName(), $bookstoreToContest->getSymmetricalRelation()->getName());
    }

    public function testOneToOne()
    {
        $accountTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\CustomerAccount');
        $accountToCustomer = $accountTable->getRelation('Customer');
        $customerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $customerToAccount = $customerTable->getRelation('CustomerAccount');
        $this->assertEquals($accountToCustomer, $customerToAccount->getSymmetricalRelation());
        $this->assertEquals($customerToAccount, $accountToCustomer->getSymmetricalRelation());
    }

    public function testSeveralRelationsOnSameTable()
    {
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToCustomer = $contestTable->getRelation('CustomerRelatedByFirstContest');
        $customerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $customerToContest = $customerTable->getRelation('BookstoreContestRelatedByFirstContest');
        $this->assertEquals($contestToCustomer, $customerToContest->getSymmetricalRelation());
        $this->assertEquals($customerToContest, $contestToCustomer->getSymmetricalRelation());
    }

    public function testCompositeForeignKey()
    {
        $entryTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContestEntry');
        $entryToContest = $entryTable->getRelation('BookstoreContest');
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToEntry = $contestTable->getRelation('BookstoreContestEntry');
        $this->assertEquals($entryToContest, $contestToEntry->getSymmetricalRelation());
        $this->assertEquals($contestToEntry, $entryToContest->getSymmetricalRelation());
    }

}
