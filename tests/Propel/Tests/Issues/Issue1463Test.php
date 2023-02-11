<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Issue1463ItemQuery;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Regression tests for a SQL injection vulnerability with `limit()`.
 *
 * @link https://github.com/propelorm/Propel2/issues/1463
 */
class Issue1463Test extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (class_exists('\Issue1463Item')) {
            return;
        }

        $schema = <<<END
<database name="issue_1463">
    <table name="issue_1463_item">
        <column name="id" type="INTEGER" size="10" sqlType="INT(10) UNSIGNED" primaryKey="true" required="true" autoIncrement="true"/>
        <column name="name" type="VARCHAR" size="32" required="true"/>
    </table>
</database>
END;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);
        Propel::getServiceContainer()->setAdapter('issue_1463', new MysqlAdapter());
    }

    /**
     * Verifies that the correct SQL is generated for queries that use `limit()`.
     *
     * @dataProvider dataLimit
     *
     * @return void
     */
    public function testLimit($limit, $expectedSql)
    {
        $query = Issue1463ItemQuery::create()->limit($limit);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataLimit()
    {
        return [

            /*
                Valid limits
             */

            'Zero' => [
                'limit' => 0,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 0',
            ],

            'Small integer' => [
                'limit' => 38427,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427',
            ],
            'Small integer as a string' => [
                'limit' => '38427',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427',
            ],

            'Large integer' => [
                'limit' => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807',
            ],
            'Large integer as a string' => [
                'limit' => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807',
            ],

            'Decimal value' => [
                'limit' => 123.9,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123',
            ],
            'Decimal value as a string' => [
                'limit' => '123.9',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123',
            ],

            /*
                Invalid limits
             */

            'Negative value' => [
                'limit' => -1,
                'expectedSql' => 'SELECT  FROM issue_1463_item',
            ],
            'Non-numeric string' => [
                'limit' => 'foo',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 0',
            ],
            'Injected SQL' => [
                'limit' => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 3',
            ],
        ];
    }

    /**
     * Verifies that the correct SQL is generated for queries that use `offset()`.
     *
     * @dataProvider dataOffset
     *
     * @return void
     */
    public function testOffset($offset, $expectedSql)
    {
        $query = Issue1463ItemQuery::create()->offset($offset);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataOffset()
    {
        return [

            /*
                Valid offsets
             */

            'Zero' => [
                'offset' => 0,
                'expectedSql' => 'SELECT  FROM issue_1463_item',
            ],

            'Small integer' => [
                'offset' => 38427,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427, 18446744073709551615',
            ],
            'Small integer as a string' => [
                'offset' => '38427',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427, 18446744073709551615',
            ],

            'Large integer' => [
                'offset' => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807, 18446744073709551615',
            ],
            'Large integer as a string' => [
                'offset' => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807, 18446744073709551615',
            ],

            'Decimal value' => [
                'offset' => 123.9,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123, 18446744073709551615',
            ],
            'Decimal value as a string' => [
                'offset' => '123.9',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123, 18446744073709551615',
            ],

            /*
                Invalid offsets
             */

            'Negative value' => [
                'offset' => -1,
                'expectedSql' => 'SELECT  FROM issue_1463_item',
            ],
            'Non-numeric string' => [
                'offset' => 'foo',
                'expectedSql' => 'SELECT  FROM issue_1463_item',
            ],
            'Injected SQL' => [
                'offset' => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 3, 18446744073709551615',
            ],
        ];
    }

    /**
     * Verifies that the correct SQL is generated for queries that use both `offset()` and `limit()`.
     *
     * @dataProvider dataOffsetAndLimit
     *
     * @return void
     */
    public function testOffsetAndLimit($offset, $expectedSql)
    {
        $query = Issue1463ItemQuery::create()->offset($offset)->limit(999);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataOffsetAndLimit()
    {
        return [

            /*
                Valid offsets
             */

            'Zero' => [
                'offset' => 0,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 999',
            ],

            'Small integer' => [
                'offset' => 38427,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427, 999',
            ],
            'Small integer as a string' => [
                'offset' => '38427',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 38427, 999',
            ],

            'Large integer' => [
                'offset' => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807, 999',
            ],
            'Large integer as a string' => [
                'offset' => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 9223372036854775807, 999',
            ],

            'Decimal value' => [
                'offset' => 123.9,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123, 999',
            ],
            'Decimal value as a string' => [
                'offset' => '123.9',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 123, 999',
            ],

            /*
                Invalid offsets
             */

            'Negative value' => [
                'offset' => -1,
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 999',
            ],
            'Non-numeric string' => [
                'offset' => 'foo',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 999',
            ],
            'Injected SQL' => [
                'offset' => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM issue_1463_item LIMIT 3, 999',
            ],
        ];
    }
}
