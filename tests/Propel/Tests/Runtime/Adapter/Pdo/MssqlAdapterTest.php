<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Adapter\Pdo\MssqlAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCase;

/**
 * Tests the MSSQL adapter
 *
 * @author Chase McManning <mcmanning.1@osu.edu>
 */
class MssqlAdapterTest extends TestCase
{
    /**
     * DB adapter saved for later.
     *
     * @var \Propel\Runtime\Adapter\AdapterInterface
     */
    private $savedAdapter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        Propel::init(__DIR__ . '/../../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
        parent::setUp();

        // Cache and swap the default database adapter with Mssql.
        $defaultDatasource = Propel::getServiceContainer()
                                ->getDefaultDatasource();

        $this->savedAdapter = Propel::getServiceContainer()
                                ->getAdapter($defaultDatasource);

        Propel::getServiceContainer()->setAdapter(
            $defaultDatasource,
            new MssqlAdapter()
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Propel::getServiceContainer()->setAdapter(
            Propel::getServiceContainer()->getDefaultDatasource(),
            $this->savedAdapter
        );

        parent::tearDown();
    }

    /**
     * @return string
     */
    protected function getDriver()
    {
        return 'mssql';
    }

    /**
     * Test `applyLimit` with no offsetting
     *
     * @return void
     */
    public function testApplyLimitZeroOffset()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);

        $c->setOffset(0);
        $c->setLimit(10);

        $params = [];
        $sql = $c->createSelectSql($params);

        // Expect a TOP N result with no subquery
        $expected = 'SELECT TOP 10 book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book';

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test `applyLimit` with page offsetting
     *
     * @return void
     */
    public function testApplyLimitOffset()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);

        $c->setOffset(10);
        $c->setLimit(10);

        $params = [];
        $sql = $c->createSelectSql($params);

        // Expect a subquery
        $expected = 'SELECT [book.id], [book.title], [book.isbn], [book.price], [book.publisher_id], [book.author_id] FROM (SELECT ROW_NUMBER() OVER(ORDER BY book.id) AS [RowNumber], book.id AS [book.id], book.title AS [book.title], book.isbn AS [book.isbn], book.price AS [book.price], book.publisher_id AS [book.publisher_id], book.author_id AS [book.author_id] FROM book) AS derivedb WHERE RowNumber BETWEEN 11 AND 20';

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test `applyLimit` with no offsetting and additional virtual columns
     *
     * @return void
     */
    public function testApplyLimitZeroOffsetWithVirtualColumns()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);

        $c->addAsColumn(
            'author_email',
            '(SELECT email FROM author WHERE id = book.author_id)'
        );

        $c->setOffset(0);
        $c->setLimit(10);

        $params = [];
        $sql = $c->createSelectSql($params);

        // Expect a TOP N, subquery in the SELECT clause
        $expected = 'SELECT TOP 10 book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, (SELECT email FROM author WHERE id = book.author_id) AS author_email FROM book';

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test `applyLimit` with page offsetting and additional virtual columns
     *
     * @return void
     */
    public function testApplyLimitOffsetWithVirtualColumns()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);

        $c->addAsColumn(
            'author_email',
            '(SELECT email FROM author WHERE id = book.author_id)'
        );

        $c->setOffset(10);
        $c->setLimit(10);

        $params = [];
        $sql = $c->createSelectSql($params);

        // Expect a subquery, with our SELECT subquery intact
        $expected = 'SELECT [book.id], [book.title], [book.isbn], [book.price], [book.publisher_id], [book.author_id], [author_email] FROM (SELECT ROW_NUMBER() OVER(ORDER BY book.id) AS [RowNumber], book.id AS [book.id], book.title AS [book.title], book.isbn AS [book.isbn], book.price AS [book.price], book.publisher_id AS [book.publisher_id], book.author_id AS [book.author_id], (SELECT email FROM author WHERE id = book.author_id) AS [author_email] FROM book) AS derivedb WHERE RowNumber BETWEEN 11 AND 20';

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test `applyLimit` with no offsetting and a subquery in the WHERE clause
     *
     * @return void
     */
    public function testApplyLimitZeroOffsetWithSubquery()
    {
        $c = new ModelCriteria();
        BookTableMap::addSelectColumns($c);

        $c->setOffset(0);
        $c->setLimit(10);
        $c->where('book.author_id IN (SELECT DISTINCT author_id FROM author)');

        $params = [];
        $result = $c->createSelectSql($params);

        // Expect a TOP N result with no subquery
        $expected = 'SELECT TOP 10 book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.author_id IN (SELECT DISTINCT author_id FROM author)';

        $this->assertEquals($expected, $result);
    }

    /**
     * Test `applyLimit` with page offsetting and a subquery in the WHERE clause
     *
     * @return void
     */
    public function testApplyLimitOffsetWithSubquery()
    {
        $c = new ModelCriteria();
        BookTableMap::addSelectColumns($c);

        $c->setOffset(10);
        $c->setLimit(10);
        $c->where('book.author_id IN (SELECT DISTINCT author_id FROM author)');

        $params = [];
        $result = $c->createSelectSql($params);

        // Expect a subquery that also maintains the WHERE clause subquery
        $expected = 'SELECT [book.id], [book.title], [book.isbn], [book.price], [book.publisher_id], [book.author_id] FROM (SELECT ROW_NUMBER() OVER(ORDER BY book.id) AS [RowNumber], book.id AS [book.id], book.title AS [book.title], book.isbn AS [book.isbn], book.price AS [book.price], book.publisher_id AS [book.publisher_id], book.author_id AS [book.author_id] FROM book WHERE book.author_id IN (SELECT DISTINCT author_id FROM author)) AS derivedb WHERE RowNumber BETWEEN 11 AND 20';

        $this->assertEquals($expected, $result);
    }

    /**
     * Regression: Ensure correct parsing when `from` is used as a non-keyword
     * e.g. as a column name
     *
     * @return void
     */
    public function testApplyLimitWithFromNonKeyword()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);

        $c->addAsColumn(
            'date_from',
            '(SELECT GETDATE())'
        );

        $c->setOffset(0);
        $c->setLimit(10);

        $params = [];
        $sql = $c->createSelectSql($params);

        // Expect a well-formed query where the `date_from` column remains untouched
        $expected = 'SELECT TOP 10 book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, (SELECT GETDATE()) AS date_from FROM book';

        $this->assertEquals($expected, $sql);
    }
}
