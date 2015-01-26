<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Propel;
use Propel\Runtime\Map\RelationMap;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for PHP5TableMapBuilder with schemas.
 *
 * @author Ulf Hermann
 *
 * @group database
 */
class GeneratedRelationMapWithSchemasTest extends TestCaseFixturesDatabase
{
    /**
     * @var \Propel\Runtime\Map\DatabaseMap
     */
    protected $databaseMap;

    protected function setUp()
    {
        parent::setUp();
        $this->databaseMap = Propel::getServiceContainer()->getDatabaseMap('bookstore-schemas');
    }

    public function testGetRightTable()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Bookstore');
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $this->assertEquals(
            $bookTable->getName(),
            $contestTable->getRelation('Bookstore')->getRightTable()->getName(),
            'getRightTable() returns correct table when called on a many to one relationship'
        );
        $this->assertEquals(
            $contestTable->getName(),
            $bookTable->getRelation('BookstoreContest')->getRightTable()->getName(),
            'getRightTable() returns correct table when called on a one to many relationship'
        );
        $bookCustomerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $bookCustomerAccTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\CustomerAccount');
        $this->assertEquals(
            $bookCustomerAccTable->getName(),
            $bookCustomerTable->getRelation('CustomerAccount')->getRightTable()->getName(),
            'getRightTable() returns correct table when called on a one to one relationship'
        );
        $this->assertEquals(
            $bookCustomerTable->getName(),
            $bookCustomerAccTable->getRelation('Customer')->getRightTable()->getName(),
            'getRightTable() returns correct table when called on a one to one relationship'
        );
    }

    public function testColumnMappings()
    {
        $contestTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $del = $this->getPlatform()->getSchemaDelimiter();
        $this->assertEquals(array('contest'.$del.'bookstore_contest.bookstore_id' => 'bookstore_schemas'.$del.'bookstore.id'), $contestTable->getRelation('Bookstore')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('contest'.$del.'bookstore_contest.bookstore_id' => 'bookstore_schemas'.$del.'bookstore.id'), $contestTable->getRelation('Bookstore')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns local to foreign when asked left to right for a many to one relationship');

        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Bookstore');
        $this->assertEquals(array('contest'.$del.'bookstore_contest.bookstore_id' => 'bookstore_schemas'.$del.'bookstore.id'), $bookTable->getRelation('BookstoreContest')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('bookstore_schemas'.$del.'bookstore.id' => 'contest'.$del.'bookstore_contest.bookstore_id'), $bookTable->getRelation('BookstoreContest')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to many relationship');

        $bookCustomerTable = $this->databaseMap->getTableByPhpName('Propel\Tests\BookstoreSchemas\Customer');
        $this->assertEquals(array('bookstore_schemas'.$del.'customer_account.customer_id' => 'bookstore_schemas'.$del.'customer.id'), $bookCustomerTable->getRelation('CustomerAccount')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('bookstore_schemas'.$del.'customer.id' => 'bookstore_schemas'.$del.'customer_account.customer_id'), $bookCustomerTable->getRelation('CustomerAccount')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to one relationship');
    }

}
