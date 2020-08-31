<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Issue1192ItemQuery;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/1192.
 *
 * @link https://github.com/propelorm/Propel2/issues/1192
 */
class Issue1192Test extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (class_exists('\Issue1192Item')) {
            return;
        }

        $schema = <<<END
<database name="issue_1192">
    <table name="issue_1192_item">
        <column name="target" type="INTEGER" required="true"/>
    </table>
</database>
END;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);
        Propel::getServiceContainer()->setAdapter('issue_1192', new MysqlAdapter());
    }

    /**
     * Verifies that the correct SQL and Params are generated for queries that use `Criteria::BINARY_ALL`.
     *
     * @return void
     */
    public function testCriteriaBinaryAll()
    {
        /** @var \Propel\Runtime\ActiveQuery\ModelCriteria $query */
        $query = Issue1192ItemQuery::create()->filterByTarget(1, Criteria::BINARY_ALL);
        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertSame(
            'SELECT  FROM issue_1192_item WHERE issue_1192_item.target & :p1 = :p2',
            $actualSql,
            'Generated SQL does not match expected SQL'
        );

        $this->assertSame(2, count($params), 'Incorrect number of params');

        $this->assertSame($params[0], $params[1], 'Params are not identical');
    }
}
