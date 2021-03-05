<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbMySQL adapter
 *
 * @see BookstoreDataPopulator
 * @author William Durand
 */
class MysqlAdapterTest extends TestCaseFixtures
{
    /**
     * @return array
     */
    public static function getConParams()
    {
        return [
            [
                [
                    'dsn' => 'dsn=my_dsn',
                    'settings' => [
                        'charset' => 'foobar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getDriver()
    {
        return 'mysql';
    }

    /**
     * @dataProvider getConParams
     *
     * @param array $conparams
     *
     * @return void
     */
    public function testPrepareParamsThrowsException($conparams)
    {
        $db = new TestableMysqlAdapter();
        $result = $db->prepareParams($conparams);

        $this->assertIsArray($result);
    }

    /**
     * @dataProvider getConParams
     *
     * @return void
     */
    public function testPrepareParams($conparams)
    {
        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $this->assertTrue(is_array($params));
        $this->assertEquals('dsn=my_dsn;charset=foobar', $params['dsn'], 'The given charset is in the DSN string');
        $this->assertArrayNotHasKey('charset', $params['settings'], 'The charset should be removed');
    }

    /**
     * @dataProvider getConParams
     *
     * @return void
     */
    public function testNoSetNameQueryExecuted($conparams)
    {
        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $settings = [];
        if (isset($params['settings'])) {
            $settings = $params['settings'];
        }

        $db->initConnection($this->getPdoMock(), $settings);
    }

    protected function getPdoMock()
    {
        $con = $this
            ->getMockBuilder('\Propel\Runtime\Connection\ConnectionInterface')->getMock();

        $con
            ->expects($this->never())
            ->method('exec');

        return $con;
    }

    /**
     * Test `applyLock`
     *
     * @group mysql
     *
     * @return void
     */
    public function testSimpleLock(): void
    {
        $c = new BookQuery();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->lockForShare();

        $params = [];
        $result = $c->createSelectSql($params);

        $expected = 'SELECT book.id FROM book LOCK IN SHARE MODE';

        $this->assertEquals($expected, $result);
    }

    /**
     * Test `applyLock`
     *
     * @group mysql
     *
     * @return void
     */
    public function testComplexLock(): void
    {
        $c = new BookQuery();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->lockForUpdate([BookTableMap::TABLE_NAME], true);

        $params = [];
        $result = $c->createSelectSql($params);

        $expected = 'SELECT book.id FROM book FOR UPDATE';

        $this->assertEquals($expected, $result);
    }

    /**
     * @group mysql
     *
     * @return void
     */
    public function testSubQueryWithSharedLock()
    {
        $subCriteria = new BookQuery();
        $subCriteria->addSelectColumn(BookTableMap::COL_ID);
        $subCriteria->lockForShare([BookTableMap::TABLE_NAME]);

        $c = new BookQuery();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias', false);
        $c->lockForShare([BookTableMap::TABLE_NAME], true);

        $expected = 'SELECT subCriteriaAlias.id FROM (SELECT book.id FROM book LOCK IN SHARE MODE) AS subCriteriaAlias LOCK IN SHARE MODE';

        $params = [];
        $this->assertSame($expected, $c->createSelectSql($params), 'Subquery contains shared read lock');
    }

    /**
     * @return void
     */
    public function testWrapOnlyNonaggregatedColumnSelectionLiterals()
    {
        $wrapper = new class extends MysqlAdapter{
            public function wrap(Criteria $queryBuilder, string $columnSelectionLiteral): string
            {
                return parent::adjustSimpleColumnSelectionLiteral($queryBuilder, $columnSelectionLiteral);
            }

            protected function wrapNonaggregatedSelectedColumn(string $columnSelectionLiteral): string
            {
                return 'was wrapped';
            }
        };
        $c = new Criteria();
        $this->assertEquals('table.id', $wrapper->wrap($c, 'table.id'), 'Column should be unchanged if no GROUP BY');

        $gbColumn = 'leGroupByColumn';
        $c->addGroupByColumn($gbColumn);
        $this->assertEquals($gbColumn, $wrapper->wrap($c, $gbColumn), 'Columns in GROUP BY should not change');
        $this->assertEquals('COUNT(*)', $wrapper->wrap($c, 'COUNT(*)'), 'Aggregated columns should not change');
        $this->assertEquals('was wrapped', $wrapper->wrap($c, 'table.id'), 'Columns not in aggregation should change');
    }

    /**
     * @return void
     */
    public function testWrappingOfNonaggregatedColumnSelectionLiteral()
    {
        $wrapper = new class extends MysqlAdapter{
            public function wrap(string $columnSelectionLiteral): string
            {
                return parent::wrapNonaggregatedSelectedColumn($columnSelectionLiteral);
            }
        };
        $this->assertEquals('ANY_VALUE(id) AS \'id\'', $wrapper->wrap('id'));
        $this->assertEquals('ANY_VALUE(table.id) AS \'id\'', $wrapper->wrap('table.id'));
    }
}

class TestableMysqlAdapter extends MysqlAdapter
{
    /**
     * @param array $conparams
     *
     * @return array
     */
    public function prepareParams($conparams)
    {
        return parent::prepareParams($conparams);
    }
}
