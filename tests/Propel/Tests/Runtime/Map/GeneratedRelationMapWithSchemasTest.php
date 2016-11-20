<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Configuration;
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
        $this->databaseMap = Configuration::getCurrentConfiguration()->getDatabase('bookstore-schemas');
    }

    public function testGetRightTable()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\Bookstore');
        $contestTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $this->assertEquals(
            $bookTable->getName(),
            $contestTable->getRelation('bookstore')->getRightEntity()->getName(),
            'getRightEntity() returns correct table when called on a many to one relationship'
        );
        $this->assertEquals(
            $contestTable->getName(),
            $bookTable->getRelation('bookstoreContest')->getRightEntity()->getName(),
            'getRightEntity() returns correct table when called on a one to many relationship'
        );
        $bookCustomerTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\Customer');
        $bookCustomerAccTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\CustomerAccount');
        $this->assertEquals(
            $bookCustomerAccTable->getName(),
            $bookCustomerTable->getRelation('customerAccount')->getRightEntity()->getName(),
            'getRightEntity() returns correct table when called on a one to one relationship'
        );
        $this->assertEquals(
            $bookCustomerTable->getName(),
            $bookCustomerAccTable->getRelation('customer')->getRightEntity()->getName(),
            'getRightEntity() returns correct table when called on a one to one relationship'
        );
    }

    public function testColumnMappings()
    {
        $contestTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\BookstoreContest');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\BookstoreContest.bookstoreId' => 'Propel\Tests\BookstoreSchemas\Bookstore.id'), $contestTable->getRelation('Bookstore')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\BookstoreContest.bookstoreId' => 'Propel\Tests\BookstoreSchemas\Bookstore.id'), $contestTable->getRelation('Bookstore')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns local to foreign when asked left to right for a many to one relationship');

        $bookTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\Bookstore');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\BookstoreContest.bookstoreId' => 'Propel\Tests\BookstoreSchemas\Bookstore.id'), $bookTable->getRelation('BookstoreContest')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\Bookstore.id' => 'Propel\Tests\BookstoreSchemas\BookstoreContest.bookstoreId'), $bookTable->getRelation('BookstoreContest')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns foreign to local when asked left to right for a one to many relationship');

        $bookCustomerTable = $this->databaseMap->getEntity('Propel\Tests\BookstoreSchemas\Customer');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\CustomerAccount.customerId' => 'Propel\Tests\BookstoreSchemas\Customer.id'), $bookCustomerTable->getRelation('CustomerAccount')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(array('Propel\Tests\BookstoreSchemas\Customer.id' => 'Propel\Tests\BookstoreSchemas\CustomerAccount.customerId'), $bookCustomerTable->getRelation('CustomerAccount')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns foreign to local when asked left to right for a one to one relationship');
    }

}
