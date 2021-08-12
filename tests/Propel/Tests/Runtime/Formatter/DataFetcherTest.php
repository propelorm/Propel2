<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Formatter;

use PDO;
use Propel\Runtime\DataFetcher\ArrayDataFetcher;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for DataFetcher.
 *
 * @group database
 */
class DataFetcherTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    /**
     * @return void
     */
    public function testGeneral()
    {
        $items = [5, 22, 33];
        $items2 = [882, 34];

        $dataFetcher = new ArrayDataFetcher($items);
        $this->assertEquals($items, $dataFetcher->getDataObject());

        $dataFetcher->setDataObject($items2);
        $this->assertEquals($items2, $dataFetcher->getDataObject());
    }

    /**
     * @return void
     */
    public function testPDODataFetcher()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query('SELECT id, title, isbn, price, publisher_id, author_id FROM book');
        $this->assertInstanceOf('Propel\Runtime\DataFetcher\PDODataFetcher', $dataFetcher);

        $this->assertEquals(4, $dataFetcher->count());

        $i = 0;
        while ($row = $dataFetcher->fetch()) {
            $last = $row;
            $this->assertNotNull($row);
            $i++;
        }

        $this->assertCount(6, $last);
        $this->assertEquals(4, $i);
        $this->assertEquals('The Tin Drum', $last[1]);
        $this->assertEquals('067972575X', $last[2]);

        $last = null;
        foreach ($dataFetcher as $row) {
            $last = $row;
        }
        $this->assertNull($last);

        $dataFetcher = $con->query('SELECT id, title, isbn, price, publisher_id, author_id FROM book');
        $this->assertEquals('Harry Potter and the Order of the Phoenix', $dataFetcher->fetchColumn(1));
        $this->assertEquals('Quicksilver', $dataFetcher->fetchColumn(1));

        $dataFetcher = $con->query('SELECT id, title, isbn, price, publisher_id, author_id FROM book');
        $rows = [];
        $last = null;
        $i = -1;

        foreach ($dataFetcher as $k => $row) {
            $rows[] = $row;
            $last = $row;
            $i++;
            $this->assertNotNull($row);
            $this->assertEquals($i, $k);
        }
        $this->assertCount(4, $rows);
        $this->assertEquals('The Tin Drum', $last[1]);
    }

    /**
     * @return void
     */
    public function testPDODataFetcherFetchAllReturnsAllRowsAsArray()
    {
        $query = 'SELECT id, title FROM book';
        $fetcher = $this->con->query($query);
        $rows = $fetcher->fetchAll();

        $this->assertIsArray($rows, 'PDODataFetcher::fetchAll() should return an array');
        $this->assertCount($fetcher->count(), $rows, 'Expected number of rows should be returned');
    }

    /**
     * @return void
     */
    public function testPDODataFetcherFetchAllUsesFetchStyle()
    {
        $query = 'SELECT id, title FROM book';
        $keyOptions = [
            PDO::FETCH_BOTH => ['id', 0, 'title', 1],
            PDO::FETCH_NUM => [0, 1],
            PDO::FETCH_ASSOC => ['id', 'title'],
        ];

        foreach ($keyOptions as $fetchStyle => $expectedKeys) {
            $fetcher = $this->con->query($query);
            $rows = $fetcher->fetchAll($fetchStyle);
            $this->assertNotEmpty($rows);
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $this->assertEquals($expectedKeys, $keys);
            }
        }
    }

    /**
     * @return void
     */
    public function testArrayDataFetcher()
    {
        $items = [
            ['col1' => 'Peter', 'col2' => 'Mueller'],
            ['col1' => 'Sergey', 'col2' => 'Sayer'],
        ];

        $dataFetcher = new ArrayDataFetcher($items);
        $this->assertEquals(TableMap::TYPE_PHPNAME, $dataFetcher->getIndexType());

        while ($row = $dataFetcher->fetch()) {
            $testItems[] = $row;
            $this->assertNotNull($row);
        }

        $this->assertCount(2, $testItems);
        $this->assertEquals($items, $testItems);

        $dataFetcher->rewind();
        foreach ($dataFetcher as $k => $row) {
            $testItems2[] = $row;
            $this->assertGreaterThanOrEqual(0, $k);
            $this->assertNotNull($row);
        }

        $this->assertCount(2, $testItems2);
        $this->assertEquals($items, $testItems2);

        $dataFetcher2 = new ArrayDataFetcher($items);
        $this->assertEquals(2, $dataFetcher2->count());
        $this->assertEquals('Peter', $dataFetcher2->fetchColumn());
        $this->assertEquals('Sayer', $dataFetcher2->fetchColumn('col2'));
        $this->assertSame(null, $dataFetcher2->fetchColumn()); //no rows left, returns NULL
        $this->assertSame(null, $dataFetcher2->fetchColumn()); //be sure further calls returns NULL as well

        $dataFetcher2->close();
        $this->assertSame(null, $dataFetcher2->fetchColumn());

        $dataFetcher3 = new ArrayDataFetcher($items);
        $dataFetcher3->close();
        $this->assertSame(null, $dataFetcher3->fetch());
        $this->assertSame(null, $dataFetcher3->fetchColumn());
    }
}
