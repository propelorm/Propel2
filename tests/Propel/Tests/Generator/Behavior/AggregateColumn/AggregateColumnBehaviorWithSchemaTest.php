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
use Propel\Tests\Helpers\Schemas\SchemasTestBase;

use Propel\Runtime\Propel;

/**
 * Tests for AggregateColumnBehavior class
 *
 * @author François Zaninotto
 */
class AggregateColumnBehaviorWithSchemaTest extends SchemasTestBase
{
    protected function setUp()
    {
        parent::setUp();

        $this->con = Propel::getServiceContainer()->getConnection(BookstoreTableMap::DATABASE_NAME);
        $this->con->beginTransaction();
    }

    protected function tearDown()
    {
        $this->con->commit();
        parent::tearDown();
    }

    public function testParametersWithSchema()
    {
        $storeTable = BookstoreTableMap::getTableMap();
        $this->assertEquals(count($storeTable->getColumns()), 8, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('Propel\Tests\BookstoreSchemas\Bookstore', 'getTotalContestEntries'));
    }

    public function testComputeWithSchema()
    {
        BookstoreContestEntryQuery::create()->deleteAll($this->con);
        BookstoreQuery::create()->deleteAll($this->con);
        CustomerQuery::create()->deleteAll($this->con);
        BookstoreContestQuery::create()->deleteAll($this->con);

        $store = new Bookstore();
        $store->setStoreName('FreeAgent Bookstore');
        $store->save($this->con);
        $this->assertEquals(0, $store->computeTotalContestEntries($this->con), 'The compute method returns 0 for objects with no related objects');

        $contest = new BookstoreContest();
        $contest->setBookstore($store);
        $contest->save($this->con);
        $customer1 = new Customer();
        $customer1->save($this->con);

        $entry1 = new BookstoreContestEntry();
        $entry1->setBookstore($store);
        $entry1->setBookstoreContest($contest);
        $entry1->setCustomer($customer1);
        $entry1->save($this->con, true); // skip reload to avoid #1151 for now

        $this->assertEquals(1, $store->computeTotalContestEntries($this->con), 'The compute method computes the aggregate function on related objects');

        $customer2 = new Customer();
        $customer2->save($this->con);

        $entry2 = new BookstoreContestEntry();
        $entry2->setBookstore($store);
        $entry2->setBookstoreContest($contest);
        $entry2->setCustomer($customer2);
        $entry2->save($this->con, true); // skip reload to avoid #1151 for now

        $this->assertEquals(2, $store->computeTotalContestEntries($this->con), 'The compute method computes the aggregate function on related objects');
        $entry1->delete($this->con);
        $this->assertEquals(1, $store->computeTotalContestEntries($this->con), 'The compute method computes the aggregate function on related objects');
    }
}
