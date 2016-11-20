<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Tests\BookstoreSchemas\Base\BaseBookstoreRepository;
use Propel\Tests\BookstoreSchemas\Bookstore;
use Propel\Tests\BookstoreSchemas\BookstoreContest;
use Propel\Tests\BookstoreSchemas\BookstoreContestEntry;
use Propel\Tests\BookstoreSchemas\BookstoreContestEntryQuery;
use Propel\Tests\BookstoreSchemas\BookstoreContestQuery;
use Propel\Tests\BookstoreSchemas\BookstoreQuery;
use Propel\Tests\BookstoreSchemas\Map\BookstoreEntityMap;
use Propel\Tests\BookstoreSchemas\Customer;
use Propel\Tests\BookstoreSchemas\CustomerQuery;

use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for AggregateColumnBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class AggregateFieldBehaviorWithSchemaTest extends TestCaseFixturesDatabase
{
    public function testParametersWithSchema()
    {
        $storeTable = $this->configuration->getEntityMap(BookstoreEntityMap::ENTITY_CLASS);
        $this->assertEquals(count($storeTable->getFields()), 8, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('Propel\Tests\BookstoreSchemas\Bookstore', 'getTotalContestEntries'));
    }

    public function testComputeWithSchema()
    {
        BookstoreContestEntryQuery::create()->deleteAll();
        BookstoreQuery::create()->deleteAll();
        CustomerQuery::create()->deleteAll();
        BookstoreContestQuery::create()->deleteAll();

        /** @var BaseBookstoreRepository $bookstoreRepository */
        $bookstoreRepository = $this->configuration->getRepository(BookstoreEntityMap::ENTITY_CLASS);

        $store = new Bookstore();
        $store->setStoreName('FreeAgent Bookstore');
        $store->save();
        $this->assertEquals(0, $bookstoreRepository->computeTotalContestEntries($store), 'The compute method returns 0 for objects with no related objects');

        $contest = new BookstoreContest();
        $contest->setBookstore($store);
        $contest->save();
        $customer1 = new Customer();
        $customer1->save();

        $entry1 = new BookstoreContestEntry();
        $entry1->setBookstore($store);
        $entry1->setBookstoreContest($contest);
        $entry1->setCustomer($customer1);
        $entry1->save();

        $this->assertEquals(1, $bookstoreRepository->computeTotalContestEntries($store), 'The compute method computes the aggregate function on related objects');

        $customer2 = new Customer();
        $customer2->save();

        $entry2 = new BookstoreContestEntry();
        $entry2->setBookstore($store);
        $entry2->setBookstoreContest($contest);
        $entry2->setCustomer($customer2);
        $entry2->save();

        $this->assertEquals(2, $bookstoreRepository->computeTotalContestEntries($store), 'The compute method computes the aggregate function on related objects');
        $entry1->delete();
        $this->assertEquals(1, $bookstoreRepository->computeTotalContestEntries($store), 'The compute method computes the aggregate function on related objects');
    }
}
