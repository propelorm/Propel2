<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Tests\BookstoreSchemas\Bookstore;
use Propel\Tests\BookstoreSchemas\BookstoreContest;
use Propel\Tests\BookstoreSchemas\BookstoreContestEntry;
use Propel\Tests\BookstoreSchemas\BookstoreContestEntryQuery;
use Propel\Tests\BookstoreSchemas\BookstoreContestQuery;
use Propel\Tests\BookstoreSchemas\BookstoreQuery;
use Propel\Tests\BookstoreSchemas\Map\BookstoreTableMap;
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
class AggregateColumnBehaviorWithSchemaTest extends TestCaseFixturesDatabase
{
    public function testParametersWithSchema()
    {
        $storeTable = BookstoreTableMap::getTableMap();
        $this->assertEquals(count($storeTable->getColumns()), 8, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('Propel\Tests\BookstoreSchemas\Bookstore', 'getTotalContestEntries'));
    }

    public function testComputeWithSchema()
    {
        $con = Propel::getServiceContainer()->getConnection(BookstoreTableMap::DATABASE_NAME);

        BookstoreContestEntryQuery::create()->deleteAll();
        BookstoreQuery::create()->deleteAll();
        CustomerQuery::create()->deleteAll();
        BookstoreContestQuery::create()->deleteAll();

        $store = new Bookstore();
        $store->setStoreName('FreeAgent Bookstore');
        $store->save();
        $this->assertEquals(0, $store->computeTotalContestEntries($con), 'The compute method returns 0 for objects with no related objects');

        $contest = new BookstoreContest();
        $contest->setBookstore($store);
        $contest->save();
        $customer1 = new Customer();
        $customer1->save();

        $entry1 = new BookstoreContestEntry();
        $entry1->setBookstore($store);
        $entry1->setBookstoreContest($contest);
        $entry1->setCustomer($customer1);
        $entry1->save(null, true); // skip reload to avoid #1151 for now

        $this->assertEquals(1, $store->computeTotalContestEntries($con), 'The compute method computes the aggregate function on related objects');

        $customer2 = new Customer();
        $customer2->save();

        $entry2 = new BookstoreContestEntry();
        $entry2->setBookstore($store);
        $entry2->setBookstoreContest($contest);
        $entry2->setCustomer($customer2);
        $entry2->save(null, true); // skip reload to avoid #1151 for now

        $this->assertEquals(2, $store->computeTotalContestEntries($con), 'The compute method computes the aggregate function on related objects');
        $entry1->delete();
        $this->assertEquals(1, $store->computeTotalContestEntries($con), 'The compute method computes the aggregate function on related objects');
    }
}
