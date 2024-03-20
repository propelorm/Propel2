<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\SqlBuilder;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\SqlBuilder\SelectQuerySqlBuilder;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixtures;

class SelectQuerySqlBuilderTest extends TestCaseFixtures
{
    /**
     * @var bool
     */
    protected $configLoaded = false;

    /**
     * @return void
     */
    protected function loadConfig(): void
    {
        if ($this->configLoaded) {
            return;
        }
        parent::setUp();
        $this->setupWasExecuted = true;
    }

    /**
     * @return mixed[][]
     */
    public function havingClauseDataProvider(): array
    {
        $this->loadConfig();

        return [
            // [<criteria>, <having clause>, <params>, <message>]]
            [BookQuery::create(), null, [], 'Empty HAVING clause should build to null'],
            [
                BookQuery::create()->addHaving('Price', 42, Criteria::GREATER_THAN),
                'HAVING Price>:p1',
                [['table' => null, 'column' => 'Price', 'value' => 42]],
               'HAVING clause should build to statement'],
        ];
    }

    /**
     * @dataProvider havingClauseDataProvider
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $query
     * @param string|null $expectedClause
     * @param array $expectedParams
     * @param string $message
     *
     * @return void
     */
    public function testBuildHavingClause(Criteria $query, ?string $expectedClause, array $expectedParams, string $message): void
    {
        $builder = new class ($query) extends SelectQuerySqlBuilder{
            public function doBuildHavingClause(array &$params): ?string
            {
                return $this->buildHavingClause($params);
            }
        };
        $params = [];
        $clause = $builder->doBuildHavingClause($params);

        $this->assertSame($expectedClause, $clause, $message);
        $this->assertSame($expectedParams, $params, 'Generated query parameter array does not match');
    }

    /**
     * @return mixed[][]
     */
    public function fromClauseDataProvider(): array
    {
        return [
            // [<query>, <from tables>, <expected clause>, <expected params>, <message>]
            [BookQuery::create(), [], 'FROM book', [], 'Build simple from should work' ],
            [BookQuery::create(), ['book', 'book', '', null], 'FROM book', [], 'Builder should remove duplicates and emptie values' ],
            [BookQuery::create()->innerJoinAuthor(), [], 'FROM book INNER JOIN author ON (book.author_id=author.id)', [], 'Builder should build FROM with simple join' ],
            [BookQuery::create()->innerJoinAuthor(), ['author'], 'FROM book INNER JOIN author ON (book.author_id=author.id)', [], 'Builder should remove duplicate join tables' ],

        ];
    }

    /**
     * @dataProvider fromClauseDataProvider
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $query
     * @param array $fromTables
     * @param string $expectedClause
     * @param array $expectedParams
     * @param string $message
     *
     * @return void
     */
    public function testBuildFromClause(Criteria $query, array $fromTables, string $expectedClause, array $expectedParams, string $message): void
    {
        $builder = new class ($query) extends SelectQuerySqlBuilder{
            public function doBuildFromClause(array &$params, array $fromTables): ?string
            {
                $joinClauses = $this->buildJoinClauses($params, $fromTables);

                return $this->buildFromClause($params, $fromTables, $joinClauses);
            }
        };
        $params = [];
        $clause = $builder->doBuildFromClause($params, $fromTables);

        $this->assertSame($expectedClause, $clause, $message);
        $this->assertSame($expectedParams, $params, 'Generated query parameter array does not match');
    }

    /**
     * @return mixed[][]
     */
    public function removeRecursiveSubqueryTableAliasesDataProvider(): array
    {
        $this->loadConfig();

        $query = BookQuery::create()->addSelectQuery(BookQuery::create(), 'subquery');

        return [
            // [<query>, <from table names>, <expected table names>, <message>]
            [BookQuery::create(), ['book'], ['book'], 'Queries without subqueries should not change'],
            [$query, ['book subquery'], [], 'Queries with subqueries should remove the table alias'],
        ];
    }

    /**
     * @dataProvider removeRecursiveSubqueryTableAliasesDataProvider
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $query
     * @param array $fromTableNames
     * @param array $expectedTableNames
     * @param string $message
     *
     * @return void
     */
    public function testRemoveRecursiveSubqueryTableAliases(Criteria $query, array $fromTableNames, array $expectedTableNames, string $message): void
    {
        $builder = new class ($query) extends SelectQuerySqlBuilder{
            public function doResolve(array &$fromTableNames): ?string
            {
                return $this->removeRecursiveSubqueryTableAliases($fromTableNames);
            }
        };
        $builder->doResolve($fromTableNames);

        $this->assertSame($expectedTableNames, $fromTableNames, $message);
    }
}
