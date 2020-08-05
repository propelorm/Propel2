<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for RelatedMap::getSymmetricalRelation with schemas.
 *
 * @author Ulf Hermann
 *
 * @group database
 */
class RelatedMapSymmetricalWithSchemasTest extends TestCaseFixturesDatabase
{
    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMap = Propel::getServiceContainer()->getDatabaseMap('bookstore-schemas');
    }

    /**
     * @return void
     */
    public function testOneToMany()
    {
        // passes on its own, but not with the full tests suite
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToBookstore = $contestTable->getRelation('Bookstore');
        $bookstoreTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Bookstore');
        $bookstoreToContest = $bookstoreTable->getRelation('BookstoreContest');

        $this->markTestIncomplete('The two following tests don\'t pass');
        $this->assertEquals($bookstoreToContest->getName(), $contestToBookstore->getSymmetricalRelation()->getName());
        $this->assertEquals($contestToBookstore->getName(), $bookstoreToContest->getSymmetricalRelation()->getName());
    }

    /**
     * @return void
     */
    public function testOneToOne()
    {
        $accountTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\CustomerAccount');
        $accountToCustomer = $accountTable->getRelation('Customer');
        $customerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $customerToAccount = $customerTable->getRelation('CustomerAccount');

        $this->markTestIncomplete('The two following tests don\'t pass');
        $this->assertEquals($accountToCustomer, $customerToAccount->getSymmetricalRelation());
        $this->assertEquals($customerToAccount, $accountToCustomer->getSymmetricalRelation());
    }

    /**
     * @return void
     */
    public function testSeveralRelationsOnSameTable()
    {
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToCustomer = $contestTable->getRelation('CustomerRelatedByFirstContestId');
        $customerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $customerToContest = $customerTable->getRelation('FirstContest');

        $this->markTestIncomplete('The two following tests don\'t pass');
        $this->assertEquals($contestToCustomer, $customerToContest->getSymmetricalRelation());
        $this->assertEquals($customerToContest, $contestToCustomer->getSymmetricalRelation());
    }

    /**
     * @return void
     */
    public function testCompositeForeignKey()
    {
        $entryTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContestEntry');
        $entryToContest = $entryTable->getRelation('BookstoreContest');
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $contestToEntry = $contestTable->getRelation('BookstoreContestEntry');

        $this->markTestIncomplete('The two following tests don\'t pass');
        $this->assertEquals($entryToContest, $contestToEntry->getSymmetricalRelation());
        $this->assertEquals($contestToEntry, $entryToContest->getSymmetricalRelation());
    }
}
